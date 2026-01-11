<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareStaffData
{
    /**
     * Handle an incoming request.
     * 
     * This middleware shares staff data with Inertia without requiring authentication.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $staff = $this->getOptionalStaff($request);

        if ($staff) {
            // Refresh token expiration on each visit
            $staff->refreshTokenExpiration();
        }

        $request->attributes->set('staff', $staff);

        return $next($request);
    }

    /**
     * Get the staff member from cookie if available.
     */
    protected function getOptionalStaff(Request $request): ?Staff
    {
        $token = $request->cookie('staff_token');

        if (!$token) {
            return null;
        }

        return Staff::where('auth_token', $token)
            ->where(function ($query) {
                $query->whereNull('token_expires_at')
                    ->orWhere('token_expires_at', '>', now());
            })
            ->first();
    }
}
