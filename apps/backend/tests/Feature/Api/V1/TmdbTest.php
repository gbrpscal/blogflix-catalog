<?php

use App\Enums\MovieSort;
use App\Exceptions\TmdbException;
use App\Models\User;
use App\Services\Tmdb\TmdbMovieService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('caches identical TMDB searches', function (): void {
    Cache::clear();
    Http::fake([
        'api.themoviedb.org/3/search/movie*' => Http::response([
            'page' => 1,
            'total_pages' => 1,
            'total_results' => 1,
            'results' => [fakeMovieDetails()],
        ]),
    ]);

    $service = app(TmdbMovieService::class);
    $first = $service->search('  Clube   da Luta  ', 1);
    $second = $service->search('clube da luta', 1);

    expect($first->movies)->toHaveCount(1)
        ->and($second->movies[0]->id)->toBe(550);
    Http::assertSentCount(1);
});

it('broadens partial searches across adjacent TMDB pages while preserving pagination', function (): void {
    Cache::clear();
    $firstPage = collect(range(1, 20))
        ->map(fn (int $id): array => array_merge(fakeMovieDetails($id), [
            'title' => "Resultado {$id}",
            'vote_average' => 1.0,
        ]))
        ->all();
    $secondPage = collect(range(21, 40))
        ->map(fn (int $id): array => array_merge(fakeMovieDetails($id), [
            'title' => "Outro resultado {$id}",
            'vote_average' => 1.0,
        ]))
        ->all();
    $secondPage[0] = array_merge(fakeMovieDetails(21), [
        'title' => 'Minha Mãe é uma Peça: O Filme',
        'vote_average' => 8.0,
    ]);

    Http::fake(function (Request $request) use ($firstPage, $secondPage) {
        $page = (int) $request['page'];

        return Http::response([
            'page' => $page,
            'total_pages' => 2,
            'total_results' => 40,
            'results' => $page === 1 ? $firstPage : $secondPage,
        ]);
    });

    $service = app(TmdbMovieService::class);
    $first = $service->search('Minha mãe', 1);
    $second = $service->search('Minha mãe', 2);

    expect($first->movies)->toHaveCount(20)
        ->and($first->movies[0]->title)->toBe('Minha Mãe é uma Peça: O Filme')
        ->and($second->movies)->toHaveCount(20)
        ->and(collect($first->movies)->merge($second->movies)->pluck('id')->unique())->toHaveCount(40);
    Http::assertSentCount(2);
});

it('caches the TMDB genre list', function (): void {
    Cache::clear();
    Http::fake(['api.themoviedb.org/3/genre/movie/list*' => Http::response(['genres' => [['id' => 18, 'name' => 'Drama']]])]);

    $service = app(TmdbMovieService::class);
    expect($service->genres())->toHaveCount(1)
        ->and($service->genres()[0]->name)->toBe('Drama');
    Http::assertSentCount(1);
});

it('maps TMDB server failures to a safe domain exception', function (): void {
    Http::fake(['api.themoviedb.org/3/*' => Http::response([], 500)]);

    expect(fn () => app(TmdbMovieService::class)->search('matrix'))
        ->toThrow(TmdbException::class, 'temporariamente indisponível');
});

it('rejects invalid TMDB payloads without caching them', function (): void {
    Cache::clear();
    Http::fake(['api.themoviedb.org/3/search/movie*' => Http::response(['unexpected' => true])]);

    expect(fn () => app(TmdbMovieService::class)->search('matrix'))
        ->toThrow(TmdbException::class, 'resposta inválida');
});

it('loads the initial catalog through discover with native sort and genre filters', function (): void {
    Cache::clear();
    Http::fake([
        'api.themoviedb.org/3/discover/movie*' => Http::response([
            'page' => 2,
            'total_pages' => 3,
            'total_results' => 42,
            'results' => [fakeMovieDetails()],
        ]),
    ]);

    $result = app(TmdbMovieService::class)->browse(2, MovieSort::TitleAsc, 18);

    expect($result->movies)->toHaveCount(1)
        ->and($result->page)->toBe(2)
        ->and($result->totalResults)->toBe(42);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/discover/movie')
        && $request['sort_by'] === 'original_title.asc'
        && $request['with_genres'] === 18
        && $request['include_adult'] === 'false');
});

it('filters and sorts title search results on the returned page', function (): void {
    Cache::clear();
    Http::fake([
        'api.themoviedb.org/3/search/movie*' => Http::response([
            'page' => 1,
            'total_pages' => 1,
            'total_results' => 3,
            'results' => [
                array_merge(fakeMovieDetails(1), ['title' => 'Zeta', 'genre_ids' => [18]]),
                array_merge(fakeMovieDetails(2), ['title' => 'Comedia', 'genre_ids' => [35]]),
                array_merge(fakeMovieDetails(3), ['title' => 'Alpha', 'genre_ids' => [18]]),
            ],
        ]),
    ]);

    $result = app(TmdbMovieService::class)->search(
        'filme',
        1,
        MovieSort::TitleAsc,
        18,
    );

    expect(collect($result->movies)->pluck('title')->all())->toBe(['Alpha', 'Zeta']);
});

it('caches each home collection independently', function (): void {
    Cache::clear();
    $payload = [
        'page' => 1,
        'total_pages' => 1,
        'total_results' => 1,
        'results' => [fakeMovieDetails()],
    ];
    Http::fake([
        'api.themoviedb.org/3/movie/popular*' => Http::response($payload),
        'api.themoviedb.org/3/movie/top_rated*' => Http::response($payload),
        'api.themoviedb.org/3/movie/now_playing*' => Http::response($payload),
        'api.themoviedb.org/3/trending/movie/week*' => Http::response($payload),
    ]);

    $service = app(TmdbMovieService::class);
    $first = $service->collections();
    $second = $service->collections();

    expect($first)->toHaveKeys(['popular', 'top_rated', 'releases', 'trending'])
        ->and($first['popular'])->toHaveCount(1)
        ->and($second['trending'])->toHaveCount(1);
    Http::assertSentCount(4);
});

it('serves a paginated initial catalog without requiring a search query', function (): void {
    $this->actingAs(User::factory()->create());
    Http::fake([
        'api.themoviedb.org/3/discover/movie*' => Http::response([
            'page' => 1,
            'total_pages' => 5,
            'total_results' => 100,
            'results' => [fakeMovieDetails()],
        ]),
    ]);

    $this->getJson('/api/v1/movies?sort=highlights&genre_id=18')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.last_page', 5)
        ->assertJsonPath('data.0.tmdb_id', 550);
});

it('validates catalog sort and genre filters', function (): void {
    $this->actingAs(User::factory()->create());

    $this->getJson('/api/v1/movies?sort=unknown&genre_id=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sort', 'genre_id']);
});
