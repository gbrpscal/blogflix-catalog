<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageBase = rtrim((string) config('tmdb.image_base_url'), '/');

        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'title' => $this->title,
            'overview' => $this->overview,
            'poster_path' => $this->poster_path,
            'poster_url' => $this->poster_path ? $imageBase.'/w500'.$this->poster_path : null,
            'release_date' => $this->release_date?->format('Y-m-d'),
            'genre_ids' => $this->genre_ids,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
