<?php

namespace App\DTOs\Tmdb;

final readonly class GenreData
{
    public function __construct(public int $id, public string $name) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self((int) ($data['id'] ?? 0), trim((string) ($data['name'] ?? '')));
    }
}
