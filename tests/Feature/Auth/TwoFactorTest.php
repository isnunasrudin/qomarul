<?php

use App\Enums\UserRole;
use App\Models\User;

it('allows enrolling and using totp via setup mandiri', function () {
    $user = User::factory()->foundationHead()->create([
        'username' => 'ketua',
        'password' => 'secret12345',
        'must_change_password' => false,
        'two_factor_enabled' => true,
    ]);

    // setup belum selesai → login tetap langsung
    $this->post('/login', ['login' => 'ketua', 'password' => 'secret12345'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->two_factor_active)->toBeFalse();

    // selesaikan setup dari halaman keamanan
    $this->get('/2fa/setup')->assertOk();

    $google2fa = app('pragmarx.google2fa');
    $secret = $google2fa->generateSecretKey();
    $code = $google2fa->getCurrentOtp($secret);

    $this->post('/2fa/confirm', ['secret' => $secret, 'code' => $code])
        ->assertRedirect('/');

    $user->refresh();
    expect($user->two_factor_secret)->toBe($secret);
    expect($user->two_factor_active)->toBeTrue();
});

it('requires the totp code on every subsequent login when 2fa aktif', function () {
    $google2fa = app('pragmarx.google2fa');
    $secret = $google2fa->generateSecretKey();

    $user = User::factory()->foundationHead()->create([
        'username' => 'ketua',
        'password' => 'secret12345',
        'two_factor_secret' => $secret,
        'two_factor_enabled' => true,
        'must_change_password' => false,
    ]);

    $this->post('/login', ['login' => 'ketua', 'password' => 'secret12345'])
        ->assertRedirect('/2fa/verify');

    $this->assertGuest();

    $this->post('/2fa/challenge', ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();

    $code = $google2fa->getCurrentOtp($secret);

    $this->post('/2fa/challenge', ['code' => $code])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('tidak meminta totp bila 2fa belum diaktifkan', function () {
    $user = User::factory()->foundationAdmin()->create([
        'username' => 'admin',
        'password' => 'secret12345',
    ]);

    $this->post('/login', ['login' => 'admin', 'password' => 'secret12345'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('login langsung bila 2fa diaktifkan namun setup belum selesai', function () {
    $user = User::factory()->create([
        'role' => UserRole::Employee,
        'two_factor_enabled' => true,
        'must_change_password' => false,
    ]);

    $this->post('/login', ['login' => $user->username, 'password' => 'password'])
        ->assertRedirect(route('portal.home'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->two_factor_active)->toBeFalse();
});

it('membutuhkan totp untuk peran non-ketua bila 2fa diaktifkan', function () {
    $google2fa = app('pragmarx.google2fa');
    $secret = $google2fa->generateSecretKey();

    $user = User::factory()->foundationAdmin()->create([
        'username' => 'admin',
        'password' => 'secret12345',
        'two_factor_secret' => $secret,
        'two_factor_enabled' => true,
        'must_change_password' => false,
    ]);

    $this->post('/login', ['login' => 'admin', 'password' => 'secret12345'])
        ->assertRedirect('/2fa/verify');

    $this->assertGuest();

    $code = $google2fa->getCurrentOtp($secret);

    $this->post('/2fa/challenge', ['code' => $code])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('bisa menonaktifkan 2fa sendiri', function () {
    $user = User::factory()->create([
        'role' => UserRole::FoundationAdmin,
        'two_factor_enabled' => true,
    ]);

    $this->actingAs($user)
        ->post('/2fa/disable')
        ->assertRedirect();

    $user->refresh();
    expect($user->two_factor_enabled)->toBeFalse();
    expect($user->two_factor_secret)->toBeNull();
});

it('admin dapat mengaktifkan & mereset 2fa pengguna lain', function () {
    $admin = User::factory()->foundationAdmin()->create();
    $target = User::factory()->create(['role' => UserRole::UnitAdmin]);

    $this->actingAs($admin)
        ->post("/admin/users/{$target->id}/toggle-2fa")
        ->assertRedirect();

    expect($target->fresh()->two_factor_enabled)->toBeTrue();

    $this->post("/admin/users/{$target->id}/reset-2fa")
        ->assertRedirect();

    $target->refresh();
    expect($target->two_factor_enabled)->toBeFalse();
    expect($target->two_factor_secret)->toBeNull();
});
