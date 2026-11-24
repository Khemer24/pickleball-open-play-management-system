<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManageOpenPlays
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isManager(), 403, 'Manager approval is required to manage legacy open plays.');

        return $next($request);
    }
}
