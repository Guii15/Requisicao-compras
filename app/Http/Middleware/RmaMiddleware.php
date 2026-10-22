<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RmaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !($user->isRma() || $user->isAdmin())) {
            abort(403, 'Acesso restrito.');
        }

        return $next($request);
    }
}
