<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchMoviesRequest;
use App\Http\Resources\Api\V1\MovieResource;
use App\Services\Tmdb\TmdbMovieService;
use Illuminate\Http\JsonResponse;

class MovieController extends Controller
{
    public function __construct(private readonly TmdbMovieService $movies) {}

    public function index(SearchMoviesRequest $request): JsonResponse
    {
        $result = $this->movies->search(
            (string) $request->validated('query'),
            (int) $request->validated('page', 1),
        );

        return response()->json([
            'data' => collect($result->movies)->map(fn ($movie): array => (new MovieResource($movie))->resolve($request))->all(),
            'meta' => [
                'current_page' => $result->page,
                'last_page' => $result->totalPages,
                'total' => $result->totalResults,
            ],
        ]);
    }

    public function show(int $tmdbId): MovieResource
    {
        return new MovieResource($this->movies->find($tmdbId));
    }
}
