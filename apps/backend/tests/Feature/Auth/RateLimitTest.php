<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('rate limits password reset email requests', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    foreach (range(1, 3) as $attempt) {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();
    }

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertStatus(429);
});
