<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use Inertia\Testing\AssertableInertia as Assert;

it('operator yayasan dapat mengimpersonasi semua pengguna aktif', function () {
    $admin = User::factory()->foundationAdmin()->create();
    $employee = Employee::factory()->create();
    $target = User::factory()->create(['role' => UserRole::Employee, 'employee_id' => $employee->id]);

    $res = $this->actingAs($admin)->post("/admin/users/{$target->id}/impersonate");
    if ($res->status() !== 302) {
        echo 'DEBUG_STATUS: '.$res->status().' '.substr($res->getContent(), 0, 400);
    }
    $res->assertRedirect(route('portal.home'));

    $this->assertAuthenticatedAs($target);
    expect(session('impersonator_id'))->toBe($admin->id);

    // banner impersonasi ter-share
    $this->get(route('portal.home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('impersonation.active', true)
            ->where('impersonation.impersonator.id', $admin->id));
});

it('operator satker hanya dapat mengimpersonasi gtk pada satker sendiri', function () {
    $unit1 = WorkUnit::factory()->create(['code' => 'U1']);
    $unit2 = WorkUnit::factory()->create(['code' => 'U2']);

    $operator = User::factory()->create(['role' => UserRole::UnitAdmin, 'work_unit_id' => $unit1->id]);
    $ownGtk = User::factory()->create(['role' => UserRole::Employee, 'work_unit_id' => $unit1->id]);
    $otherGtk = User::factory()->create(['role' => UserRole::Employee, 'work_unit_id' => $unit2->id]);
    $otherAdmin = User::factory()->create(['role' => UserRole::FoundationAdmin]);

    expect($operator->can('impersonate', $ownGtk))->toBeTrue();
    expect($operator->can('impersonate', $otherGtk))->toBeFalse();
    expect($operator->can('impersonate', $otherAdmin))->toBeFalse();

    $this->actingAs($operator)
        ->post("/admin/users/{$otherGtk->id}/impersonate")
        ->assertForbidden();

    $this->actingAs($operator)
        ->post("/admin/users/{$ownGtk->id}/impersonate")
        ->assertRedirect(route('portal.home'));

    $this->assertAuthenticatedAs($ownGtk);
});

it('tidak dapat mengimpersonasi diri sendiri atau akun nonaktif', function () {
    $admin = User::factory()->foundationAdmin()->create();
    $inactive = User::factory()->create(['role' => UserRole::Employee, 'is_active' => false]);

    expect($admin->can('impersonate', $admin))->toBeFalse();
    expect($admin->can('impersonate', $inactive))->toBeFalse();
});

it('kembali ke akun operator saat menghentikan impersonasi', function () {
    $admin = User::factory()->foundationAdmin()->create();
    $target = User::factory()->create(['role' => UserRole::Employee]);

    $this->actingAs($admin)
        ->post("/admin/users/{$target->id}/impersonate")
        ->assertRedirect(route('portal.home'));

    $this->post(route('impersonate.stop'))
        ->assertRedirect(route('admin.users.index'));

    $this->assertAuthenticatedAs($admin);
    expect(session('impersonator_id'))->toBeNull();
});

it('memblokir pengaturan keamanan saat impersonasi berlangsung', function () {
    $admin = User::factory()->foundationAdmin()->create();
    $target = User::factory()->create(['role' => UserRole::Employee]);

    $this->actingAs($admin)
        ->post("/admin/users/{$target->id}/impersonate");

    $this->get(route('security'))->assertForbidden();
    $this->post(route('security.password'))->assertForbidden();
});

it('memuat flag can_impersonate pada daftar pengguna', function () {
    $admin = User::factory()->foundationAdmin()->create();
    User::factory()->create(['role' => UserRole::Employee]);
    User::factory()->create(['role' => UserRole::FoundationAdmin]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 3)
            ->has('users.data.0.can_impersonate'));
});

it('memuat flag can_impersonate pada daftar gtk untuk operator satker', function () {
    $unit = WorkUnit::factory()->create(['code' => 'SD1']);
    $operator = User::factory()->create(['role' => UserRole::UnitAdmin, 'work_unit_id' => $unit->id]);
    $employee = Employee::factory()->create(['work_unit_id' => $unit->id]);
    User::factory()->create(['role' => UserRole::Employee, 'employee_id' => $employee->id, 'work_unit_id' => $unit->id]);

    $this->actingAs($operator)
        ->get(route('admin.employees.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('employees.data', 1)
            ->where('employees.data.0.can_impersonate', true));
});
