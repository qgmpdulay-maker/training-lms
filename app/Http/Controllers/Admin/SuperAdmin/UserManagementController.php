<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $participants = User::where('role', User::ROLE_PARTICIPANT)
            ->orderBy('name')
            ->paginate(15, ['*'], 'participants');

        $admins = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $regions = config('regions.list');

        return view('admin.super-admin.users.index', compact('participants', 'admins', 'regions'));
    }

    public function promote(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isParticipant(), 403, 'Only participants can be promoted to admin.');

        $validated = $request->validate([
            'region' => ['required', 'string', 'in:'.implode(',', config('regions.list'))],
        ]);

        $user->forceFill([
            'role' => User::ROLE_ADMIN,
            'region' => $validated['region'],
        ])->save();

        return Redirect::route('admin.users.index')->with('status', "{$user->name} is now an admin for {$validated['region']}.");
    }

    public function demote(User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 403, 'Only regional admins can be demoted back to participant.');

        $user->forceFill([
            'role' => User::ROLE_PARTICIPANT,
            'region' => null,
        ])->save();

        return Redirect::route('admin.users.index')->with('status', "{$user->name} is now a participant.");
    }
}
