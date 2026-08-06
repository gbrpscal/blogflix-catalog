<?php

use App\Exceptions\TmdbException;
use App\Services\Tmdb\TmdbMovieService;
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
