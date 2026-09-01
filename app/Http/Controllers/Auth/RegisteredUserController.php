<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'sex' => ['required', 'in:Male,Female,Other'],
            'picture' => ['required', 'image', 'max:4096'],
            'participant_type' => ['required', 'string', 'max:255'],
            'organization' => ['required', 'string', 'max:255'],
            'agency' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'digits:11'],
            'landline_number' => ['nullable', 'digits:10'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'age' => $validated['age'],
            'sex' => $validated['sex'],
            'picture' => $request->file('picture')->store('participant-pictures', 'public'),
            'participant_type' => $validated['participant_type'],
            'organization' => $validated['organization'],
            'agency' => $validated['agency'],
            // Derived from the "OCD Regional Office" picklist so participants can be
            // scoped to a region the same way admins are (see config/regions.php).
            'region' => config('regions.agency_map')[$validated['agency']] ?? null,
            'mobile_number' => $validated['mobile_number'],
            'landline_number' => $validated['landline_number'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}