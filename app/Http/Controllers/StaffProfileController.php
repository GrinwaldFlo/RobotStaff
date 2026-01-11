<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StaffProfileController extends Controller
{
    /**
     * Display the staff profile page.
     */
    public function show(Request $request): Response
    {
        $staff = $request->attributes->get('staff');

        return Inertia::render('Staff/Profile', [
            'staff' => $staff,
        ]);
    }

    /**
     * Update staff profile.
     */
    public function update(Request $request)
    {
        $staff = $request->attributes->get('staff');

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:100',
            'comment' => 'nullable|string|max:2000',
        ]);

        $staff->update($validated);

        return back()->with('success', __('staff.profile_updated'));
    }

    /**
     * Upload staff photo.
     */
    public function uploadPhoto(Request $request)
    {
        $staff = $request->attributes->get('staff');

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        // Delete old photo if exists
        if ($staff->photo_path) {
            Storage::disk('public')->delete($staff->photo_path);
        }

        // Store new photo
        $path = $request->file('photo')->store("photos/staff/{$staff->id}", 'public');
        
        // Resize image if necessary
        $this->resizeImage(Storage::disk('public')->path($path));

        $staff->update(['photo_path' => $path]);

        return back()->with('success', __('staff.photo_updated'));
    }

    /**
     * Delete staff photo.
     */
    public function deletePhoto(Request $request)
    {
        $staff = $request->attributes->get('staff');

        if ($staff->photo_path) {
            Storage::disk('public')->delete($staff->photo_path);
            $staff->update(['photo_path' => null]);
        }

        return back()->with('success', __('staff.photo_deleted'));
    }

    /**
     * Anonymize staff data (GDPR deletion request).
     */
    public function anonymize(Request $request)
    {
        $staff = $request->attributes->get('staff');

        // Delete photo if exists
        if ($staff->photo_path) {
            Storage::disk('public')->delete($staff->photo_path);
        }

        $staff->anonymize();

        // Clear the cookie and redirect
        $cookie = cookie()->forget('staff_token');

        return redirect()->route('home')
            ->withCookie($cookie)
            ->with('success', __('staff.data_deleted'));
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
