<?php

namespace App\Services\Tmdb;

use App\DTOs\Tmdb\GenreData;
use App\DTOs\Tmdb\MovieData;
use App\DTOs\Tmdb\MoviePageData;
use App\Enums\MovieSort;
use App\Exceptions\TmdbException;
use App\Integrations\Tmdb\TmdbClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TmdbMovieService
{
    public function __construct(private readonly TmdbClient $client) {}

    public function catalog(
        ?string $query,
        int $page = 1,
        MovieSort $sort = MovieSort::Highlights,
        ?int $genreId = null,
    ): MoviePageData {
        return filled($query)
            ? $this->search((string) $query, $page, $sort, $genreId)
            : $this->browse($page, $sort, $genreId);
    }

    public function search(
        string $query,
        int $page = 1,
        MovieSort $sort = MovieSort::Highlights,
        ?int $genreId = null,
    ): MoviePageData {
        $query = $this->normalizeQuery($query);
        $language = (string) config('tmdb.language');
        $region = (string) config('tmdb.region');
        $key = 'tmdb:v1:search:'.hash('sha256', implode('|', [
            $query,
            $page,
            $language,
            $region,
            $sort->value,
            $genreId ?? 'all',
        ]));

        return Cache::remember($key, (int) config('tmdb.cache.search_ttl'), function () use ($query, $page, $language, $region, $sort, $genreId): MoviePageData {
            $payload = $this->client->get('/search/movie', [
                'query' => $query,
                'page' => $page,
                'language' => $language,
                'region' => $region,
                'include_adult' => 'false',
            ]);

            return $this->pageFromPayload($payload, $page, $sort, $genreId, true);
        });
    }

    public function browse(
        int $page = 1,
        MovieSort $sort = MovieSort::Highlights,
        ?int $genreId = null,
    ): MoviePageData {
        $params = array_filter([
            'page' => $page,
            'language' => (string) config('tmdb.language'),
            'region' => (string) config('tmdb.region'),
            'include_adult' => 'false',
            'include_video' => 'false',
            'sort_by' => $sort->tmdbValue(),
            'with_genres' => $genreId,
            'vote_count.gte' => $sort->minimumVoteCount(),
            'primary_release_date.lte' => $sort === MovieSort::Releases ? now()->toDateString() : null,
        ], fn (mixed $value): bool => $value !== null);

        $key = 'tmdb:v1:browse:'.hash('sha256', json_encode($params, JSON_THROW_ON_ERROR));

        return Cache::remember($key, (int) config('tmdb.cache.search_ttl'), function () use ($params, $page, $sort): MoviePageData {
            return $this->pageFromPayload(
                $this->client->get('/discover/movie', $params),
                $page,
                $sort,
            );
        });
    }

    /** @return array{popular: list<MovieData>, top_rated: list<MovieData>, releases: list<MovieData>, trending: list<MovieData>} */
    public function collections(): array
    {
        $language = (string) config('tmdb.language');
        $region = (string) config('tmdb.region');

        return [
            'popular' => $this->collection('popular', '/movie/popular', compact('language', 'region')),
            'top_rated' => $this->collection('top_rated', '/movie/top_rated', compact('language', 'region')),
            'releases' => $this->collection('releases', '/movie/now_playing', compact('language', 'region')),
            'trending' => $this->collection('trending', '/trending/movie/week', compact('language')),
        ];
    }

    /**
     * @param  array<string, int|string>  $params
     * @return list<MovieData>
     */
    private function collection(string $name, string $path, array $params): array
    {
        $params['page'] = 1;
        $key = 'tmdb:v1:collection:'.$name.':'.hash('sha256', json_encode($params, JSON_THROW_ON_ERROR));

        return Cache::remember($key, (int) config('tmdb.cache.collections_ttl'), function () use ($path, $params): array {
            $page = $this->pageFromPayload(
                $this->client->get($path, $params),
                1,
                MovieSort::Highlights,
            );

            return array_slice($page->movies, 0, 12);
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function pageFromPayload(
        array $payload,
        int $fallbackPage,
        MovieSort $sort,
        ?int $genreId = null,
        bool $sortLocally = false,
    ): MoviePageData {
        if (! is_array($payload['results'] ?? null)) {
            throw TmdbException::invalidResponse();
        }

        $movies = collect($payload['results'])
            ->filter(fn (mixed $movie): bool => is_array($movie))
            ->map(fn (array $movie): MovieData => MovieData::fromArray($movie))
            ->filter(fn (MovieData $movie): bool => $movie->id > 0 && $movie->title !== '')
            ->when(
                $genreId !== null,
                fn ($items) => $items->filter(
                    fn (MovieData $movie): bool => in_array($genreId, $movie->genreIds, true),
                ),
            )
            ->values()
            ->all();

        if ($sortLocally) {
            $movies = $this->sortMovies($movies, $sort);
        }

        return new MoviePageData(
            movies: $movies,
            page: max(1, (int) ($payload['page'] ?? $fallbackPage)),
            totalPages: min(500, max(0, (int) ($payload['total_pages'] ?? 0))),
            totalResults: max(0, (int) ($payload['total_results'] ?? 0)),
        );
    }

    /**
     * @param  list<MovieData>  $movies
     * @return list<MovieData>
     */
    private function sortMovies(array $movies, MovieSort $sort): array
    {
        $items = collect($movies);

        return match ($sort) {
            MovieSort::Releases => $items->sortByDesc(
                fn (MovieData $movie): string => $movie->releaseDate ?? '',
            )->values()->all(),
            MovieSort::Highlights => $items->sortByDesc(
                fn (MovieData $movie): float => $movie->voteAverage ?? -1,
            )->values()->all(),
            MovieSort::TitleAsc => $items->sortBy(
                fn (MovieData $movie): string => Str::ascii(Str::lower($movie->title)),
                SORT_NATURAL,
            )->values()->all(),
            MovieSort::TitleDesc => $items->sortByDesc(
                fn (MovieData $movie): string => Str::ascii(Str::lower($movie->title)),
                SORT_NATURAL,
            )->values()->all(),
        };
    }

    private function normalizeQuery(string $query): string
    {
        return Str::lower(trim((string) preg_replace('/\s+/u', ' ', $query)));
    }
}
