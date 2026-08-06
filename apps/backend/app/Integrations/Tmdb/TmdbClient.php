<?php

namespace App\Integrations\Tmdb;

use App\Exceptions\TmdbException;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class TmdbClient
{
    /** @param array<string, int|string> $query */
    public function get(string $path, array $query = []): array
    {
        $token = (string) config('tmdb.token');

        if ($token === '') {
            throw TmdbException::notConfigured();
        }

        try {
            $response = Http::baseUrl((string) config('tmdb.base_url'))
                ->acceptJson()
                ->withToken($token)
                ->connectTimeout((int) config('tmdb.connect_timeout'))
                ->timeout((int) config('tmdb.timeout'))
                ->retry(2, 250, function (Exception $exception, PendingRequest $request): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    return $exception instanceof RequestException
                        && ($exception->response->serverError() || $exception->response->status() === 429);
                }, throw: false)
                ->get($path, $query);
        } catch (ConnectionException) {
            throw TmdbException::unavailable();
        }

        if ($response->status() === 404) {
            throw TmdbException::movieNotFound();
        }

        if ($response->status() === 429 || $response->serverError()) {
            throw TmdbException::unavailable();
        }

        if ($response->failed()) {
            throw new TmdbException('Não foi possível consultar o serviço de filmes.', 'tmdb_request_failed');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw TmdbException::invalidResponse();
        }

        return $payload;
    }
}
