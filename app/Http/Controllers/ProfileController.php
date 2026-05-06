<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    private string $photoDisk;

    public function __construct()
    {
        // Use the same disk as all other file uploads in the app (S3 in production)
        $this->photoDisk = config('filesystems.default', 's3');
    }

    // -------------------------------------------------------------------------
    // Show the unified settings page
    // -------------------------------------------------------------------------
    public function show()
    {
        return view('profile.settings', [
            'user' => Auth::user(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Update name / email
    // -------------------------------------------------------------------------
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return back()->with('profile_updated', 'Profile information updated successfully.');
    }

    // -------------------------------------------------------------------------
    // Change password
    // -------------------------------------------------------------------------
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [
            'current_password.current_password' => 'The current password you entered is incorrect.',
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('password_updated', 'Password changed successfully.');
    }

    // -------------------------------------------------------------------------
    // Upload / replace profile photo
    // -------------------------------------------------------------------------
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = Auth::user();

        $path = $request->file('photo')->store('profile-photos', $this->photoDisk);

        if (!$path) {
            return back()->withErrors(['photo' => 'Upload failed — could not save the file. Please try again.']);
        }

        // Delete old photo only after confirming the new one was saved
        if ($user->profile_photo_path && $user->profile_photo_path !== $path) {
            \Illuminate\Support\Facades\Storage::disk($this->photoDisk)->delete($user->profile_photo_path);
        }

        $user->forceFill(['profile_photo_path' => $path])->save();

        return back()->with('photo_updated', 'Profile photo updated successfully.');
    }

    // -------------------------------------------------------------------------
    // Upload / replace signature
    // -------------------------------------------------------------------------
    public function uploadSignature(Request $request)
    {
        $request->validate([
            'signature' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = Auth::user();

        $path = $request->file('signature')->store('signatures', $this->photoDisk);

        if (!$path) {
            return back()->withErrors(['signature' => 'Upload failed — could not save the file. Please try again.']);
        }

        // Delete old signature only after confirming the new one was saved
        if ($user->signature_path && $user->signature_path !== $path) {
            \Illuminate\Support\Facades\Storage::disk($this->photoDisk)->delete($user->signature_path);
        }

        $user->forceFill(['signature_path' => $path])->save();

        return back()->with('signature_updated', 'Signature uploaded successfully.');
    }

    // -------------------------------------------------------------------------
    // Remove signature
    // -------------------------------------------------------------------------
    public function removeSignature()
    {
        $user = Auth::user();

        if ($user->signature_path) {
            Storage::disk($this->photoDisk)->delete($user->signature_path);
            $user->forceFill(['signature_path' => null])->save();
        }

        return back()->with('signature_removed', 'Signature removed.');
    }
}
