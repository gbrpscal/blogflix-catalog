<?php

namespace App\Policies;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FavoritePolicy
{
    public function delete(User $user, Favorite $favorite): Response
    {
        return $user->is($favorite->user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
