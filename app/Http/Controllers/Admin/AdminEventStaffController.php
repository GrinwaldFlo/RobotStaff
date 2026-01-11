<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\StaffNotification;
use App\Models\EmailNotification;
use App\Models\Event;
use App\Models\StaffEventRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class AdminEventStaffController extends Controller
{
    /**
     * Display staff list for an event.
     */
    public function index(string $tagname): Response
    {
        $event = Event::where('tagname', $tagname)
            ->with(['roles', 'days'])
            ->firstOrFail();

        $registrations = StaffEventRegistration::where('event_id', $event->id)
            ->with([
                'staff',
                'rolePreferences.role',
                'assignedRole',
                'availability.eventDay',
            ])
            ->get();

        return Inertia::render('Admin/Event/Staff', [
            'event' => $event,
            'registrations' => $registrations,
        ]);
    }

    /**
     * Validate a staff registration.
     */
    public function validate(Request $request, string $tagname, string $registrationId)
    {
        $event = Event::where('tagname', $tagname)->firstOrFail();
        $registration = StaffEventRegistration::where('event_id', $event->id)
            ->where('id', $registrationId)
            ->with('staff')
            ->firstOrFail();

        $registration->update(['is_validated' => true]);

        // Send notification to staff
        $this->notifyStaff(
            $registration,
            EmailNotification::TYPE_PARTICIPATION_VALIDATED,
            __('notifications.participation_validated_subject'),
            __('notifications.participation_validated_body', ['event' => $event->name])
        );

        return back()->with('success', __('admin.registration_validated'));
    }

    /**
     * Assign a role to a staff member.
     */
    public function assignRole(Request $request, string $tagname, string $registrationId)
    {
        $event = Event::where('tagname', $tagname)->firstOrFail();
        $registration = StaffEventRegistration::where('event_id', $event->id)
            ->where('id', $registrationId)
            ->with('staff')
            ->firstOrFail();

        $validated = $request->validate([
            'role_id' => 'required|exists:event_roles,id',
        ]);

        // Verify role belongs to this event
        $role = $event->roles()->findOrFail($validated['role_id']);

        $registration->update(['assigned_role_id' => $role->id]);

        // Send notification to staff
        $this->notifyStaff(
            $registration,
            EmailNotification::TYPE_ROLE_ASSIGNED,
            __('notifications.role_assigned_subject'),
            __('notifications.role_assigned_body', [
                'event' => $event->name,
                'role' => $role->designation,
            ])
        );

        return back()->with('success', __('admin.role_assigned'));
    }

    /**
     * Send event reminder to all validated staff.
     */
    public function sendReminder(Request $request, string $tagname)
    {
        $event = Event::where('tagname', $tagname)->firstOrFail();

        $registrations = StaffEventRegistration::where('event_id', $event->id)
            ->where('is_validated', true)
            ->with('staff')
            ->get();

        foreach ($registrations as $registration) {
            $this->notifyStaff(
                $registration,
                EmailNotification::TYPE_EVENT_REMINDER,
                __('notifications.event_reminder_subject', ['event' => $event->name]),
                __('notifications.event_reminder_body', ['event' => $event->name])
            );
        }

        return back()->with('success', __('admin.reminders_sent', ['count' => $registrations->count()]));
    }

    /**
     * Send notification to staff member.
     */
    protected function notifyStaff(
        StaffEventRegistration $registration,
        string $notificationType,
        string $subject,
        string $body
    ): void {
        $staff = $registration->staff;
        $event = $registration->event;

        Mail::to($staff->email)->send(
            new \App\Mail\StaffNotificationMail($staff, $subject, $body)
        );

        EmailNotification::recordNotification(
            'staff',
            $staff->id,
            $notificationType,
            $event->id
        );
    }
}
