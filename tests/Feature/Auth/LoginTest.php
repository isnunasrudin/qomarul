<?php

use App\Models\User;

it('logs in a user with username and password', function () {
    $user = User::factory()->foundationAdmin()->create([
        'username' => 'admin.yayasan',
        'password' => 'secret12345',
        'must_change_password' => false,
    ]);

    $this->post('/login', [
        'login' => 'admin.yayasan',
        'password' => 'secret12345',
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('logs in with email instead of username', function () {
    $user = User::factory()->foundationAdmin()->create([
        'email' => 'admin@yayasan.test',
        'password' => 'secret12345',
        'must_change_password' => false,
    ]);

    $this->post('/login', [
        'login' => 'admin@yayasan.test',
        'password' => 'secret12345',
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    User::factory()->foundationAdmin()->create([
        'username' => 'admin.yayasan',
        'password' => 'secret12345',
    ]);

    $this->post('/login', [
        'login' => 'admin.yayasan',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('rejects inactive accounts', function () {
    User::factory()->foundationAdmin()->create([
        'username' => 'admin.nonaktif',
        'password' => 'secret12345',
        'is_active' => false,
    ]);

    $this->post('/login', [
        'login' => 'admin.nonaktif',
        'password' => 'secret12345',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('rate limits after five failed attempts per minute', function () {
    User::factory()->foundationAdmin()->create([
        'username' => 'admin.yayasan',
        'password' => 'secret12345',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'login' => 'admin.yayasan',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('login');
    }

    $this->post('/login', [
        'login' => 'admin.yayasan',
        'password' => 'secret12345',
    ])->assertStatus(429);
});

it('redirects users who must change password to the change page', function () {
    $user = User::factory()->foundationAdmin()->create([
        'username' => 'admin.baru',
        'password' => 'secret12345',
        'must_change_password' => true,
    ]);

    $this->post('/login', [
        'login' => 'admin.baru',
        'password' => 'secret12345',
    ])->assertRedirect('/password/change');

    $this->assertAuthenticatedAs($user);
});

it('logs the user out', function () {
    $user = User::factory()->foundationAdmin()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect('/login');

    $this->assertGuest();
});
