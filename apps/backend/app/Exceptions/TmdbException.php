<?php

namespace App\Exceptions;

use RuntimeException;

class TmdbException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status = 502,
    ) {
        parent::__construct($message);
    }

    public static function notConfigured(): self
    {
        return new self('A integração com o TMDB ainda não foi configurada.', 'tmdb_not_configured', 503);
    }

    public static function unavailable(): self
    {
        return new self('O serviço de filmes está temporariamente indisponível.', 'tmdb_unavailable', 503);
    }

    public static function invalidResponse(): self
    {
        return new self('O serviço de filmes retornou uma resposta inválida.', 'tmdb_invalid_response');
    }

    public static function movieNotFound(): self
    {
        return new self('Filme não encontrado.', 'movie_not_found', 404);
    }
}
