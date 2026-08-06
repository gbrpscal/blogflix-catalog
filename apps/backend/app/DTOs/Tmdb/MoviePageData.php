<?php

namespace App\DTOs\Tmdb;

final readonly class MoviePageData
{
    /** @param list<MovieData> $movies */
    public function __construct(
        public array $movies,
        public int $page,
        public int $totalPages,
        public int $totalResults,
    ) {}
}
