<?php

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;

beforeEach(function () {
    $this->unit = WorkUnit::factory()->create();
    $this->employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
});

it('prevents reuse of a work unit code', function () {
    WorkUnit::factory()->create(['code' => 'SMK']);

    $this->actingAs(User::factory()->foundationAdmin()->create())
        ->post('/admin/work-units', [
            'code' => 'SMK',
            'name' => 'Duplikat',
            'level' => 'SMK',
        ])->assertSessionHasErrors('code');
});

it('records an audit trail when an employee is updated', function () {
    $admin = User::factory()->foundationAdmin()->create();

    $this->actingAs($admin);

    $this->employee->update(['name' => 'Nama Baru']);

    $log = AuditLog::query()
        ->where('auditable_type', Employee::class)
        ->where('auditable_id', $this->employee->id)
        ->where('action', 'updated')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($admin->id);
    expect($log->new_values['name'])->toBe('Nama Baru');
});

it('allows only foundation admin to reach settings page', function () {
    $this->actingAs(User::factory()->foundationHead()->create())
        ->get('/admin/settings')->assertStatus(403);

    $this->actingAs(User::factory()->foundationAdmin()->create())
        ->get('/admin/settings')->assertOk();
});

it('requires the password change flow before accessing dashboard', function () {
    $user = User::factory()->foundationAdmin()->create(['must_change_password' => true]);

    $this->actingAs($user)->get('/')->assertRedirect('/password/change');

    $this->post('/password/change', [
        'current_password' => 'password',
        'password' => 'Bajubaru123',
        'password_confirmation' => 'Bajubaru123',
    ])->assertRedirect('/');

    $user->refresh();
    expect($user->must_change_password)->toBeFalse();

    $this->get('/')->assertOk();
});
