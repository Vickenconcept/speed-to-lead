<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessIsReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $business = $request->user()?->business;

        if ($business === null || ! $business->isReady()) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
