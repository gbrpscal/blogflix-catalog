<?php

namespace App\Exceptions;

use RuntimeException;

class FavoriteAlreadyExistsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este filme já está nos seus favoritos.');
    }
}
