<?php

namespace App\Http\Middleware;

use App\Models\UserDetail;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoActiveSubMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $subStatus = UserDetail::where('user_id',Auth::id())->pluck('subscription_status')->first();
        if($subStatus==='active')
        return $next($request);
        else 
        return response()->json([
            'message' => 'No Active Subscription.'
        ],402);
    }
}
