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
    public function index(Request $request): View
    {
        $adminSearch = trim((string) $request->query('admins_q'));
        $participantSearch = trim((string) $request->query('participants_q'));

        $searchScope = fn ($query, string $search) => $query->when($search !== '', function ($q) use ($search) {
            $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('organization', 'like', "%{$search}%");
            });
        });

        $participants = User::where('role', User::ROLE_PARTICIPANT)
            ->tap(fn ($q) => $searchScope($q, $participantSearch))
            ->orderBy('name')
            ->paginate(15, ['*'], 'participants')
            ->withQueryString();

        $admins = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
            ->tap(fn ($q) => $searchScope($q, $adminSearch))
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $regions = config('regions.list');

        return view('admin.super-admin.users.index', compact('participants', 'admins', 'regions', 'adminSearch', 'participantSearch'));
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
