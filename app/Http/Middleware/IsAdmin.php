<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->expectsJson() & auth()->user()->isAdmin()) {
            return $next($request);
        }
    
        return redirect('/user/profile')->with('error',"You don't have admin access.");
    }
}
