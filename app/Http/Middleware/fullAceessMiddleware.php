<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Models\UserSub;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class fullAceessMiddleware
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
        $access = Subscription::where('id',UserSub::where('user_id',Auth::id())->where('status','active')->orderBy('id','desc')->pluck('sub_id')->first())->pluck('access_type')->first();
        if($access==='full_access')
        return $next($request);
        else
        return response()->json([
            'status' => false,
            'message' => 'Subscription Upgrade Required'
        ],412);
    }
}
