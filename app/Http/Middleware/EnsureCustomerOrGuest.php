<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Booking is open to guests (DEC-003) and logged-in customers, but never
 * to an authenticated staff/admin account - they have their own walk-in
 * flow under /manage/walk-in.
 */
class EnsureCustomerOrGuest
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isCustomer()) {
            abort(403);
        }

        return $next($request);
    }
}
