<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GenreResource;
use App\Services\Tmdb\TmdbMovieService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GenreController extends Controller
{
    public function __invoke(TmdbMovieService $movies): AnonymousResourceCollection
    {
        return GenreResource::collection($movies->genres());
    }
}
