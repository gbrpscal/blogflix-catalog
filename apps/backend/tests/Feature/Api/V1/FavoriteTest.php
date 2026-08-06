<?php

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function fakeMovieDetails(int $id = 550): array
{
    return [
        'id' => $id,
        'title' => 'Clube da Luta',
        'overview' => 'Um trabalhador insone encontra um vendedor de sabonetes.',
        'poster_path' => '/poster.jpg',
        'backdrop_path' => '/backdrop.jpg',
        'release_date' => '1999-10-15',
        'genres' => [['id' => 18, 'name' => 'Drama'], ['id' => 53, 'name' => 'Thriller']],
        'vote_average' => 8.4,
        'runtime' => 139,
    ];
}

it('adds a TMDB movie to the authenticated user favorites', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    Http::fake(['api.themoviedb.org/3/movie/550*' => Http::response(fakeMovieDetails())]);

    $this->postJson('/api/v1/favorites', ['tmdb_id' => 550])
        ->assertCreated()
        ->assertJsonPath('data.tmdb_id', 550)
        ->assertJsonPath('data.title', 'Clube da Luta');

    $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'tmdb_id' => 550]);
});

it('prevents duplicate favorites for the same user', function (): void {
    $user = User::factory()->create();
    Favorite::factory()->for($user)->create(['tmdb_id' => 550]);
    $this->actingAs($user);
    Http::fake();

    $this->postJson('/api/v1/favorites', ['tmdb_id' => 550])
        ->assertConflict()
        ->assertJsonPath('code', 'favorite_already_exists');

    Http::assertNothingSent();
});

it('removes an owned favorite', function (): void {
    $user = User::factory()->create();
    $favorite = Favorite::factory()->for($user)->create();
    $this->actingAs($user);

    $this->deleteJson("/api/v1/favorites/{$favorite->id}")->assertNoContent();
    $this->assertDatabaseMissing('favorites', ['id' => $favorite->id]);
});

it('does not allow removing another user favorite', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $favorite = Favorite::factory()->for($owner)->create();
    $this->actingAs($attacker);

    $this->deleteJson("/api/v1/favorites/{$favorite->id}")->assertNotFound();
    $this->assertDatabaseHas('favorites', ['id' => $favorite->id]);
});

it('filters only the authenticated user favorites by genre', function (): void {
    $user = User::factory()->create();
    Favorite::factory()->for($user)->create(['title' => 'Ação', 'genre_ids' => [28, 53]]);
    Favorite::factory()->for($user)->create(['title' => 'Comédia', 'genre_ids' => [35]]);
    Favorite::factory()->for(User::factory())->create(['title' => 'Outro usuário', 'genre_ids' => [28]]);
    $this->actingAs($user);

    $this->getJson('/api/v1/favorites?genre_id=28')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Ação');
});

it('validates favorite and search endpoint inputs', function (): void {
    $this->actingAs(User::factory()->create());

    $this->postJson('/api/v1/favorites', [])->assertUnprocessable()->assertJsonValidationErrors('tmdb_id');
    $this->getJson('/api/v1/movies?query=x&page=0')->assertUnprocessable()->assertJsonValidationErrors(['query', 'page']);
    $this->getJson('/api/v1/favorites?per_page=100')->assertUnprocessable()->assertJsonValidationErrors('per_page');
});

it('requires authentication and a verified email', function (): void {
    $this->getJson('/api/v1/favorites')->assertUnauthorized();

    $this->actingAs(User::factory()->unverified()->create());
    $this->getJson('/api/v1/favorites')->assertForbidden();
});
