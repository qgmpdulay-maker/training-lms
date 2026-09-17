<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('participant.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $previousPicture = null;

        if ($request->hasFile('picture')) {
            $previousPicture = $request->user()->picture;
            $validated['picture'] = $request->file('picture')->store('participant-pictures', 'public');
        } else {
            unset($validated['picture']);
        }

        $request->user()->fill($validated)->save();

        // The replaced picture is no longer used by anyone (every upload gets
        // its own random file name), so delete it instead of letting old
        // uploads pile up on the server's limited disk space.
        if ($previousPicture) {
            Storage::disk('public')->delete($previousPicture);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Display the participant's printable ID card.
     */
    public function idCard(Request $request): View
    {
        return view('participant.profile.id-card', [
            'user' => $request->user(),
        ]);
    }
}
