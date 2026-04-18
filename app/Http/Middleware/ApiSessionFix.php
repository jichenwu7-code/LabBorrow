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
        // 完全禁用会话中间件，避免SQLite连接问题
        $request->session()->flush();
        
        return $next($request);
    }
}