<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GuestOrAuth
{
    public function handle(Request $request, Closure $next)
    {

        if ($request->bearerToken())
            Auth::setUser(Auth::guard('sanctum')->user());

        return $next($request);
    }
}
