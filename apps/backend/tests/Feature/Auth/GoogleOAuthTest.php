<?php

use App\Models\User;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

it('returns a controlled response while Google OAuth is not configured', function (): void {
    config()->set('services.google', ['client_id' => null, 'client_secret' => null, 'redirect' => null]);

    $this->get('/api/v1/auth/google/redirect')->assertStatus(503);
});

it('creates and authenticates a verified Google user without storing tokens', function (): void {
    config()->set('services.google', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect' => 'http://localhost:8080/api/v1/auth/google/callback',
    ]);

    $googleUser = (new GoogleUser)->setRaw(['verified_email' => true])->map([
        'id' => 'google-123',
        'name' => 'Google User',
        'email' => 'GOOGLE@EXAMPLE.COM',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andReturn($googleUser);
    Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

    $this->get('/api/v1/auth/google/callback')->assertRedirect(config('app.frontend_url').'/movies');

    $user = User::query()->where('google_id', 'google-123')->firstOrFail();
    expect($user->email)->toBe('google@example.com')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->getAttributes())->not->toHaveKeys(['google_access_token', 'google_refresh_token']);
    $this->assertAuthenticatedAs($user);
});

it('safely links a verified Google identity to an existing email', function (): void {
    config()->set('services.google', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect' => 'http://localhost:8080/api/v1/auth/google/callback',
    ]);
    $existing = User::factory()->unverified()->create(['email' => 'existing@example.com']);
    $googleUser = (new GoogleUser)->setRaw(['verified_email' => true])->map([
        'id' => 'google-existing',
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'avatar' => null,
    ]);
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andReturn($googleUser);
    Socialite::shouldReceive('driver')->andReturn($provider);

    $this->get('/api/v1/auth/google/callback')->assertRedirect(config('app.frontend_url').'/movies');

    expect($existing->fresh()->google_id)->toBe('google-existing')
        ->and($existing->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and(User::query()->count())->toBe(1);
});
