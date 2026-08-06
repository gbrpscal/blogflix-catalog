<?php

namespace Database\Factories;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Favorite> */
class FavoriteFactory extends Factory
{
    protected $model = Favorite::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tmdb_id' => fake()->unique()->numberBetween(1, 999999),
            'title' => fake()->sentence(3),
            'overview' => fake()->paragraph(),
            'poster_path' => '/'.fake()->uuid().'.jpg',
            'release_date' => fake()->date(),
            'genre_ids' => fake()->randomElements([12, 16, 18, 28, 35, 53], fake()->numberBetween(1, 3)),
        ];
    }
}
