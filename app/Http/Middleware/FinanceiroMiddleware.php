<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinanceiroMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->podeVerFinanceiro()) {
            abort(403, 'Acesso restrito.');
        }

        return $next($request);
    }
}
