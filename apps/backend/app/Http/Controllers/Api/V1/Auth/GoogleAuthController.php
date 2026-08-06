<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse|Response
    {
        if (! $this->isConfigured()) {
            return response(['message' => 'Login com Google ainda não configurado.', 'code' => 'google_not_configured'], 503);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');

        if (! $this->isConfigured()) {
            return redirect()->away($frontend.'/login?oauth_error=google_not_configured');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
            $user = $this->resolveUser($googleUser);

            Auth::guard('web')->login($user);
            request()->session()->regenerate();

            return redirect()->away($frontend.'/movies');
        } catch (Throwable) {
            return redirect()->away($frontend.'/login?oauth_error=google_failed');
        }
    }

    private function resolveUser(SocialiteUser $googleUser): User
    {
        $googleId = (string) $googleUser->getId();
        $email = Str::lower(trim((string) $googleUser->getEmail()));
        $isVerified = (bool) data_get($googleUser->user, 'verified_email', false);

        if ($googleId === '' || $email === '' || ! $isVerified) {
            throw new \RuntimeException('The Google identity is incomplete or unverified.');
        }

        return DB::transaction(function () use ($googleUser, $googleId, $email): User {
            $byGoogleId = User::query()->where('google_id', $googleId)->lockForUpdate()->first();

            if ($byGoogleId) {
                $byGoogleId->update([
                    'avatar_url' => $googleUser->getAvatar(),
                    'email_verified_at' => $byGoogleId->email_verified_at ?? now(),
                ]);

                return $byGoogleId;
            }

            $byEmail = User::query()->where('email', $email)->lockForUpdate()->first();

            if ($byEmail?->google_id) {
                throw new \RuntimeException('This email is linked to another Google identity.');
            }

            if ($byEmail) {
                $byEmail->update([
                    'google_id' => $googleId,
                    'avatar_url' => $googleUser->getAvatar(),
                    'email_verified_at' => $byEmail->email_verified_at ?? now(),
                ]);

                return $byEmail;
            }

            return User::create([
                'name' => trim((string) $googleUser->getName()) ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Str::random(64),
                'google_id' => $googleId,
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);
        });
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
