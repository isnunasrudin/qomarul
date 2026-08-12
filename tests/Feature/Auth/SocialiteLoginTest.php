<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

it('mengarahkan ke Google saat tombol login ditekan', function () {
    config()->set('services.google', [
        'client_id' => 'google-client-id',
        'client_secret' => 'google-client-secret',
        'redirect' => 'http://localhost/auth/google/callback',
    ]);

    $response = $this->get(route('auth.google'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://accounts.google.com/o/oauth2/auth?');
});

it('login otomatis saat email cocok dengan akun terdaftar', function () {
    $user = User::factory()->create([
        'email' => 'fauzi@gmail.com',
        'role' => UserRole::FoundationAdmin,
        'must_change_password' => false,
    ]);

    Socialite::shouldReceive('driver->user')
        ->andReturn(new FakeGoogleUser('fauzi@gmail.com'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('mengarahkan GTK ke portal saat email cocok', function () {
    $user = User::factory()->create([
        'email' => 'guru@gmail.com',
        'role' => UserRole::Employee,
        'must_change_password' => false,
    ]);

    Socialite::shouldReceive('driver->user')
        ->andReturn(new FakeGoogleUser('guru@gmail.com'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('portal.home'));

    $this->assertAuthenticatedAs($user);
});

it('menolak login bila email tidak terdaftar', function () {
    Socialite::shouldReceive('driver->user')
        ->andReturn(new FakeGoogleUser('orang-lain@gmail.com'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('menolak login bila akun dinonaktifkan', function () {
    User::factory()->create([
        'email' => 'off@gmail.com',
        'is_active' => false,
    ]);

    Socialite::shouldReceive('driver->user')
        ->andReturn(new FakeGoogleUser('off@gmail.com'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('mengarahkan ke verifikasi 2FA bila 2FA aktif', function () {
    $user = User::factory()->create([
        'email' => 'ketua@gmail.com',
        'role' => UserRole::FoundationHead,
        'two_factor_secret' => 'ABC123',
        'two_factor_enabled' => true,
    ]);

    Socialite::shouldReceive('driver->user')
        ->andReturn(new FakeGoogleUser('ketua@gmail.com'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('two-factor.verify'));

    $this->assertGuest();
    $this->assertSame($user->id, session('login.pre_2fa_id'));
});

final class FakeGoogleUser
{
    public function __construct(private readonly string $email) {}

    public function getEmail(): string
    {
        return $this->email;
    }
}

it('menampilkan pesan saat google belum dikonfigurasi', function () {
    config()->set('services.google.client_id', null);

    $this->get(route('auth.google'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('login');
});
