<?php

namespace App\Http\Middleware;

use Closure;

class DisableSessionGlobally
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        config(['session.driver' => 'array']);
        
        if ($request->session()) {
            $request->session()->flush();
        }
        
        return $next($request);
    }
}
