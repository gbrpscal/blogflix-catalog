<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MovieResource;
use App\Services\Tmdb\TmdbMovieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovieCollectionController extends Controller
{
    public function __construct(private readonly TmdbMovieService $movies) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = collect($this->movies->collections())
            ->map(fn (array $movies): array => collect($movies)
                ->map(fn ($movie): array => (new MovieResource($movie))->resolve($request))
                ->all())
            ->all();

        return response()->json(['data' => $data]);
    }
}
