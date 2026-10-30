<?php

namespace App\Http\Middleware;

use App\Queries\Vendors\VendorQuery;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveVendor
{
    public function __construct(
        private VendorQuery $vendors,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $requestedVendorId = $request->query('vendor_id') !== null
            ? (int) $request->query('vendor_id')
            : null;

        $activeVendor = $this->vendors->resolveActiveVendor($user, $requestedVendorId);

        if (! $activeVendor) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Active vendor account required to perform this action.',
                ], 403);
            }

            return redirect()->route('profile')
                ->with('warning', 'You need an active or approved vendor store to access vendor management features.');
        }

        if ($request->hasSession()) {
            $request->session()->put('active_vendor_id', $activeVendor->vendor_id);
        }

        // Attach active vendor instance and all owned active stores to request
        $request->attributes->set('active_vendor', $activeVendor);
        $request->attributes->set('owned_active_vendors', $this->vendors->activeForUser($user));

        return $next($request);
    }
}
