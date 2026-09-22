<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Super-admin management of the bodies OCD trains — LGUs, NGAs, academe, and
 * teams — and of who belongs to each.
 *
 * Membership is curated here and nowhere else: a participant can't self-join,
 * and the freetext organization they typed at signup is only ever a suggestion
 * shown to the admin doing the assigning. That's what makes a confirmed member
 * trustworthy enough to act for the body.
 */
class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $organizations = Organization::filteredBy(
            $request->query('type'),
            $request->query('region'),
            trim((string) $request->query('q', '')) ?: null,
        )
            ->withCount('members')
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.super-admin.organizations.index', [
            'organizations' => $organizations,
            'typeLabels' => Organization::$typeLabels,
            'regions' => config('regions.list'),
            'selectedType' => $request->query('type'),
            'selectedRegion' => $request->query('region'),
            'search' => trim((string) $request->query('q', '')),
        ]);
    }

    public function show(Request $request, Organization $organization): View
    {
        $organization->load(['members' => fn ($query) => $query->orderBy('name')]);

        // Candidates are listed straight away rather than only after a search:
        // the roster is narrowed to this body's own region and to people not
        // already in an organization, which is usually a short enough list to
        // just tick through. An LGU in Region III recruits from Region III, so
        // the nationwide roster would be mostly noise. A body with no region
        // set isn't narrowed, since there's nothing to narrow to.
        $candidateSearch = trim((string) $request->query('candidates_q', ''));

        $candidates = User::unassigned()
            ->where('role', '!=', User::ROLE_SUPER_ADMIN)
            ->when($organization->region, fn ($query) => $query->where('region', $organization->region))
            ->when($candidateSearch !== '', function ($query) use ($candidateSearch) {
                $query->where(function ($inner) use ($candidateSearch) {
                    $inner->where('name', 'like', "%{$candidateSearch}%")
                        ->orWhere('email', 'like', "%{$candidateSearch}%")
                        ->orWhere('organization', 'like', "%{$candidateSearch}%");
                });
            })
            ->orderBy('name')
            ->paginate(10, ['id', 'name', 'email', 'organization', 'city', 'region'], 'candidates')
            ->withQueryString()
            ->fragment('organization-candidates');

        $payload = [
            'organization' => $organization,
            'typeLabels' => Organization::$typeLabels,
            'regions' => config('regions.list'),
            'candidates' => $candidates,
            'candidateSearch' => $candidateSearch,
        ];

        // Typing in the member search re-requests this route and swaps in just
        // the candidate table, so the page neither reloads nor jumps back to
        // the top — same live-search wiring the Summary and Needs Assessment
        // tabs use (resources/views/admin/partials/live-search-script.blade.php).
        if ($request->ajax() && $request->query('_section') === 'candidates') {
            return view('admin.partials.organization-candidates', $payload);
        }

        return view('admin.super-admin.organizations.show', $payload);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = Organization::create($this->validated($request));

        return Redirect::route('admin.organizations.show', $organization)
            ->with('status', "{$organization->name} was added.");
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $organization->update($this->validated($request, $organization));

        return Redirect::route('admin.organizations.show', $organization)
            ->with('status', "{$organization->name} was updated.");
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        // Members aren't deleted with the body — organization_id is nulled by
        // the foreign key, returning them to the unassigned queue.
        $name = $organization->name;
        $organization->delete();

        return Redirect::route('admin.organizations.index')
            ->with('status', "{$name} was removed. Its members are now unassigned.");
    }

    /**
     * Place the ticked people into this organization.
     *
     * Takes a list rather than one id at a time — a roster usually arrives as
     * a batch, and ticking ten boxes then saving once beats ten separate adds.
     * `position` is optional and applied to everyone in this batch, so add
     * people with different positions in separate batches.
     */
    public function addMembers(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', '!=', User::ROLE_SUPER_ADMIN)),
            ],
            'position' => ['nullable', 'string', 'max:255'],
        ], [
            'user_ids.required' => 'Tick at least one person to add.',
        ]);

        $added = User::whereIn('id', $validated['user_ids'])
            ->where('role', '!=', User::ROLE_SUPER_ADMIN)
            ->update([
                'organization_id' => $organization->id,
                'position' => $validated['position'] ?? null,
            ]);

        return Redirect::route('admin.organizations.show', $organization)
            ->with('status', trans_choice(
                ':count person was added to :org|:count people were added to :org',
                $added,
                ['count' => $added, 'org' => $organization->name],
            ));
    }

    public function removeMember(Organization $organization, User $user): RedirectResponse
    {
        abort_unless($user->organization_id === $organization->id, 404);

        $user->organization_id = null;
        $user->position = null;
        $user->save();

        return Redirect::route('admin.organizations.show', $organization)
            ->with('status', "{$user->name} was removed from {$organization->name}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Organization $organization = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                // The same body shouldn't be entered twice under one type and
                // region; different regions may legitimately share a name.
                Rule::unique('organizations')
                    ->where(fn ($q) => $q->where('type', $request->input('type'))->where('region', $request->input('region')))
                    ->ignore($organization),
            ],
            'type' => ['required', 'string', Rule::in(array_keys(Organization::$typeLabels))],
            'region' => ['nullable', 'string', Rule::in(config('regions.list'))],
            'city' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.unique' => 'That name is already on file for this type and region.',
        ]);
    }
}
