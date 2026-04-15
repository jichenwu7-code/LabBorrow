<?php

namespace App\Http\Middleware;

use Closure;

class ApiSessionFix
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
        // 为 API 请求设置会话驱动为 array
        config(['session.driver' => 'array']);
        
        return $next($request);
    }
}