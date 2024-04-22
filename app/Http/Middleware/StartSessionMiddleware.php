<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Session;

class StartSessionMiddleware extends StartSession
{
    public function handle($request, Closure $next)
    {
        // If the request doesn't have a session, start a new one
        if (!$request->hasSession()) {
            $session = Session::driver();
            $request->setLaravelSession($session);
        }
        return $next($request);
    }
}
