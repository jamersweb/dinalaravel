<?php

namespace App\Http\Middleware;

use App\Models\UserDetail;
use App\Models\UserSub;
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
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $subStatus = UserDetail::where('user_id', $userId)->pluck('subscription_status')->first();
        if ($subStatus === 'active') {
            return $next($request);
        }

        // RevenueCat / legacy payments may create user_subs before user_details is updated
        $hasActiveUserSub = UserSub::where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
        if ($hasActiveUserSub) {
            return $next($request);
        }

        return response()->json([
            'message' => 'No Active Subscription.',
        ], 402);
    }
}
