<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminEventController extends Controller
{
    /**
     * Display event details for editing.
     */
    public function show(string $tagname): Response
    {
        $event = Event::where('tagname', $tagname)
            ->with(['roles', 'days'])
            ->firstOrFail();

        return Inertia::render('Admin/Event/Show', [
            'event' => $event,
        ]);
    }

    /**
     * Store a new event.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tagname' => 'required|string|max:255|unique:events,tagname|alpha_dash',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $event = Event::create($validated);

        return redirect()->route('admin.event.show', $event->tagname)
            ->with('success', __('admin.event_created'));
    }

    /**
     * Update event details.
     */
    public function update(Request $request, string $tagname)
    {
        $event = Event::where('tagname', $tagname)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'short_description' => 'nullable|string|max:1000',
            'long_description' => 'nullable|string|max:10000',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'whatsapp_link' => 'nullable|url|max:1000',
            'general_documents_links' => 'nullable|array',
            'general_documents_links.*.title' => 'required|string|max:255',
            'general_documents_links.*.url' => 'required|url|max:1000',
        ]);

        $event->update($validated);

        // Sync event days if dates changed
        if (isset($validated['start_date']) || isset($validated['end_date'])) {
            $event->syncEventDays();
        }

        return back()->with('success', __('admin.event_updated'));
    }

    /**
     * Upload event logo.
     */
    public function uploadLogo(Request $request, string $tagname)
    {
        $event = Event::where('tagname', $tagname)->firstOrFail();

        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        // Delete old logo if exists
        if ($event->logo_path) {
            Storage::disk('public')->delete($event->logo_path);
        }

        // Store new logo
        $path = $request->file('logo')->store("logos/events/{$event->id}", 'public');
        
        // Resize image if necessary
        $this->resizeImage(Storage::disk('public')->path($path));

        $event->update(['logo_path' => $path]);

        return back()->with('success', __('admin.logo_updated'));
    }

    /**
     * Delete an event.
     */
    public function destroy(string $tagname)
    {
        $event = Event::where('tagname', $tagname)->firstOrFail();

        // Delete logo if exists
        if ($event->logo_path) {
            Storage::disk('public')->delete($event->logo_path);
        }

        $event->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', __('admin.event_deleted'));
    }

    /**
     * Copy an event.
     */
    public function copy(Request $request, string $tagname)
    {
        $event = Event::where('tagname', $tagname)->firstOrFail();

        $validated = $request->validate([
            'new_tagname' => 'required|string|max:255|unique:events,tagname|alpha_dash',
        ]);

        $newEvent = $event->copyTo($validated['new_tagname']);

        return redirect()->route('admin.event.show', $newEvent->tagname)
            ->with('success', __('admin.event_copied'));
    }

    /**
     * Add a role to the event.
     */
    public function addRole(Request $request, string $tagname)
    {
        $event = Event::where('tagname', $tagname)->firstOrFail();

        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'number_required' => 'required|integer|min:0',
            'document_links' => 'nullable|array',
            'document_links.*.title' => 'required|string|max:255',
            'document_links.*.url' => 'required|url|max:1000',
        ]);

        $event->roles()->create($validated);

        return back()->with('success', __('admin.role_added'));
    }

    /**
     * Update a role.
     */
    public function updateRole(Request $request, string $tagname, int $roleId)
    {
        $event = Event::where('tagname', $tagname)->firstOrFail();
        $role = $event->roles()->findOrFail($roleId);

        $validated = $request->validate([
            'designation' => 'sometimes|string|max:255',
            'number_required' => 'sometimes|integer|min:0',
            'document_links' => 'nullable|array',
            'document_links.*.title' => 'required|string|max:255',
            'document_links.*.url' => 'required|url|max:1000',
        ]);

        $role->update($validated);

        return back()->with('success', __('admin.role_updated'));
    }

    /**
     * Delete a role.
     */
    public function deleteRole(string $tagname, int $roleId)
    {
        $event = Event::where('tagname', $tagname)->firstOrFail();
        $role = $event->roles()->findOrFail($roleId);

        $role->delete();

        return back()->with('success', __('admin.role_deleted'));
    }

    /**
     * Update event day schedule.
     */
    public function updateDaySchedule(Request $request, string $tagname, int $dayId)
    {
        $event = Event::where('tagname', $tagname)->firstOrFail();
        $day = $event->days()->findOrFail($dayId);

        $validated = $request->validate([
            'schedule' => 'nullable|string|max:2000',
        ]);

        $day->update($validated);

        return back()->with('success', __('admin.schedule_updated'));
    }

    /**
     * Resize image to max 1000x1000 maintaining aspect ratio.
     */
    protected function resizeImage(string $path): void
    {
        $imageInfo = getimagesize($path);
        if (!$imageInfo) {
            return;
        }

        [$width, $height, $type] = $imageInfo;

        if ($width <= 1000 && $height <= 1000) {
            return;
        }

        $ratio = min(1000 / $width, 1000 / $height);
        $newWidth = (int) ($width * $ratio);
        $newHeight = (int) ($height * $ratio);

        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            default => null,
        };

        if (!$source) {
            return;
        }

        $destination = imagecreatetruecolor($newWidth, $newHeight);

        if ($type === IMAGETYPE_PNG) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
        }

        imagecopyresampled(
            $destination, $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );

        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($destination, $path, 90),
            IMAGETYPE_PNG => imagepng($destination, $path),
            default => null,
        };

        imagedestroy($source);
        imagedestroy($destination);
    }
}
