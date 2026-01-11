<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\SitePreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): Response
    {
        $events = Event::withCount('registrations')
            ->orderBy('start_date', 'desc')
            ->get();

        $sitePreferences = SitePreference::instance();

        return Inertia::render('Admin/Dashboard', [
            'events' => $events,
            'sitePreferences' => $sitePreferences,
        ]);
    }

    /**
     * Update site preferences.
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'association_description' => 'nullable|string|max:5000',
            'website_url' => 'nullable|url|max:255',
            'general_whatsapp_link' => 'nullable|url|max:1000',
        ]);

        $preferences = SitePreference::instance();
        $preferences->update($validated);

        return back()->with('success', __('admin.preferences_updated'));
    }

    /**
     * Upload site logo.
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $preferences = SitePreference::instance();

        // Delete old logo if exists
        if ($preferences->logo_path) {
            Storage::disk('public')->delete($preferences->logo_path);
        }

        // Store and resize new logo
        $path = $request->file('logo')->store('logos/site', 'public');
        
        // Resize image if necessary
        $this->resizeImage(Storage::disk('public')->path($path));

        $preferences->update(['logo_path' => $path]);

        return back()->with('success', __('admin.logo_updated'));
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
            return; // No resize needed
        }

        // Calculate new dimensions
        $ratio = min(1000 / $width, 1000 / $height);
        $newWidth = (int) ($width * $ratio);
        $newHeight = (int) ($height * $ratio);

        // Create image resource
        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            default => null,
        };

        if (!$source) {
            return;
        }

        $destination = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG
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

        // Save resized image
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($destination, $path, 90),
            IMAGETYPE_PNG => imagepng($destination, $path),
            default => null,
        };

        imagedestroy($source);
        imagedestroy($destination);
    }
}
