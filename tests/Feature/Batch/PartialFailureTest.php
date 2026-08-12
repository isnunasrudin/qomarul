<?php

use App\Enums\DecreeBatchStatus;
use App\Enums\DecreeStatus;
use App\Models\Decree;
use App\Models\DecreeType;
use App\Models\Education;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Decree\BatchDecreeService;

beforeEach(function () {
    $this->unit = WorkUnit::factory()->create(['code' => 'SMK']);
    $this->type = DecreeType::factory()->create(['code' => 'SK-PPJ']);
    $this->admin = User::factory()->foundationAdmin()->create();
    $this->head = User::factory()->foundationHead()->create();
});

it('marks recipients with missing data as failed and processes the rest', function () {
    $ok1 = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    Education::factory()->create(['employee_id' => $ok1->id, 'is_highest' => true]);

    $ok2 = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    Education::factory()->create(['employee_id' => $ok2->id, 'is_highest' => true]);

    // tanpa pendidikan tertinggi → harus gagal
    $broken = Employee::factory()->create(['work_unit_id' => $this->unit->id]);

    $batch = app(BatchDecreeService::class)->createBatch(
        [$ok1->id, $ok2->id, $broken->id],
        [
            'name' => 'Uji Parsial',
            'decree_type_id' => $this->type->id,
            'academic_year' => '2026/2027',
            'effective_date' => '2026-07-01',
            'issued_date' => '2026-07-15',
        ],
        $this->admin,
    );

    $result = app(BatchDecreeService::class)->processBatch($batch, $this->admin);

    expect($result['succeeded'])->toBe(2);
    expect($result['failed'])->toBe(1);
    expect($result['failures'][0])->toContain('Pendidikan tertinggi');

    $brokenDecree = Decree::where('employee_id', $broken->id)->first();
    expect($brokenDecree->status)->toBe(DecreeStatus::Rejected);
    expect($brokenDecree->rejection_reason)->toContain('Pendidikan tertinggi');

    $okDecree = Decree::where('employee_id', $ok1->id)->first();
    expect($okDecree->status)->toBe(DecreeStatus::Verified);
    expect($okDecree->decree_number)->toBe('001/SK-PPJ/SMK/YPP-QH/VII/2026');

    expect($batch->fresh()->status)->toBe(DecreeBatchStatus::AwaitingSignature);
});

it('allocates sequential unique numbers across the whole batch', function () {
    $employees = collect();

    foreach (range(1, 12) as $i) {
        $employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
        Education::factory()->create(['employee_id' => $employee->id, 'is_highest' => true]);
        $employees->push($employee);
    }

    $batch = app(BatchDecreeService::class)->createBatch(
        $employees->pluck('id')->all(),
        [
            'name' => 'Uji 12',
            'decree_type_id' => $this->type->id,
            'academic_year' => '2026/2027',
            'effective_date' => '2026-07-01',
            'issued_date' => '2026-07-15',
        ],
        $this->admin,
    );

    app(BatchDecreeService::class)->processBatch($batch, $this->admin);

    $numbers = Decree::where('decree_batch_id', $batch->id)
        ->orderBy('sequence_number')
        ->pluck('sequence_number')
        ->all();

    expect($numbers)->toBe(range(1, 12));
    expect(Decree::where('decree_batch_id', $batch->id)->pluck('decree_number')->unique()->count())->toBe(12);
});

it('enforces the 500 SK batch limit', function () {
    $employees = collect();

    foreach (range(1, 501) as $i) {
        $employees->push(Employee::factory()->create(['work_unit_id' => $this->unit->id]));
    }

    expect(fn () => app(BatchDecreeService::class)->createBatch(
        $employees->pluck('id')->all(),
        [
            'name' => 'Terlalu Besar',
            'decree_type_id' => $this->type->id,
            'academic_year' => '2026/2027',
            'effective_date' => '2026-07-01',
            'issued_date' => '2026-07-15',
        ],
        $this->admin,
    ))->toThrow(RuntimeException::class, '500');
});

it('only the foundation head can sign a batch', function () {
    $employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    Education::factory()->create(['employee_id' => $employee->id, 'is_highest' => true]);

    $batch = app(BatchDecreeService::class)->createBatch(
        [$employee->id],
        [
            'name' => 'Uji Otorisasi',
            'decree_type_id' => $this->type->id,
            'academic_year' => '2026/2027',
            'effective_date' => '2026-07-01',
            'issued_date' => '2026-07-15',
        ],
        $this->admin,
    );

    app(BatchDecreeService::class)->processBatch($batch, $this->admin);

    $this->actingAs($this->admin)
        ->post("/admin/batches/{$batch->id}/sign")
        ->assertForbidden();

    $this->actingAs($this->head)
        ->post("/admin/batches/{$batch->id}/sign")
        ->assertSessionHasNoErrors();
});

it('lets the batch be cancelled while awaiting signature', function () {
    $employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    Education::factory()->create(['employee_id' => $employee->id, 'is_highest' => true]);

    $batch = app(BatchDecreeService::class)->createBatch(
        [$employee->id],
        [
            'name' => 'Uji Batal',
            'decree_type_id' => $this->type->id,
            'academic_year' => '2026/2027',
            'effective_date' => '2026-07-01',
            'issued_date' => '2026-07-15',
        ],
        $this->admin,
    );

    app(BatchDecreeService::class)->processBatch($batch, $this->admin);

    $this->actingAs($this->admin)
        ->post("/admin/batches/{$batch->id}/cancel")
        ->assertSessionHasNoErrors();

    expect($batch->fresh()->status)->toBe(DecreeBatchStatus::Cancelled);
    expect(Decree::where('decree_batch_id', $batch->id)->first()->status)->toBe(DecreeStatus::Cancelled);
});
