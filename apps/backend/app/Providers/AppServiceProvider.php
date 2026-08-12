<?php

namespace App\Providers;

use App\Http\Responses\Fortify\PasswordResetLinkResponse;
use App\Http\Responses\Fortify\VerifyEmailResponse;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
        $this->app->singleton(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
        $this->app->singleton(SuccessfulPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);

        $this->app->singleton(BrevoTransportFactory::class, fn (): BrevoTransportFactory => new BrevoTransportFactory(
            client: HttpClient::create([
                'timeout' => (float) config('mail.mailers.brevo.timeout', 10),
            ]),
        ));
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        $this->app->make(MailManager::class)->extend('brevo', function (array $config) {
            $apiKey = trim((string) ($config['key'] ?? ''));

            if ($apiKey === '') {
                throw new InvalidArgumentException('BREVO_API_KEY must be configured when MAIL_MAILER=brevo.');
            }

            return $this->app->make(BrevoTransportFactory::class)->create(
                new Dsn('brevo+api', 'default', $apiKey),
            );
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return rtrim((string) config('app.frontend_url'), '/')
                .'/reset-password?token='.urlencode($token)
                .'&email='.urlencode((string) $notifiable->getEmailForPasswordReset());
        });
    }
}
