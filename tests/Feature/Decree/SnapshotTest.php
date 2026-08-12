<?php

use App\Enums\DecreeStatus;
use App\Models\Decree;
use App\Models\DecreeType;
use App\Models\Education;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Decree\DecreeSnapshotBuilder;

beforeEach(function () {
    $this->unit = WorkUnit::factory()->create(['code' => 'SMK']);
    $this->employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $this->type = DecreeType::factory()->create(['code' => 'SK-PPT']);
    $this->admin = User::factory()->foundationAdmin()->create();
});

it('freezes the printed values into snapshot_data at issue time', function () {
    $this->employee->update([
        'title_prefix' => 'Drs.',
        'name' => 'Ahmad Fauzi',
        'title_suffix' => 'M.Pd.',
        'foundation_start_date' => '2020-07-01',
    ]);

    Education::factory()->create([
        'employee_id' => $this->employee->id,
        'level' => 'S2',
        'major' => 'Manajemen Pendidikan',
        'is_highest' => true,
    ]);

    $decree = Decree::factory()->create([
        'employee_id' => $this->employee->id,
        'work_unit_id' => $this->unit->id,
        'decree_type_id' => $this->type->id,
        'status' => DecreeStatus::Verified,
        'effective_date' => '2026-07-01',
        'issued_date' => '2026-07-10',
        'academic_year' => '2026/2027',
        'appointed_as' => 'Guru Mata Pelajaran',
        'decree_number' => '001/SK-PPT/SMK/YPP-QH/VII/2026',
        'registration_number' => 'E-1',
    ]);

    $snapshot = app(DecreeSnapshotBuilder::class)->freeze($decree);

    expect($snapshot->snapshot_data['name'])->toBe('Drs. Ahmad Fauzi M.Pd.');
    expect($snapshot->snapshot_data['education_level'])->toBe('S2');
    expect($snapshot->snapshot_data['major'])->toBe('Manajemen Pendidikan');
    expect($snapshot->snapshot_data['service_years'])->toBe(6);
    expect($snapshot->snapshot_data['position'])->toBe($decree->position_snapshot);
    expect($snapshot->snapshot_data['decree_number'])->toBe('001/SK-PPT/SMK/YPP-QH/VII/2026');
});

it('keeps the snapshot unchanged when the employee data changes afterwards', function () {
    $decree = Decree::factory()->create([
        'employee_id' => $this->employee->id,
        'work_unit_id' => $this->unit->id,
        'decree_type_id' => $this->type->id,
        'status' => DecreeStatus::Issued,
        'effective_date' => '2026-07-01',
        'issued_date' => '2026-07-10',
        'academic_year' => '2026/2027',
        'appointed_as' => 'Guru',
        'decree_number' => '001/SK-PPT/SMK/YPP-QH/VII/2026',
        'registration_number' => 'E-1',
    ]);

    $snapshot = app(DecreeSnapshotBuilder::class)->freeze($decree);
    $originalName = $snapshot->snapshot_data['name'];

    // data GTK berubah setelah SK terbit
    $this->employee->update([
        'name' => 'Nama Berubah Total',
        'title_prefix' => 'Prof.',
        'foundation_start_date' => '2015-01-01',
    ]);

    $still = $decree->fresh()->snapshot_data;

    expect($still['name'])->toBe($originalName);
    expect($still['name'])->not->toBe('Prof. Nama Berubah Total');
    expect($still['service_years'])->toBe($snapshot->snapshot_data['service_years']);
});
