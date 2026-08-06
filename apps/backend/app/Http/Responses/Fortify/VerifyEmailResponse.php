<?php

namespace App\Http\Responses\Fortify;

use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->away(rtrim((string) config('app.frontend_url'), '/').'/verify-email?verified=1');
    }
}
