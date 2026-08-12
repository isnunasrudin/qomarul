<?php

use App\Enums\DecreeStatus;
use App\Models\Certificate;
use App\Models\Decree;
use App\Models\DecreeSignature;
use App\Models\DecreeType;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->unit = WorkUnit::factory()->create(['code' => 'SMK']);
    $this->employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $this->type = DecreeType::factory()->create(['code' => 'SK-PPT']);
    $this->head = User::factory()->foundationHead()->create();
    $this->admin = User::factory()->foundationAdmin()->create();

    Setting::set('foundation.chairman_name', 'KH. Ahmad Dahlan, M.Pd.', 'foundation');

    $this->decree = Decree::factory()->create([
        'employee_id' => $this->employee->id,
        'work_unit_id' => $this->unit->id,
        'decree_type_id' => $this->type->id,
        'status' => DecreeStatus::Verified,
        'effective_date' => '2026-07-01',
        'issued_date' => '2026-07-10',
        'academic_year' => '2026/2027',
        'appointed_as' => 'Guru',
        'decree_number' => '001/SK-PPT/SMK/YPP-QH/VII/2026',
        'registration_number' => 'E-1',
    ]);
});

it('records the pdf hash and a signature row when the decree is issued', function () {
    $this->actingAs($this->head)
        ->post("/admin/decrees/{$this->decree->id}/issue")
        ->assertSessionHasNoErrors();

    $decree = $this->decree->fresh();

    expect($decree->status)->toBe(DecreeStatus::Issued);
    expect($decree->pdf_path)->not->toBeNull();
    expect($decree->pdf_hash)->toBeString()->toHaveLength(64);
    expect($decree->pdf_hash)->toMatch('/^[a-f0-9]{64}$/');

    $signature = DecreeSignature::query()->where('decree_id', $decree->id)->firstOrFail();
    expect($signature->hash_sha256)->toBe($decree->pdf_hash);
    expect($signature->signer_name)->toBe('KH. Ahmad Dahlan, M.Pd.');
    expect($signature->signature_meta['reason'])->toBe('Pengesahan SK');
});

it('fails hash verification when the pdf is altered by a single byte', function () {
    $this->actingAs($this->head)
        ->post("/admin/decrees/{$this->decree->id}/issue")
        ->assertSessionHasNoErrors();

    $decree = $this->decree->fresh();
    $pdf = Storage::disk('private')->get($decree->pdf_path);

    expect(hash('sha256', $pdf))->toBe($decree->pdf_hash);

    // ubah 1 byte
    $tampered = substr($pdf, 0, -1).'X';

    expect(hash('sha256', $tampered))->not->toBe($decree->pdf_hash);
});

it('rejects a self-verification upload whose hash differs', function () {
    $this->actingAs($this->head)
        ->post("/admin/decrees/{$this->decree->id}/issue")
        ->assertSessionHasNoErrors();

    $decree = $this->decree->fresh();
    $original = Storage::disk('private')->get($decree->pdf_path);

    $this->post('/verifikasi/periksa', [
        'uuid' => $decree->uuid,
        'file' => UploadedFile::fake()->createWithContent('tampered.pdf', substr($original, 0, -1).'X'),
    ])->assertSessionHas('result.matches', false);

    $this->post('/verifikasi/periksa', [
        'uuid' => $decree->uuid,
        'file' => UploadedFile::fake()->createWithContent('asli.pdf', $original),
    ])->assertSessionHas('result.matches', true);
});

it('embeds the verification qr url in the signature metadata', function () {
    $this->actingAs($this->head)
        ->post("/admin/decrees/{$this->decree->id}/issue")
        ->assertSessionHasNoErrors();

    $decree = $this->decree->fresh();

    $signature = DecreeSignature::query()->where('decree_id', $decree->id)->firstOrFail();
    $expected = rtrim(config('app.url'), '/').'/verifikasi/'.$decree->uuid;

    expect($signature->signature_meta['qr_url'])->toBe($expected);
});

it('keeps old decrees verifiable with the certificate used at issue time', function () {
    $p12 = file_get_contents(base_path('storage/app/private/certificates/dev-yayasan.p12'));
    Storage::disk('private')->put('certificates/dev.p12', $p12);

    Certificate::create([
        'name' => 'dev',
        'p12_path' => 'certificates/dev.p12',
        'password_encrypted' => Crypt::encryptString('dev-simqoh-2026'),
        'subject' => 'Yayasan Pondok Pesantren Qomarul Hidayah',
        'fingerprint' => 'f'.str_repeat('0', 63),
        'is_active' => true,
    ]);

    $this->actingAs($this->head)
        ->post("/admin/decrees/{$this->decree->id}/issue")
        ->assertSessionHasNoErrors();

    $certId = DecreeSignature::query()->where('decree_id', $this->decree->id)->value('certificate_id');

    expect($certId)->not->toBeNull();

    Storage::disk('private')->delete('certificates/dev.p12');
});
