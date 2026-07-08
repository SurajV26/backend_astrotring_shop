<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CheckUserSessionTimeout
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user) {

            if (
                $user->last_seen_at &&
                Carbon::parse($user->last_seen_at)
                    ->diffInMinutes(now()) >= 120
            ) {

                $user->is_online = 0;
                $user->last_seen_at = now();
                $user->save();

                if ($request->user()?->currentAccessToken()) {
                    $request->user()->currentAccessToken()->delete();
                }

                return response()->json([
                    'status' => false,
                    'message' => 'Session expired. Please login again.',
                ], 401);
            }

            $user->update([
                'last_seen_at' => now(),
            ]);
        }

        return $next($request);
    }
}