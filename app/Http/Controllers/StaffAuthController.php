<?php

namespace App\Http\Controllers;

use App\Mail\StaffConnectionLink;
use App\Models\EmailNotification;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class StaffAuthController extends Controller
{
    /**
     * Handle new staff registration.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:staff,username',
            'email' => 'required|email|max:255|unique:staff,email',
        ]);

        $staff = Staff::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
        ]);

        $this->sendConnectionEmail($staff);

        return back()->with('success', __('auth.registration_email_sent'));
    }

    /**
     * Handle existing staff login request.
     */
    public function requestLogin(Request $request)
    {
        $validated = $request->validate([
            'identifier' => 'required|string|max:255',
        ]);

        $staff = Staff::where('username', $validated['identifier'])
            ->orWhere('email', $validated['identifier'])
            ->first();

        if (!$staff) {
            return back()->withErrors([
                'identifier' => __('auth.staff_not_found'),
            ]);
        }

        $this->sendConnectionEmail($staff);

        return back()->with('success', __('auth.login_email_sent'));
    }

    /**
     * Handle the login via token link.
     */
    public function loginWithToken(Request $request, string $token)
    {
        $staff = Staff::where('auth_token', $token)
            ->where(function ($query) {
                $query->whereNull('token_expires_at')
                    ->orWhere('token_expires_at', '>', now());
            })
            ->first();

        if (!$staff) {
            return redirect()->route('home')->withErrors([
                'token' => __('auth.invalid_or_expired_token'),
            ]);
        }

        // Refresh token and expiration
        $staff->refreshTokenExpiration();

        // Set the cookie and redirect
        $cookie = cookie('staff_token', $staff->auth_token, 60 * 24 * 60); // 60 days

        return redirect()->route('home')->withCookie($cookie);
    }

    /**
     * Handle staff logout.
     */
    public function logout(Request $request)
    {
        $cookie = cookie()->forget('staff_token');

        return redirect()->route('home')->withCookie($cookie);
    }

    /**
     * Send connection email to staff.
     */
    protected function sendConnectionEmail(Staff $staff): void
    {
        // Regenerate token
        $staff->regenerateToken();

        $loginUrl = route('staff.login.token', ['token' => $staff->auth_token]);

        Mail::to($staff->email)->send(new StaffConnectionLink($staff, $loginUrl));

        // Record notification
        EmailNotification::recordNotification(
            'staff',
            $staff->id,
            EmailNotification::TYPE_CONNECTION_LINK
        );
    }
}
