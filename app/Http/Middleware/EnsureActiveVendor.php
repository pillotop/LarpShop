<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveVendor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $activeVendor = $user->activeVendor();

        if (! $activeVendor) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Active vendor account required to perform this action.',
                ], 403);
            }

            return redirect()->route('profile')
                ->with('warning', 'You need an active or approved vendor store to access vendor management features.');
        }

        // Attach active vendor instance to request
        $request->attributes->set('active_vendor', $activeVendor);

        return $next($request);
    }
}
