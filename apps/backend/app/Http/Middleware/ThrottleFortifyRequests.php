<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

class ThrottleFortifyRequests
{
    public function __construct(private readonly ThrottleRequests $throttleRequests) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $limiter = match ($request->route()?->getName()) {
            'register.store' => 'registration',
            'password.email' => 'password-email',
            default => null,
        };

        if ($limiter === null) {
            return $next($request);
        }

        return $this->throttleRequests->handle($request, $next, $limiter);
    }
}
