<?php

use App\Models\User;
use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

it('registers a user and queues email verification', function (): void {
    Notification::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Gabriel',
        'email' => 'GABRIEL@EXAMPLE.COM',
        'password' => 'a-secure-password',
        'password_confirmation' => 'a-secure-password',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'gabriel@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, QueuedVerifyEmail::class);
});

it('dispatches verification using the real queued notification path', function (): void {
    Queue::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Queue Test',
        'email' => 'queue@example.com',
        'password' => 'a-secure-password',
        'password_confirmation' => 'a-secure-password',
    ])->assertSuccessful();

    Queue::assertPushed(
        SendQueuedNotifications::class,
        fn (SendQueuedNotifications $job): bool => $job->queue === 'emails',
    );
});

it('logs in and logs out through the Fortify session guard', function (): void {
    $user = User::factory()->create(['password' => 'a-secure-password']);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'a-secure-password',
    ])->assertOk();

    $this->assertAuthenticatedAs($user);
    $this->postJson('/api/v1/auth/logout')->assertNoContent();
    $this->assertGuest();
});

it('verifies an email using a signed Fortify URL', function (): void {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(5), [
        'id' => $user->getKey(),
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(config('app.frontend_url').'/verify-email?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('resends email verification through a queued notification', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->postJson('/api/v1/auth/email/verification-notification')->assertSuccessful();
    Notification::assertSentTo($user, QueuedVerifyEmail::class);
});

it('sends and consumes a password reset token without real SMTP', function (): void {
    Notification::fake();
    $user = User::factory()->create();
    $token = null;

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

    Notification::assertSentTo($user, QueuedResetPassword::class, function (QueuedResetPassword $notification) use (&$token): bool {
        $token = $notification->token;

        return true;
    });

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'my-new-secure-password',
        'password_confirmation' => 'my-new-secure-password',
    ])->assertOk();

    expect(Hash::check('my-new-secure-password', $user->fresh()->password))->toBeTrue();
});

it('does not reveal whether a password reset email exists', function (): void {
    Notification::fake();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'missing@example.com'])->assertOk();
});
