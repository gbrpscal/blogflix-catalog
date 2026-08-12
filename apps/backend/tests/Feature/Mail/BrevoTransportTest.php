<?php

use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;

it('sends transactional email through the Brevo HTTPS API', function (): void {
    $request = null;
    $httpClient = new MockHttpClient(
        function (string $method, string $url, array $options) use (&$request): MockResponse {
            $request = [
                'method' => $method,
                'url' => $url,
                'options' => $options,
            ];

            return new MockResponse(
                json_encode(['messageId' => 'test-message-id'], JSON_THROW_ON_ERROR),
                [
                    'http_code' => 201,
                    'response_headers' => ['content-type: application/json'],
                ],
            );
        },
    );

    $this->app->instance(
        BrevoTransportFactory::class,
        new BrevoTransportFactory(client: $httpClient),
    );

    config()->set([
        'mail.from.address' => 'sender@example.com',
        'mail.from.name' => 'Blogflix',
        'mail.mailers.brevo' => [
            'transport' => 'brevo',
            'key' => 'test-brevo-api-key',
            'timeout' => 10,
        ],
    ]);

    Mail::purge('brevo');
    Mail::mailer('brevo')->raw('Mensagem de teste', function ($message): void {
        $message
            ->to('recipient@example.com', 'Recipient')
            ->subject('Teste Brevo');
    });

    expect($request)->not->toBeNull()
        ->and($request['method'])->toBe('POST')
        ->and($request['url'])->toBe('https://api.brevo.com/v3/smtp/email');

    $headers = implode("\n", $request['options']['headers'] ?? []);
    $payload = json_decode((string) $request['options']['body'], true, flags: JSON_THROW_ON_ERROR);

    expect($headers)->toContain('api-key: test-brevo-api-key')
        ->and($payload['sender']['email'])->toBe('sender@example.com')
        ->and($payload['to'][0]['email'])->toBe('recipient@example.com')
        ->and($payload['subject'])->toBe('Teste Brevo')
        ->and($payload['textContent'])->toContain('Mensagem de teste');
});

it('refuses to create the Brevo mailer without an API key', function (): void {
    config()->set('mail.mailers.brevo', [
        'transport' => 'brevo',
        'key' => '',
        'timeout' => 10,
    ]);

    Mail::purge('brevo');

    expect(fn () => Mail::mailer('brevo'))
        ->toThrow(InvalidArgumentException::class, 'BREVO_API_KEY must be configured');
});
