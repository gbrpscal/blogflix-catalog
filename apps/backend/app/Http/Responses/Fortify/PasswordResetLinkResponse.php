<?php

namespace App\Http\Responses\Fortify;

use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    public function __construct(private readonly string $status = '') {}

    public function toResponse($request): Response
    {
        return response()->json([
            'message' => 'Se o endereço estiver cadastrado, enviaremos um link de recuperação.',
        ]);
    }
}
