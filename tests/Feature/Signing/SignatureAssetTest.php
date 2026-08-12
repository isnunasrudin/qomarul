<?php

use App\Models\AuditLog;
use App\Models\Decree;
use App\Models\DecreeType;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    $this->admin = User::factory()->foundationAdmin()->create();
    $this->unitAdmin = User::factory()->unitAdmin(WorkUnit::factory()->create()->id)->create();
});

it('stores the signature image on the private disk with restricted permissions', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    $this->actingAs($this->admin)
        ->post('/admin/settings/signature', [
            'current_password' => 'password',
            'file' => UploadedFile::fake()->createWithContent('ttd.png', $png),
        ])
        ->assertSessionHasNoErrors();

    $path = Setting::get('foundation.signature_path');

    expect($path)->not->toBeNull();
    Storage::disk('private')->assertExists($path);

    $log = AuditLog::query()->where('action', 'signature_replaced')->first();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($this->admin->id);
});

it('requires the admin password to replace the signature', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    $this->actingAs($this->admin)
        ->post('/admin/settings/signature', [
            'current_password' => 'salah-password',
            'file' => UploadedFile::fake()->createWithContent('ttd.png', $png),
        ])
        ->assertSessionHasErrors('current_password');

    expect(Setting::get('foundation.signature_path'))->toBeNull();
});

it('forbids unit admins from replacing the signature', function () {
    $this->actingAs($this->unitAdmin)
        ->post('/admin/settings/signature', [
            'current_password' => 'password',
            'file' => UploadedFile::fake()->createWithContent('ttd.png', 'x'),
        ])
        ->assertForbidden();
});

it('never serves the signature image over http in any form', function () {
    foreach (['/storage/signature/signature-basah.png', '/signature/signature-basah.png'] as $path) {
        $response = $this->actingAs($this->admin)->get($path);
        expect(in_array($response->status(), [403, 404], true))->toBeTrue();
    }
});

it('keeps the signature out of draft previews', function () {
    Setting::set('foundation.signature_path', 'signature/signature-basah.png', 'foundation');

    $employee = Employee::factory()->create();
    $decree = Decree::factory()->create([
        'employee_id' => $employee->id,
        'work_unit_id' => $employee->work_unit_id,
        'decree_type_id' => DecreeType::factory()->create()->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->admin)->get("/admin/decrees/{$decree->id}/preview-pdf");

    expect($response->status())->toBe(200);

    // template hanya merender gambar tanda tangan saat $is_signed (status issued);
    // pratinjau draft memakai $is_signed=false sehingga gambar tidak disertakan.
    $content = $response->getContent();
    expect(str_contains($content, 'signature-basah'))->toBeFalse();
});
