<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\AccountApproved;
use App\Mail\AccountRejected;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        // Only registrations that have finished OTP verification belong here —
        // nothing for a Super Admin to act on until then. Grouped by region
        // (like the Evaluation Computation tabs on the Tools page) so a long
        // nationwide queue doesn't read as one undifferentiated list — see
        // PendingRegistration::regionLabel() for how region is derived (only
        // known for OCD Personnel; everyone else lands in "Unspecified Region").
        $pendingAccounts = PendingRegistration::where('status', PendingRegistration::STATUS_PENDING)
            ->whereNotNull('email_verified_at')
            ->orderBy('created_at')
            ->get();

        $pendingAccountsByRegion = $pendingAccounts->groupBy(fn (PendingRegistration $account) => $account->regionLabel() ?? 'Unspecified Region')
            ->sortBy(fn ($accounts, $region) => array_search($region, [...config('regions.list'), 'Unspecified Region']));

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

        // Each search box re-requests this same route and swaps in just its own
        // results, so typing doesn't reload the whole page — see resources/views/
        // admin/partials/live-search-script.blade.php.
        if ($request->ajax() && $request->query('_section') === 'admins') {
            return view('admin.partials.manage-admins-results', compact('admins', 'adminSearch'));
        }

        if ($request->ajax() && $request->query('_section') === 'participants') {
            return view('admin.partials.manage-participants-results', compact('participants', 'participantSearch', 'regions'));
        }

        return view('admin.super-admin.users.index', compact('pendingAccounts', 'pendingAccountsByRegion', 'participants', 'admins', 'regions', 'adminSearch', 'participantSearch'));
    }

    public function approve(PendingRegistration $registration): RedirectResponse
    {
        abort_unless($registration->status === PendingRegistration::STATUS_PENDING, 403);

        $user = $registration->approve();

        try {
            Mail::to($user->email)->send(new AccountApproved($user));
        } catch (\Throwable $e) {
            Log::error('Failed to send account-approved email: '.$e->getMessage());
        }

        return Redirect::route('admin.users.index')->with('status', "{$user->name}'s account was approved.");
    }

    public function reject(PendingRegistration $registration): RedirectResponse
    {
        abort_unless($registration->status === PendingRegistration::STATUS_PENDING, 403);

        $registration->reject();

        try {
            Mail::to($registration->email)->send(new AccountRejected($registration));
        } catch (\Throwable $e) {
            Log::error('Failed to send account-rejected email: '.$e->getMessage());
        }

        return Redirect::route('admin.users.index')->with('status', "{$registration->name}'s account was rejected.");
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

    /**
     * Only the Super Admin can change a password on this system — regional
     * admins and participants who forget theirs have to ask. Defaults to a
     * random one-time password, but the Super Admin may type a specific one
     * instead. Either way it's flashed back once so it can be relayed
     * directly; it's never stored anywhere in plain text or emailed.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $newPassword = filled($validated['password'] ?? null) ? $validated['password'] : Str::password(12);

        $user->forceFill(['password' => Hash::make($newPassword)])->save();

        return Redirect::back()->with([
            'status' => "{$user->name}'s password was reset.",
            'tempPasswords' => [['name' => $user->name, 'password' => $newPassword]],
        ]);
    }

    /**
     * Same as resetPassword() but for a whole batch at once — each account
     * still gets its own independently generated random password (never one
     * shared password across multiple people).
     */
    public function bulkResetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $results = User::whereIn('id', $validated['user_ids'])->get()
            ->map(function (User $user) {
                $newPassword = Str::password(12);
                $user->forceFill(['password' => Hash::make($newPassword)])->save();

                return ['name' => $user->name, 'password' => $newPassword];
            })
            ->values()
            ->all();

        return Redirect::back()->with([
            'status' => count($results).' password(s) were reset.',
            'tempPasswords' => $results,
        ]);
    }
}
