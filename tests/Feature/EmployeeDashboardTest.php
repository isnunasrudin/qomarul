<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('probe employee dashboard payload', function () {
    $employee = Employee::factory()->create();
    $user = User::factory()->create([
        'role' => UserRole::Employee,
        'employee_id' => $employee->id,
    ]);

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('employee.nigy', $employee->nigy)
            ->has('completeness')
            ->has('recentDecrees'));
});
