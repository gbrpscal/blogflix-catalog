<?php

namespace App\Actions\Favorites;

use App\Exceptions\FavoriteAlreadyExistsException;
use App\Models\Favorite;
use App\Models\User;
use App\Services\Tmdb\TmdbMovieService;
use Illuminate\Database\QueryException;

class AddFavorite
{
    public function __construct(private readonly TmdbMovieService $movies) {}

    public function handle(User $user, int $tmdbId): Favorite
    {
        if ($user->favorites()->where('tmdb_id', $tmdbId)->exists()) {
            throw new FavoriteAlreadyExistsException;
        }

        $movie = $this->movies->find($tmdbId);

        try {
            return $user->favorites()->create([
                'tmdb_id' => $movie->id,
                'title' => $movie->title,
                'overview' => $movie->overview,
                'poster_path' => $movie->posterPath,
                'release_date' => $movie->releaseDate,
                'genre_ids' => $movie->genreIds,
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23505') {
                throw new FavoriteAlreadyExistsException;
            }

            throw $exception;
        }
    }
}
