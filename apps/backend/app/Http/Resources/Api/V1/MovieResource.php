<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageBase = rtrim((string) config('tmdb.image_base_url'), '/');

        return [
            'tmdb_id' => $this->id,
            'title' => $this->title,
            'overview' => $this->overview,
            'poster_path' => $this->posterPath,
            'poster_url' => $this->posterPath ? $imageBase.'/w500'.$this->posterPath : null,
            'backdrop_url' => $this->backdropPath ? $imageBase.'/w780'.$this->backdropPath : null,
            'release_date' => $this->releaseDate,
            'genre_ids' => $this->genreIds,
            'vote_average' => $this->voteAverage,
            'runtime' => $this->runtime,
        ];
    }
}
