<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStaff
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $staff = $this->getAuthenticatedStaff($request);

        if (!$staff) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('home');
        }

        // Refresh token expiration on each visit
        $staff->refreshTokenExpiration();

        // Share staff with all views
        $request->attributes->set('staff', $staff);

        return $next($request);
    }

    /**
     * Get the authenticated staff member from cookie.
     */
    protected function getAuthenticatedStaff(Request $request): ?Staff
    {
        $token = $request->cookie('staff_token');

        if (!$token) {
            return null;
        }

        $staff = Staff::where('auth_token', $token)
            ->where(function ($query) {
                $query->whereNull('token_expires_at')
                    ->orWhere('token_expires_at', '>', now());
            })
            ->first();

        return $staff;
    }
}
