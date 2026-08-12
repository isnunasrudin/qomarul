<?php

use App\Enums\DecreeStatus;
use App\Models\Decree;
use App\Models\DecreeType;
use App\Models\DecreeWorkflowLog;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Decree\DecreeWorkflowService;

beforeEach(function () {
    $this->unit = WorkUnit::factory()->create(['code' => 'SMK']);
    $this->employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $this->type = DecreeType::factory()->create(['code' => 'SK-PPT']);
    $this->unitAdmin = User::factory()->unitAdmin($this->unit->id)->create();
    $this->admin = User::factory()->foundationAdmin()->create();
    $this->head = User::factory()->foundationHead()->create();

    $this->decree = Decree::factory()->create([
        'employee_id' => $this->employee->id,
        'work_unit_id' => $this->unit->id,
        'decree_type_id' => $this->type->id,
        'academic_year' => '2026/2027',
        'issued_date' => '2026-07-01',
        'effective_date' => '2026-07-01',
        'status' => DecreeStatus::Draft,
    ]);
});

it('runs the full legal flow draft → submitted → verified → issued', function () {
    $service = app(DecreeWorkflowService::class);

    $this->actingAs($this->unitAdmin);

    $decree = $service->transition($this->decree, DecreeStatus::Submitted, $this->unitAdmin);
    expect($decree->status)->toBe(DecreeStatus::Submitted);

    $this->actingAs($this->admin);
    $decree = $service->transition($decree, DecreeStatus::Verified, $this->admin);

    expect($decree->status)->toBe(DecreeStatus::Verified);
    expect($decree->decree_number)->toBe('001/SK-PPT/SMK/YPP-QH/VII/2026');
    expect($decree->registration_number)->toBe('E-1');
    expect($decree->verified_at)->not->toBeNull();

    $this->actingAs($this->head);
    $decree = $service->transition($decree, DecreeStatus::Issued, $this->head);

    expect($decree->status)->toBe(DecreeStatus::Issued);
    expect($decree->signed_at)->not->toBeNull();
});

it('rejects illegal transitions', function () {
    $service = app(DecreeWorkflowService::class);

    $this->actingAs($this->unitAdmin);

    // draft → issued (lompat verifikasi)
    expect(fn () => $service->transition($this->decree, DecreeStatus::Issued, $this->unitAdmin))
        ->toThrow(RuntimeException::class, 'Transisi ilegal');

    // issued → verified
    $this->decree->update(['status' => DecreeStatus::Issued]);
    expect(fn () => $service->transition($this->decree, DecreeStatus::Verified, $this->admin))
        ->toThrow(RuntimeException::class, 'Transisi ilegal');
});

it('rejects transitions by the wrong role', function () {
    $service = app(DecreeWorkflowService::class);

    // unit_admin tidak boleh verifikasi
    $this->decree->update(['status' => DecreeStatus::Submitted]);

    expect(fn () => $service->transition($this->decree, DecreeStatus::Verified, $this->unitAdmin))
        ->toThrow(RuntimeException::class, 'Peran tidak berhak');

    // foundation_admin tidak boleh tanda tangan
    $this->decree->update(['status' => DecreeStatus::Verified]);

    expect(fn () => $service->transition($this->decree, DecreeStatus::Issued, $this->admin))
        ->toThrow(RuntimeException::class, 'Peran tidak berhak');
});

it('requires a reason for rejection', function () {
    $service = app(DecreeWorkflowService::class);

    $this->decree->update(['status' => DecreeStatus::Submitted]);

    $this->actingAs($this->admin);

    expect(fn () => $service->transition($this->decree, DecreeStatus::Rejected, $this->admin, null))
        ->toThrow(RuntimeException::class, 'wajib menyertakan alasan');
});

it('writes a workflow log for every transition', function () {
    $service = app(DecreeWorkflowService::class);

    $this->actingAs($this->unitAdmin);
    $service->transition($this->decree, DecreeStatus::Submitted, $this->unitAdmin, 'Diajukan');

    $log = DecreeWorkflowLog::query()->firstOrFail();

    expect($log->from_status)->toBe('draft');
    expect($log->to_status)->toBe('submitted');
    expect($log->user_id)->toBe($this->unitAdmin->id);
    expect($log->notes)->toBe('Diajukan');
});

it('allows resubmission after rejection', function () {
    $service = app(DecreeWorkflowService::class);

    $this->actingAs($this->admin);
    $this->decree->update(['status' => DecreeStatus::Submitted]);
    $service->transition($this->decree, DecreeStatus::Rejected, $this->admin, 'Data TMT belum sesuai');

    $this->actingAs($this->unitAdmin);
    $decree = $service->transition($this->decree->fresh(), DecreeStatus::Submitted, $this->unitAdmin);

    expect($decree->status)->toBe(DecreeStatus::Submitted);
});

it('allocates numbers exactly once per verification cycle', function () {
    $service = app(DecreeWorkflowService::class);

    $this->decree->update(['status' => DecreeStatus::Submitted]);

    $this->actingAs($this->admin);
    $decree = $service->transition($this->decree, DecreeStatus::Verified, $this->admin);

    // tolak lalu verifikasi ulang — nomor baru dialokasikan, nomor lama tidak dipakai ulang (F5.16)
    $this->actingAs($this->admin);
    $service->transition($decree, DecreeStatus::Rejected, $this->admin, 'cek ulang');

    $this->actingAs($this->unitAdmin);
    $service->transition($decree->fresh(), DecreeStatus::Submitted, $this->unitAdmin);

    $this->actingAs($this->admin);
    $decree = $service->transition($decree->fresh(), DecreeStatus::Verified, $this->admin);

    expect($decree->decree_number)->toBe('002/SK-PPT/SMK/YPP-QH/VII/2026');
    expect($decree->sequence_number)->toBe(2);
});

it('marks the old decree superseded and points to the replacement', function () {
    $service = app(DecreeWorkflowService::class);

    $this->actingAs($this->unitAdmin);
    $old = $service->transition($this->decree, DecreeStatus::Submitted, $this->unitAdmin);

    $this->actingAs($this->admin);
    $old = $service->transition($old, DecreeStatus::Verified, $this->admin);

    $this->actingAs($this->head);
    $old = $service->transition($old, DecreeStatus::Issued, $this->head);

    $replacement = Decree::factory()->create([
        'employee_id' => $this->employee->id,
        'work_unit_id' => $this->unit->id,
        'decree_type_id' => $this->type->id,
        'academic_year' => '2026/2027',
        'issued_date' => '2026-07-15',
        'effective_date' => '2026-07-01',
        'status' => DecreeStatus::Verified,
    ]);

    $issued = $service->issueReplacement($replacement, $old, $this->head);

    expect($issued->status)->toBe(DecreeStatus::Issued);
    expect($old->fresh()->status)->toBe(DecreeStatus::Superseded);
    expect($old->fresh()->replacement_decree_id)->toBe($replacement->id);
});
