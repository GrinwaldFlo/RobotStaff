<?php

namespace App\Http\Controllers;

use App\Mail\AdminNotificationMail;
use App\Models\EmailNotification;
use App\Models\Event;
use App\Models\StaffAvailability;
use App\Models\StaffEventRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class EventRegistrationController extends Controller
{
    /**
     * Display the event registration page.
     */
    public function show(Request $request, string $tagname): Response
    {
        $staff = $request->attributes->get('staff');

        $event = Event::where('tagname', $tagname)
            ->with(['roles', 'days'])
            ->firstOrFail();

        $registration = StaffEventRegistration::where('staff_id', $staff->id)
            ->where('event_id', $event->id)
            ->with([
                'rolePreferences.role',
                'assignedRole',
                'availability.eventDay',
            ])
            ->first();

        return Inertia::render('Event/Show', [
            'event' => $event,
            'registration' => $registration,
            'staff' => $staff,
        ]);
    }

    /**
     * Register for an event.
     */
    public function register(Request $request, string $tagname)
    {
        $staff = $request->attributes->get('staff');

        $event = Event::where('tagname', $tagname)->firstOrFail();

        // Check if event has not passed
        if ($event->isPast()) {
            return back()->withErrors(['event' => __('event.already_passed')]);
        }

        // Check if already registered
        $existingRegistration = StaffEventRegistration::where('staff_id', $staff->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existingRegistration) {
            return back()->withErrors(['event' => __('event.already_registered')]);
        }

        $registration = StaffEventRegistration::create([
            'staff_id' => $staff->id,
            'event_id' => $event->id,
        ]);

        // Notify admins of new registration
        $adminCount = $this->notifyAdminsOfNewRegistration($staff, $event);

        return back()->with('success', __('event.registered'));
    }

    /**
     * Cancel event registration.
     */
    public function cancel(Request $request, string $tagname)
    {
        $staff = $request->attributes->get('staff');

        $event = Event::where('tagname', $tagname)->firstOrFail();

        $registration = StaffEventRegistration::where('staff_id', $staff->id)
            ->where('event_id', $event->id)
            ->firstOrFail();

        $registration->delete();

        return back()->with('success', __('event.registration_cancelled'));
    }

    /**
     * Update registration details.
     */
    public function update(Request $request, string $tagname)
    {
        $staff = $request->attributes->get('staff');

        $event = Event::where('tagname', $tagname)->firstOrFail();

        $registration = StaffEventRegistration::where('staff_id', $staff->id)
            ->where('event_id', $event->id)
            ->firstOrFail();

        $validated = $request->validate([
            'comment' => 'nullable|string|max:2000',
            'help_before_event' => 'boolean',
            'team_affiliation' => 'nullable|string|max:500',
            'is_first_participation' => 'boolean',
        ]);

        $registration->update($validated);

        // Notify admins with cooldown
        $this->notifyAdminsOfChange($staff, $event);

        return back()->with('success', __('event.registration_updated'));
    }

    /**
     * Update role preferences.
     */
    public function updateRolePreferences(Request $request, string $tagname)
    {
        $staff = $request->attributes->get('staff');

        $event = Event::where('tagname', $tagname)->firstOrFail();

        $registration = StaffEventRegistration::where('staff_id', $staff->id)
            ->where('event_id', $event->id)
            ->firstOrFail();

        $validated = $request->validate([
            'role_ids' => 'required|array|min:1|max:3',
            'role_ids.*' => 'exists:event_roles,id',
        ]);

        // Verify all roles belong to this event
        $eventRoleIds = $event->roles->pluck('id')->toArray();
        foreach ($validated['role_ids'] as $roleId) {
            if (!in_array($roleId, $eventRoleIds)) {
                return back()->withErrors(['role_ids' => __('event.invalid_role')]);
            }
        }

        $registration->setRolePreferences($validated['role_ids']);

        // Notify admins with cooldown
        $this->notifyAdminsOfChange($staff, $event);

        return back()->with('success', __('event.preferences_updated'));
    }

    /**
     * Update availability.
     */
    public function updateAvailability(Request $request, string $tagname)
    {
        $staff = $request->attributes->get('staff');

        $event = Event::where('tagname', $tagname)->firstOrFail();

        $registration = StaffEventRegistration::where('staff_id', $staff->id)
            ->where('event_id', $event->id)
            ->firstOrFail();

        $validated = $request->validate([
            'availability' => 'required|array',
            'availability.*.event_day_id' => 'required|exists:event_days,id',
            'availability.*.is_available_morning' => 'required|boolean',
            'availability.*.is_available_afternoon' => 'required|boolean',
        ]);

        foreach ($validated['availability'] as $availability) {
            StaffAvailability::updateOrCreate(
                [
                    'registration_id' => $registration->id,
                    'event_day_id' => $availability['event_day_id'],
                ],
                [
                    'is_available_morning' => $availability['is_available_morning'],
                    'is_available_afternoon' => $availability['is_available_afternoon'],
                ]
            );
        }

        // Notify admins with cooldown
        $this->notifyAdminsOfChange($staff, $event);

        return back()->with('success', __('event.availability_updated'));
    }

    /**
     * Notify all admins of a new registration.
     */
    protected function notifyAdminsOfNewRegistration($staff, $event): int
    {
        $admins = User::all();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(
                new AdminNotificationMail(
                    $admin,
                    __('notifications.new_registration_subject', ['event' => $event->name]),
                    __('notifications.new_registration_body', [
                        'staff' => $staff->first_name ?? $staff->username,
                        'event' => $event->name,
                    ])
                )
            );

            EmailNotification::recordNotification(
                'admin',
                (string) $admin->id,
                EmailNotification::TYPE_NEW_REGISTRATION,
                $event->id
            );
        }
        
        return $admins->count();
    }

    /**
     * Notify admins of registration changes (with cooldown).
     */
    protected function notifyAdminsOfChange($staff, $event): void
    {
        // Check cooldown - max 1 email per 5 minutes per staff member
        if (!EmailNotification::canSendNotification(
            'staff',
            $staff->id,
            EmailNotification::TYPE_PREFERENCES_CHANGED,
            5
        )) {
            return;
        }

        $admins = User::all();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(
                new AdminNotificationMail(
                    $admin,
                    __('notifications.preferences_changed_subject', ['event' => $event->name]),
                    __('notifications.preferences_changed_body', [
                        'staff' => $staff->first_name ?? $staff->username,
                        'event' => $event->name,
                    ])
                )
            );
        }

        // Record notification against staff to track cooldown
        EmailNotification::recordNotification(
            'staff',
            $staff->id,
            EmailNotification::TYPE_PREFERENCES_CHANGED,
            $event->id
        );
    }
}
