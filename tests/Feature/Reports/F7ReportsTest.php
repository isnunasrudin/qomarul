<?php

use App\Enums\DecreeStatus;
use App\Models\AuditLog;
use App\Models\Decree;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use App\Notifications\DecreeStatusChanged;

it('lets foundation admin access every report page', function () {
    $admin = User::factory()->foundationAdmin()->create();

    foreach (['employees', 'decrees', 'duties', 'incomplete', 'retiring', 'never-login'] as $report) {
        $this->actingAs($admin)
            ->get("/admin/reports/{$report}")
            ->assertOk();
    }
});

it('lists employees who have never logged in', function () {
    User::factory()->employee(Employee::factory()->create()->id)->create(['last_login_at' => null]);
    User::factory()->employee(Employee::factory()->create()->id)->create(['last_login_at' => now()]);

    $this->actingAs(User::factory()->foundationAdmin()->create())
        ->get('/admin/reports/never-login')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Reports/Show')
            ->has('rows.data', 1));
});

it('exports a report to excel', function () {
    Employee::factory()->count(3)->create();

    $this->actingAs(User::factory()->foundationAdmin()->create())
        ->get('/admin/reports/employees/export')
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('exports a report to pdf', function () {
    Employee::factory()->count(2)->create();

    $this->actingAs(User::factory()->foundationAdmin()->create())
        ->get('/admin/reports/employees/export-pdf')
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('lists decree issuance per period', function () {
    Decree::factory()->create(['status' => DecreeStatus::Issued, 'issued_date' => '2026-07-05', 'decree_number' => '001', 'registration_number' => 'E-1']);
    Decree::factory()->create(['status' => DecreeStatus::Issued, 'issued_date' => '2026-08-01', 'decree_number' => '002', 'registration_number' => 'E-2']);
    Decree::factory()->create(['status' => DecreeStatus::Draft, 'issued_date' => '2026-08-02']);

    $this->actingAs(User::factory()->foundationAdmin()->create())
        ->get('/admin/reports/decrees')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Reports/Show')
            ->has('rows.data', 2));
});

it('lists incomplete profiles', function () {
    $employee = Employee::factory()->create(['is_active' => true]);

    $this->actingAs(User::factory()->foundationAdmin()->create())
        ->get('/admin/reports/incomplete')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Reports/Show')
            ->has('rows.data', 1)
            ->where('rows.data.0.nigy', $employee->nigy)
            ->where('rows.data.0.percentage', fn ($p) => $p < 100));
});

it('filters audit logs by action and exports them', function () {
    AuditLog::create(['action' => 'created', 'auditable_type' => Employee::class, 'auditable_id' => 1]);
    AuditLog::create(['action' => 'deleted', 'auditable_type' => Employee::class, 'auditable_id' => 2]);

    $this->actingAs(User::factory()->foundationAdmin()->create())
        ->get('/admin/audit-logs?action=created')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/AuditLogs/Index')
            ->has('logs.data', 1));

    $this->get('/admin/audit-logs/export')
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('denies audit log access to unit admins', function () {
    $this->actingAs(User::factory()->unitAdmin(WorkUnit::factory()->create()->id)->create())
        ->get('/admin/audit-logs')
        ->assertForbidden();
});

it('stores in-app notifications on decree status changes', function () {
    $admin = User::factory()->foundationAdmin()->create();
    $employee = Employee::factory()->create();
    $decree = Decree::factory()->create(['employee_id' => $employee->id, 'work_unit_id' => $employee->work_unit_id, 'status' => 'draft', 'created_by' => $admin->id]);

    $this->actingAs($admin)
        ->post("/admin/decrees/{$decree->id}/submit")
        ->assertSessionHasNoErrors();

    $foundationAdmins = User::query()->where('role', 'foundation_admin')->get();
    $foundationAdmins->each(function (User $user) {
        expect($user->unreadNotifications()->count())->toBeGreaterThanOrEqual(1);
    });

    $this->get('/admin/notifications')->assertOk();
});

it('marks notifications as read', function () {
    $admin = User::factory()->foundationAdmin()->create();
    $decree = Decree::factory()->create(['status' => 'issued']);
    $admin->notify(new DecreeStatusChanged($decree, 'issued', 'issued'));

    expect($admin->unreadNotifications()->count())->toBe(1);

    $this->actingAs($admin)
        ->post('/admin/notifications/read-all')
        ->assertRedirect();

    expect($admin->unreadNotifications()->count())->toBe(0);
});

it('updates last login timestamp on authentication', function () {
    $user = User::factory()->foundationAdmin()->create(['must_change_password' => false]);

    $this->post('/login', [
        'login' => $user->username,
        'password' => 'password',
    ])->assertRedirect();

    expect($user->fresh()->last_login_at)->not->toBeNull();
});
