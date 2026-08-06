<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Favorites\AddFavorite;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexFavoritesRequest;
use App\Http\Requests\Api\V1\StoreFavoriteRequest;
use App\Http\Resources\Api\V1\FavoriteResource;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FavoriteController extends Controller
{
    public function index(IndexFavoritesRequest $request): AnonymousResourceCollection
    {
        $favorites = $request->user()->favorites()
            ->when(
                $request->filled('genre_id'),
                fn ($query) => $query->whereJsonContains('genre_ids', (int) $request->validated('genre_id')),
            )
            ->latest()
            ->paginate((int) $request->validated('per_page', 12))
            ->withQueryString();

        return FavoriteResource::collection($favorites);
    }

    public function store(StoreFavoriteRequest $request, AddFavorite $action): JsonResponse
    {
        $favorite = $action->handle($request->user(), (int) $request->validated('tmdb_id'));

        return (new FavoriteResource($favorite))->response()->setStatusCode(201);
    }

    public function destroy(Favorite $favorite): Response
    {
        $this->authorize('delete', $favorite);
        $favorite->delete();

        return response()->noContent();
    }
}
