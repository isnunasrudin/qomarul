<?php

use App\Enums\DecreeStatus;
use App\Models\Decree;
use App\Models\DecreeType;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;

beforeEach(function () {
    $this->unit = WorkUnit::factory()->create();
    $this->employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $this->type = DecreeType::factory()->create();
    $this->admin = User::factory()->foundationAdmin()->create();
    $this->unitAdmin = User::factory()->unitAdmin($this->unit->id)->create();

    $this->decree = Decree::factory()->create([
        'employee_id' => $this->employee->id,
        'work_unit_id' => $this->unit->id,
        'decree_type_id' => $this->type->id,
        'status' => DecreeStatus::Issued,
        'decree_number' => '001/SK-PPT/SMK/YPP-QH/VII/2026',
        'registration_number' => 'E-1',
        'snapshot_data' => ['name' => 'Terkunci'],
    ]);
});

it('denies editing an issued decree through the policy layer', function () {
    $this->actingAs($this->admin);

    expect($this->admin->can('update', $this->decree))->toBeFalse();
    expect($this->admin->can('delete', $this->decree))->toBeFalse();
    expect($this->admin->can('submit', $this->decree))->toBeFalse();
    expect($this->admin->can('verify', $this->decree))->toBeFalse();

    $this->actingAs($this->unitAdmin);
    expect($this->unitAdmin->can('update', $this->decree))->toBeFalse();
});

it('denies editing an issued decree through every route', function () {
    $this->actingAs($this->admin);

    // tidak ada rute update/delete untuk SK; jalur lain yang mungkin:
    $this->post("/admin/decrees/{$this->decree->id}/submit")->assertForbidden();
    $this->post("/admin/decrees/{$this->decree->id}/verify")->assertForbidden();

    $this->actingAs($this->unitAdmin);
    $this->post("/admin/decrees/{$this->decree->id}/submit")->assertForbidden();
});

it('keeps the stored pdf path and hash intact', function () {
    expect($this->decree->pdf_path)->toBeNull();
    $this->decree->update(['pdf_path' => 'decrees/2026/E-1-nigy.pdf', 'pdf_hash' => str_repeat('a', 64)]);

    // data tetap utuh setelah percobaan jalur lain
    $this->actingAs($this->admin);
    $this->post("/admin/decrees/{$this->decree->id}/submit")->assertForbidden();

    $fresh = $this->decree->fresh();
    expect($fresh->pdf_path)->toBe('decrees/2026/E-1-nigy.pdf');
    expect($fresh->status)->toBe(DecreeStatus::Issued);
});

it('allows the head to cancel an issued decree with a reason', function () {
    $head = User::factory()->foundationHead()->create();

    $this->actingAs($head)
        ->post("/admin/decrees/{$this->decree->id}/cancel", ['notes' => 'Data jabatan tidak sesuai'])
        ->assertSessionHasNoErrors();

    expect($this->decree->fresh()->status)->toBe(DecreeStatus::Cancelled);
    expect($this->decree->fresh()->cancellation_reason)->toBe('Data jabatan tidak sesuai');
});

it('blocks cancelling without a reason', function () {
    $head = User::factory()->foundationHead()->create();

    $this->actingAs($head)
        ->post("/admin/decrees/{$this->decree->id}/cancel", ['notes' => ''])
        ->assertSessionHasErrors('notes');
});
