<?php

namespace App\DTOs\Tmdb;

final readonly class MovieData
{
    /** @param list<int> $genreIds */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $overview,
        public ?string $posterPath,
        public ?string $backdropPath,
        public ?string $releaseDate,
        public array $genreIds,
        public ?float $voteAverage,
        public ?int $runtime = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $genreIds = isset($data['genre_ids']) && is_array($data['genre_ids'])
            ? $data['genre_ids']
            : array_column(is_array($data['genres'] ?? null) ? $data['genres'] : [], 'id');

        return new self(
            id: (int) ($data['id'] ?? 0),
            title: trim((string) ($data['title'] ?? $data['name'] ?? '')),
            overview: filled($data['overview'] ?? null) ? (string) $data['overview'] : null,
            posterPath: filled($data['poster_path'] ?? null) ? (string) $data['poster_path'] : null,
            backdropPath: filled($data['backdrop_path'] ?? null) ? (string) $data['backdrop_path'] : null,
            releaseDate: self::dateOrNull($data['release_date'] ?? null),
            genreIds: array_values(array_unique(array_map('intval', $genreIds))),
            voteAverage: is_numeric($data['vote_average'] ?? null) ? (float) $data['vote_average'] : null,
            runtime: is_numeric($data['runtime'] ?? null) ? (int) $data['runtime'] : null,
        );
    }

    private static function dateOrNull(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }
}
