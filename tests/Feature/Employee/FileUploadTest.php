<?php

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Storage::fake('private');

    $this->workUnit = WorkUnit::factory()->create();
    $this->employee = Employee::factory()->create(['work_unit_id' => $this->workUnit->id]);
    $this->admin = User::factory()->foundationAdmin()->create();
});

it('stores a photo with real mime and applies 3:4 crop', function () {
    $jpeg = makeJpeg(600, 800);

    $this->actingAs($this->admin)
        ->post('/admin/employees', [
            'name' => 'Siti Aminah',
            'gender' => 'female',
            'work_unit_id' => $this->workUnit->id,
            'position_id' => $this->employee->position_id,
            'employment_status_id' => $this->employee->employment_status_id,
            'foundation_start_date' => '2026-07-01',
            'photo' => UploadedFile::fake()->createWithContent('foto.jpg', $jpeg),
        ])->assertRedirect();

    $created = Employee::where('name', 'Siti Aminah')->firstOrFail();

    expect($created->photo_path)->not->toBeNull();
    Storage::disk('private')->assertExists($created->photo_path);

    // verifikasi rasio hasil crop benar 3:4
    [$width, $height] = getimagesizefromstring(Storage::disk('private')->get($created->photo_path));
    expect($height / $width)->toBeGreaterThanOrEqual(1.33);
});

it('rejects a photo whose content is not an image', function () {
    $this->actingAs($this->admin)
        ->post('/admin/employees', [
            'name' => 'Siti Aminah',
            'gender' => 'female',
            'work_unit_id' => $this->workUnit->id,
            'position_id' => $this->employee->position_id,
            'employment_status_id' => $this->employee->employment_status_id,
            'foundation_start_date' => '2026-07-01',
            'photo' => UploadedFile::fake()->createWithContent('foto.png', 'ini bukan gambar'),
        ])->assertSessionHasErrors('photo');

    expect(Employee::where('name', 'Siti Aminah')->exists())->toBeFalse();
});

it('rejects documents whose content mime is not pdf/jpg/png', function () {
    $this->actingAs($this->admin)
        ->post("/admin/employees/{$this->employee->id}/documents", [
            'category' => DocumentCategory::Ktp->value,
            'file' => UploadedFile::fake()->createWithContent('ktp.pdf', 'bukan pdf sama sekali'),
        ])->assertSessionHasErrors('file');

    expect(Document::count())->toBe(0);
});

it('stores documents on the private disk and hides them from guessed urls', function () {
    $this->actingAs($this->admin)
        ->post("/admin/employees/{$this->employee->id}/documents", [
            'category' => DocumentCategory::Diploma->value,
            'file' => UploadedFile::fake()->createWithContent('ijazah.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF"),
        ])->assertSessionHasNoErrors();

    $document = Document::firstOrFail();
    Storage::disk('private')->assertExists($document->path);

    // tidak ada rute yang menyajikan berkas privat; URL tebakan storage gagal
    $response = $this->get("/storage/{$document->path}");
    expect(in_array($response->status(), [403, 404], true))->toBeTrue();
});

it('requires a valid signature to download a document', function () {
    $document = Document::create([
        'employee_id' => $this->employee->id,
        'category' => DocumentCategory::Ktp->value,
        'name' => 'ktp.pdf',
        'path' => 'documents/1/ktp.pdf',
        'mime' => 'application/pdf',
        'size' => 1024,
        'uploaded_by' => $this->admin->id,
    ]);

    Storage::disk('private')->put($document->path, '%PDF-1.4 fake');

    // tanpa tanda tangan → 403
    $this->actingAs($this->admin)
        ->get("/documents/{$document->id}/download")
        ->assertForbidden();

    // dengan tanda tangan → 200
    $signed = URL::temporarySignedRoute(
        'admin.documents.download',
        now()->addMinutes(5),
        ['document' => $document->id],
    );

    $this->actingAs($this->admin)
        ->get($signed)
        ->assertOk();
});

it('refuses to expose documents of another employee to an employee user', function () {
    $colleague = Employee::factory()->create(['work_unit_id' => $this->workUnit->id]);

    $document = Document::create([
        'employee_id' => $colleague->id,
        'category' => DocumentCategory::Ktp->value,
        'name' => 'ktp.pdf',
        'path' => 'documents/2/ktp.pdf',
        'mime' => 'application/pdf',
        'size' => 1024,
    ]);

    $user = User::factory()->employee($this->employee->id)->create();

    $signed = URL::temporarySignedRoute(
        'admin.documents.download',
        now()->addMinutes(5),
        ['document' => $document->id],
    );

    $response = $this->actingAs($user)->get($signed);

    // 404 (tak terlihat oleh tenancy) atau 403 (ditolak) keduanya benar
    expect(in_array($response->status(), [403, 404], true))->toBeTrue();
});

/** Buat gambar JPEG asli lewat GD. */
function makeJpeg(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);

    ob_start();
    imagejpeg($image, null, 90);
    $data = ob_get_clean();
    imagedestroy($image);

    return $data;
}
