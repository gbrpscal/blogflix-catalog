<?php

namespace App\Services\Tmdb;

use App\DTOs\Tmdb\GenreData;
use App\DTOs\Tmdb\MovieData;
use App\DTOs\Tmdb\MoviePageData;
use App\Exceptions\TmdbException;
use App\Integrations\Tmdb\TmdbClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TmdbMovieService
{
    public function __construct(private readonly TmdbClient $client) {}

    public function search(string $query, int $page = 1): MoviePageData
    {
        $query = $this->normalizeQuery($query);
        $language = (string) config('tmdb.language');
        $region = (string) config('tmdb.region');
        $key = 'tmdb:v1:search:'.hash('sha256', implode('|', [$query, $page, $language, $region]));

        return Cache::remember($key, (int) config('tmdb.cache.search_ttl'), function () use ($query, $page, $language, $region): MoviePageData {
            $payload = $this->client->get('/search/movie', [
                'query' => $query,
                'page' => $page,
                'language' => $language,
                'region' => $region,
                'include_adult' => 'false',
            ]);

            if (! is_array($payload['results'] ?? null)) {
                throw TmdbException::invalidResponse();
            }

            $movies = collect($payload['results'])
                ->filter(fn (mixed $movie): bool => is_array($movie))
                ->map(fn (array $movie): MovieData => MovieData::fromArray($movie))
                ->filter(fn (MovieData $movie): bool => $movie->id > 0 && $movie->title !== '')
                ->values()
                ->all();

            return new MoviePageData(
                movies: $movies,
                page: max(1, (int) ($payload['page'] ?? $page)),
                totalPages: min(500, max(0, (int) ($payload['total_pages'] ?? 0))),
                totalResults: max(0, (int) ($payload['total_results'] ?? 0)),
            );
        });
    }

    public function find(int $tmdbId): MovieData
    {
        $key = 'tmdb:v1:movie:'.hash('sha256', $tmdbId.'|'.config('tmdb.language'));

        return Cache::remember($key, (int) config('tmdb.cache.movie_ttl'), function () use ($tmdbId): MovieData {
            $payload = $this->client->get('/movie/'.$tmdbId, ['language' => (string) config('tmdb.language')]);
            $movie = MovieData::fromArray($payload);

            if ($movie->id <= 0 || $movie->title === '') {
                throw TmdbException::invalidResponse();
            }

            return $movie;
        });
    }

    /** @return list<GenreData> */
    public function genres(): array
    {
        return Cache::remember('tmdb:v1:genres:'.hash('sha256', (string) config('tmdb.language')), (int) config('tmdb.cache.genres_ttl'), function (): array {
            $payload = $this->client->get('/genre/movie/list', ['language' => (string) config('tmdb.language')]);

            if (! is_array($payload['genres'] ?? null)) {
                throw TmdbException::invalidResponse();
            }

            return collect($payload['genres'])
                ->filter(fn (mixed $genre): bool => is_array($genre))
                ->map(fn (array $genre): GenreData => GenreData::fromArray($genre))
                ->filter(fn (GenreData $genre): bool => $genre->id > 0 && $genre->name !== '')
                ->values()
                ->all();
        });
    }

    private function normalizeQuery(string $query): string
    {
        return Str::lower(trim((string) preg_replace('/\s+/u', ' ', $query)));
    }
}
