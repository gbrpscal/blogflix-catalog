<?php

use App\DTOs\Tmdb\MovieData;

it('normalizes a TMDB payload into a movie DTO', function (): void {
    $movie = MovieData::fromArray([
        'id' => '550',
        'title' => '  Clube da Luta  ',
        'overview' => '',
        'poster_path' => '/poster.jpg',
        'release_date' => '1999-10-15',
        'genre_ids' => [18, '18', 53],
        'vote_average' => '8.4',
    ]);

    expect($movie)
        ->id->toBe(550)
        ->title->toBe('Clube da Luta')
        ->overview->toBeNull()
        ->posterPath->toBe('/poster.jpg')
        ->releaseDate->toBe('1999-10-15')
        ->genreIds->toBe([18, 53])
        ->voteAverage->toBe(8.4);
});

it('maps detail genres and rejects an invalid release date', function (): void {
    $movie = MovieData::fromArray([
        'id' => 1,
        'name' => 'Filme alternativo',
        'release_date' => '15/10/1999',
        'genres' => [['id' => 12], ['id' => 14]],
        'runtime' => 121,
    ]);

    expect($movie)
        ->title->toBe('Filme alternativo')
        ->releaseDate->toBeNull()
        ->genreIds->toBe([12, 14])
        ->runtime->toBe(121);
});
