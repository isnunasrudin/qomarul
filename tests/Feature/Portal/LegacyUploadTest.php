<?php

use App\Models\Decree;
use App\Models\DecreeType;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');

    DecreeType::factory()->create(['code' => 'SK-PPT']);

    $this->unit = WorkUnit::factory()->create();
    $this->employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $this->employeeUser = User::factory()->employee($this->employee->id)->create();
});

it('stores an uploaded legacy decree as unverified', function () {
    $this->actingAs($this->employeeUser)
        ->post('/portal/decrees/legacy', [
            'file' => UploadedFile::fake()->createWithContent('sk-lama.pdf', "%PDF-1.4\n%%EOF"),
            'decree_number' => '012/SK-PPT/SD1/YPP-QH/VII/2020',
        ])
        ->assertRedirect();

    $decree = Decree::where('employee_id', $this->employee->id)->firstOrFail();

    expect($decree->is_legacy)->toBeTrue();
    expect($decree->legacy_verified_at)->toBeNull();
    expect($decree->status->value)->toBe('issued');
    expect($decree->decree_number)->toBe('012/SK-PPT/SD1/YPP-QH/VII/2020');
    Storage::disk('private')->assertExists($decree->pdf_path);
});

it('rejects non-pdf files as legacy archives', function () {
    $this->actingAs($this->employeeUser)
        ->post('/portal/decrees/legacy', [
            'file' => UploadedFile::fake()->createWithContent('sk-lama.png', 'bukan pdf'),
        ])
        ->assertSessionHasErrors('file');

    expect(Decree::count())->toBe(0);
});

it('excludes unverified archives from the official history until verified', function () {
    $this->actingAs($this->employeeUser)
        ->post('/portal/decrees/legacy', [
            'file' => UploadedFile::fake()->createWithContent('sk-lama.pdf', "%PDF-1.4\n%%EOF"),
        ]);

    // belum diverifikasi → tidak tampil di riwayat resmi
    $this->actingAs($this->employeeUser)
        ->get('/portal')
        ->assertInertia(fn ($page) => $page->component('Portal/Home')
            ->has('recentDecrees', 0));

    // diverifikasi admin → tampil
    $admin = User::factory()->foundationAdmin()->create();
    $decree = Decree::firstOrFail();

    $this->actingAs($admin)
        ->post("/admin/decree-legacy/{$decree->id}/verify")
        ->assertRedirect();

    expect($decree->fresh()->legacy_verified_at)->not->toBeNull();

    $this->actingAs($this->employeeUser)
        ->get('/portal')
        ->assertInertia(fn ($page) => $page->component('Portal/Home')
            ->has('recentDecrees', 1));
});

it('allows unit admin to verify archives of their own unit only', function () {
    $otherUnit = WorkUnit::factory()->create();
    $otherEmployee = Employee::factory()->create(['work_unit_id' => $otherUnit->id]);
    $otherUser = User::factory()->employee($otherEmployee->id)->create();

    $this->actingAs($otherUser)
        ->post('/portal/decrees/legacy', [
            'file' => UploadedFile::fake()->createWithContent('sk-lama.pdf', "%PDF-1.4\n%%EOF"),
        ]);

    $decree = Decree::firstOrFail();

    $unitAdmin = User::factory()->unitAdmin($this->unit->id)->create();

    // arsip GTK unit lain tak terlihat oleh tenancy → 404
    $this->actingAs($unitAdmin)
        ->post("/admin/decree-legacy/{$decree->id}/verify")
        ->assertNotFound();
});
