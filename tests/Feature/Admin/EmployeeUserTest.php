<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Employee\EmployeeImportService;
use Illuminate\Support\Facades\Hash;

it('operator yayasan dapat membuatkan akun pengguna untuk gtk', function () {
    $admin = User::factory()->foundationAdmin()->create();
    $employee = Employee::factory()->create([
        'nigy' => '2026SD1001',
        'email' => 'fauzi@qomarulhidayah.sch.id',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.employees.user.create', $employee), [
            'username' => $employee->nigy,
            'email' => $employee->email,
            'password' => '',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $user = User::where('username', '2026SD1001')->first();

    expect($user)->not->toBeNull();
    expect($user->employee_id)->toBe($employee->id);
    expect($user->role)->toBe(UserRole::Employee);
    expect($user->must_change_password)->toBeTrue();
    expect($user->password)->not->toBeNull();

    // sandi acak tersimpan sebagai hash dan bisa diverifikasi
    expect(Hash::check('salah', $user->password))->toBeFalse();
});

it('menolak pembuatan akun bila gtk sudah punya akun', function () {
    $admin = User::factory()->foundationAdmin()->create();
    $employee = Employee::factory()->create();
    User::factory()->create(['role' => UserRole::Employee, 'employee_id' => $employee->id]);

    $this->actingAs($admin)
        ->post(route('admin.employees.user.create', $employee), [
            'username' => 'u1',
            'email' => 'u1@qomarulhidayah.sch.id',
        ])
        ->assertForbidden();
});

it('operator satker hanya bisa membuat akun untuk gtk satker sendiri', function () {
    $unit = WorkUnit::factory()->create(['code' => 'SD1']);
    $operator = User::factory()->create(['role' => UserRole::UnitAdmin, 'work_unit_id' => $unit->id]);
    $own = Employee::factory()->create(['work_unit_id' => $unit->id, 'email' => 'a@qomarulhidayah.sch.id']);
    $otherUnit = WorkUnit::factory()->create(['code' => 'SMP']);
    $other = Employee::factory()->create(['work_unit_id' => $otherUnit->id, 'email' => 'b@qomarulhidayah.sch.id']);

    $this->actingAs($operator)
        ->post(route('admin.employees.user.create', $other), ['username' => 'b', 'email' => 'b@qomarulhidayah.sch.id'])
        ->assertNotFound();

    $this->actingAs($operator)
        ->post(route('admin.employees.user.create', $own), ['username' => 'a', 'email' => 'a@qomarulhidayah.sch.id'])
        ->assertRedirect();

    expect(User::where('username', 'a')->exists())->toBeTrue();
});

it('impor dengan opsi akun otomatis membuatkan pengguna', function () {
    $unit = WorkUnit::where('code', 'SD1')->first() ?? WorkUnit::factory()->create(['code' => 'SD1']);
    $position = Position::factory()->create(['code' => 'GURU-X']);
    $status = EmploymentStatus::factory()->create(['code' => 'TETAP-X']);

    $rows = [[
        'nik' => '3503031234567890', 'nuptk' => '', 'nip' => '', 'gelar_depan' => '',
        'nama' => 'GTK Impor Akun', 'gelar_belakang' => '', 'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Trenggalek', 'tanggal_lahir' => '1990-01-01', 'agama' => 'Islam',
        'status_pernikahan' => '', 'alamat' => '', 'telepon' => '', 'email' => '',
        'kode_satker' => $unit->code, 'kode_jabatan' => $position->code,
        'kode_status' => $status->code, 'tmt_yayasan' => '2026-08-01', 'tmt_satker' => '', 'mapel' => '',
    ]];

    $service = app(EmployeeImportService::class);
    $preview = $service->preview($rows);
    expect($preview['valid'])->toHaveCount(1);

    $result = $service->import($preview['valid'], true);

    expect($result['saved'])->toBe(1);
    expect($result['users'])->toHaveCount(1);

    $employee = Employee::where('name', 'GTK Impor Akun')->first();
    $user = User::where('username', $employee->nigy)->first();

    expect($user)->not->toBeNull();
    expect($user->email)->toBe($employee->nigy.'@qomarulhidayah.sch.id');
    expect($user->must_change_password)->toBeTrue();
});
