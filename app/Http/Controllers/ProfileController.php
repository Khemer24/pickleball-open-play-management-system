<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $originalEmail = $user->email;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'max:3072'],
        ]);

        $disk = config('filesystems.default');
        $oldAvatarPath = $user->avatar_path;

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', $disk);

            if (! $avatarPath) {
                throw ValidationException::withMessages([
                    'avatar' => 'The profile photo could not be saved. Please try again.',
                ]);
            }

            $data['avatar_path'] = $avatarPath;
        }

        if ($data['email'] !== $user->email) {
            $data['email_verified_at'] = null;
        }

        $user->update(collect($data)->except('avatar')->all());

        if ($oldAvatarPath && isset($data['avatar_path'])) {
            Storage::disk($disk)->delete($oldAvatarPath);
        }

        if ($data['email'] !== $originalEmail) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('success', 'Your profile has been updated.');
    }
}
