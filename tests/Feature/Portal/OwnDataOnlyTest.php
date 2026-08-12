<?php

use App\Models\Decree;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Storage::fake('private');

    $this->unit = WorkUnit::factory()->create();
    $this->employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $this->colleague = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $this->employeeUser = User::factory()->employee($this->employee->id)->create();
    $this->colleagueUser = User::factory()->employee($this->colleague->id)->create();
});

it('lets an employee access only the portal home with their own data', function () {
    $this->actingAs($this->employeeUser)
        ->get('/portal')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Portal/Home')
            ->where('employee.id', $this->employee->id)
            ->where('employee.nigy', $this->employee->nigy));
});

it('rejects portal access for non-employee roles', function () {
    $admin = User::factory()->foundationAdmin()->create();

    $this->actingAs($admin)->get('/portal')->assertForbidden();
});

it('blocks downloading a document that belongs to another employee', function () {
    $document = Document::create([
        'employee_id' => $this->colleague->id,
        'category' => 'ktp',
        'name' => 'ktp.pdf',
        'path' => 'documents/2/ktp.pdf',
        'mime' => 'application/pdf',
        'size' => 100,
    ]);

    Storage::disk('private')->put($document->path, '%PDF-1.4');

    $signed = URL::temporarySignedRoute(
        'portal.documents.download',
        now()->addMinutes(5),
        ['document' => $document->id],
    );

    $response = $this->actingAs($this->employeeUser)
        ->get($signed);

    // 404 (tak terlihat oleh tenancy) atau 403 (ditolak) keduanya benar
    expect(in_array($response->status(), [403, 404], true))->toBeTrue();
});

it('blocks downloading a decree that belongs to another employee', function () {
    $decree = Decree::factory()->create([
        'employee_id' => $this->colleague->id,
        'work_unit_id' => $this->unit->id,
        'status' => 'issued',
        'pdf_path' => 'decrees/2026/1-2.pdf',
    ]);

    Storage::disk('private')->put($decree->pdf_path, '%PDF-1.4');

    $signed = URL::temporarySignedRoute(
        'portal.decrees.download',
        now()->addMinutes(5),
        ['decree' => $decree->id],
    );

    $response = $this->actingAs($this->employeeUser)
        ->get($signed);

    // 404 (tak terlihat oleh tenancy) atau 403 (ditolak) keduanya benar
    expect(in_array($response->status(), [403, 404], true))->toBeTrue();
});

it('lets an employee download their own issued decree', function () {
    $decree = Decree::factory()->create([
        'employee_id' => $this->employee->id,
        'work_unit_id' => $this->unit->id,
        'status' => 'issued',
        'decree_number' => '042/SK-PPT/SD1/YPP-QH/VII/2026',
        'pdf_path' => 'decrees/2026/042.pdf',
    ]);

    Storage::disk('private')->put($decree->pdf_path, '%PDF-1.4');

    $signed = URL::temporarySignedRoute(
        'portal.decrees.download',
        now()->addMinutes(5),
        ['decree' => $decree->id],
    );

    $this->actingAs($this->employeeUser)
        ->get($signed)
        ->assertOk();
});

it('hides pending legacy archives from the official decree list', function () {
    Decree::factory()->create([
        'employee_id' => $this->employee->id,
        'work_unit_id' => $this->unit->id,
        'status' => 'issued',
        'is_legacy' => true,
        'legacy_verified_at' => null,
    ]);

    $this->actingAs($this->employeeUser)
        ->get('/portal')
        ->assertInertia(fn ($page) => $page->component('Portal/Home')
            ->has('recentDecrees', 0));
});
