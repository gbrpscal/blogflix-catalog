<?php

return [
    'token' => env('TMDB_API_TOKEN'),
    'base_url' => env('TMDB_BASE_URL', 'https://api.themoviedb.org/3'),
    'image_base_url' => env('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p'),
    'language' => env('TMDB_LANGUAGE', 'pt-BR'),
    'region' => env('TMDB_REGION', 'BR'),
    'connect_timeout' => (int) env('TMDB_CONNECT_TIMEOUT', 3),
    'timeout' => (int) env('TMDB_TIMEOUT', 8),
    'cache' => [
        'search_ttl' => (int) env('TMDB_SEARCH_CACHE_TTL', 600),
        'movie_ttl' => (int) env('TMDB_MOVIE_CACHE_TTL', 3600),
        'genres_ttl' => (int) env('TMDB_GENRES_CACHE_TTL', 86400),
    ],
];
