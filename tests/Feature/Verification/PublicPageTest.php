<?php

use App\Enums\DecreeStatus;
use App\Models\Decree;
use App\Models\DecreeType;
use App\Models\Employee;
use App\Models\WorkUnit;

beforeEach(function () {
    $this->unit = WorkUnit::factory()->create(['code' => 'SMK']);
    $this->employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $this->type = DecreeType::factory()->create(['code' => 'SK-PPT']);

    $this->decree = Decree::factory()->create([
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
        'snapshot_data' => [
            'name' => 'Budi Santoso',
            'nigy' => $this->employee->nigy,
            'work_unit' => 'SMK Qomarul Hidayah',
            'position' => 'Guru Mata Pelajaran',
            'issued_date' => '10 Juli 2026',
            'chairman_name' => 'KH. Ahmad Dahlan, M.Pd.',
        ],
    ]);
});

it('serves the public verification page without login', function () {
    $this->get("/verifikasi/{$this->decree->uuid}")
        ->assertOk()
        ->assertSee('SK VALID')
        ->assertSee('001/SK-PPT/SMK/YPP-QH/VII/2026')
        ->assertSee('Budi Santoso')
        ->assertSee($this->employee->nigy);
});

it('returns 404 for an unknown uuid', function () {
    $this->get('/verifikasi/'.fake()->uuid())->assertNotFound();
});

it('does not leak sensitive personal data on the public page', function () {
    $this->employee->update([
        'nik' => '3503031234567890',
        'address' => 'Rahasia Alamat GTK',
        'phone' => '0812RAHASIA',
        'npwp' => '99.999.999.9-999.999',
    ]);

    $this->get("/verifikasi/{$this->decree->uuid}")
        ->assertOk()
        ->assertDontSee('3503031234567890')
        ->assertDontSee('Rahasia Alamat GTK')
        ->assertDontSee('0812RAHASIA')
        ->assertDontSee('99.999.999.9-999.999');
});

it('shows the cancelled status for cancelled decrees', function () {
    $this->decree->update([
        'status' => DecreeStatus::Cancelled,
        'cancellation_reason' => 'Data jabatan tidak sesuai',
    ]);

    $this->get("/verifikasi/{$this->decree->uuid}")
        ->assertOk()
        ->assertSee('DIBATALKAN')
        ->assertSee('Data jabatan tidak sesuai');
});

it('shows the replacement decree number for superseded decrees', function () {
    $replacement = Decree::factory()->create([
        'employee_id' => $this->employee->id,
        'work_unit_id' => $this->unit->id,
        'decree_type_id' => $this->type->id,
        'status' => DecreeStatus::Issued,
        'decree_number' => '002/SK-PPT/SMK/YPP-QH/VII/2026',
        'registration_number' => 'E-2',
    ]);

    $this->decree->update([
        'status' => DecreeStatus::Superseded,
        'replacement_decree_id' => $replacement->id,
    ]);

    $this->get("/verifikasi/{$this->decree->uuid}")
        ->assertOk()
        ->assertSee('DIGANTI')
        ->assertSee('002/SK-PPT/SMK/YPP-QH/VII/2026');
});

it('rate limits the public verification endpoint', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->get("/verifikasi/{$this->decree->uuid}")->assertOk();
    }

    $this->get("/verifikasi/{$this->decree->uuid}")->assertStatus(429);
});
