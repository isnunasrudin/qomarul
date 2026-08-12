<?php

use App\Models\User;

it('allows foundation head to enroll and use totp after login', function () {
    $user = User::factory()->foundationHead()->create([
        'username' => 'ketua',
        'password' => 'secret12345',
        'must_change_password' => false,
    ]);

    $this->post('/login', ['login' => 'ketua', 'password' => 'secret12345'])
        ->assertRedirect('/2fa/setup');

    $this->assertGuest();
    expect(session('login.pre_2fa_id'))->toBe($user->id);

    $google2fa = app('pragmarx.google2fa');
    $secret = $google2fa->generateSecretKey();
    $code = $google2fa->getCurrentOtp($secret);

    $this->post('/2fa/confirm', ['secret' => $secret, 'code' => $code])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->two_factor_secret)->toBe($secret);
});

it('requires the totp code on every subsequent login', function () {
    $google2fa = app('pragmarx.google2fa');
    $secret = $google2fa->generateSecretKey();

    $user = User::factory()->foundationHead()->create([
        'username' => 'ketua',
        'password' => 'secret12345',
        'two_factor_secret' => $secret,
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

it('does not require totp for non-foundation-head roles', function () {
    $user = User::factory()->foundationAdmin()->create([
        'username' => 'admin',
        'password' => 'secret12345',
    ]);

    $this->post('/login', ['login' => 'admin', 'password' => 'secret12345'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});
