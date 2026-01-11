<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\SitePreference;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the home page.
     */
    public function index(Request $request): Response
    {
        $staff = $request->attributes->get('staff');
        $sitePreferences = SitePreference::instance();

        $data = [
            'sitePreferences' => $sitePreferences,
            'staff' => $staff,
            'registeredEvents' => [],
            'availableEvents' => [],
        ];

        if ($staff) {
            // Get events the staff is registered for
            $registeredEventIds = $staff->registrations()
                ->pluck('event_id')
                ->toArray();

            $data['registeredEvents'] = Event::whereIn('id', $registeredEventIds)
                ->orderBy('start_date')
                ->get();

            // Get available future events (not registered)
            $data['availableEvents'] = Event::future()
                ->whereNotIn('id', $registeredEventIds)
                ->orderBy('start_date')
                ->get();

            // Check profile completeness
            $data['isProfileComplete'] = $staff->isProfileComplete();
        } else {
            // For non-authenticated users, show future events
            $data['availableEvents'] = Event::future()
                ->orderBy('start_date')
                ->get();
        }

        return Inertia::render('Home', $data);
    }
}
