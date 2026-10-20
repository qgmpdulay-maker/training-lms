<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The bodies OCD trains — LGUs, NGAs, academe, and teams — and who belongs to
 * each.
 *
 * Who does what here splits by role. A **Regional Admin adds** the bodies in
 * their own region and sees that region's list: regional staff are the ones
 * who know which LGUs and schools exist in their area, so making the Super
 * Admin type them all in was the wrong way round. Everything after that —
 * editing a body, removing one, and above all placing members into it — stays
 * **Super Admin**.
 *
 * Membership in particular is curated there and nowhere else: a participant
 * can't self-join, and the freetext organization they typed at signup is only
 * ever a suggestion shown to the admin doing the assigning. That's what makes
 * a confirmed member trustworthy enough to act for the body.
 */
class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // A Regional Admin's list is their own region and nothing else, so the
        // region filter is replaced by a notice saying so — same shape as the
        // Tools and Summary pages.
        $regionLocked = $user->isAdmin();
        $region = $regionLocked ? $user->region : $request->query('region');

        $organizations = Organization::filteredBy(
            $request->query('type'),
            $region,
            trim((string) $request->query('q', '')) ?: null,
        )
            ->withCount('members')
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.organizations.index', [
            'organizations' => $organizations,
            'typeLabels' => Organization::$typeLabels,
            'regions' => config('regions.list'),
            'selectedType' => $request->query('type'),
            'selectedRegion' => $region,
            'regionLocked' => $regionLocked,
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
        $candidateCity = trim((string) $request->query('city', ''));

        $candidates = $this->candidateQuery($organization, $candidateSearch, $candidateCity)
            ->orderBy('name')
            ->paginate(10, ['id', 'name', 'email', 'organization', 'city', 'region'], 'candidates')
            ->withQueryString()
            ->fragment('organization-candidates');

        // Only cities somebody in this pool actually lives in, so the filter
        // never offers a choice that comes back empty.
        $cityOptions = $this->candidateQuery($organization)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        $payload = [
            'organization' => $organization,
            'typeLabels' => Organization::$typeLabels,
            'regions' => config('regions.list'),
            'candidates' => $candidates,
            'candidateSearch' => $candidateSearch,
            'candidateCity' => $candidateCity,
            'cityOptions' => $cityOptions,
        ];

        // Typing in the member search re-requests this route and swaps in just
        // the candidate table, so the page neither reloads nor jumps back to
        // the top — same live-search wiring the Summary and Needs Assessment
        // tabs use (resources/views/admin/partials/live-search-script.blade.php).
        if ($request->ajax() && $request->query('_section') === 'candidates') {
            return view('admin.partials.organization-candidates', $payload);
        }

        return view('admin.organizations.show', $payload);
    }

    /**
     * People who can still be placed into this body, optionally narrowed by a
     * search and a city. Shared by the candidate table and by "select all
     * matching", so both always mean exactly the same set of people.
     *
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    private function candidateQuery(Organization $organization, string $search = '', string $city = '')
    {
        return User::unassigned()
            ->where('role', '!=', User::ROLE_SUPER_ADMIN)
            ->when($organization->region, fn ($query) => $query->where('region', $organization->region))
            ->when($city !== '', fn ($query) => $query->where('city', $city))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('organization', 'like', "%{$search}%");
                });
            });
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // A Regional Admin files bodies under their own region, whatever the
        // form sent. Merged in before validation rather than after, because
        // the name-uniqueness rule is scoped by region — validating a null
        // region and then saving a real one would let the same LGU in twice.
        if ($user->isAdmin()) {
            $request->merge(['region' => $user->region]);
        }

        $organization = Organization::create($this->validated($request));

        // Only a Super Admin has anything to do on the detail page; a Regional
        // Admin is sent back to their list, where the new body is now on file
        // for the Super Admin to place members into.
        if ($user->isAdmin()) {
            return Redirect::route('admin.organizations.index')
                ->with('status', "{$organization->name} was added to {$user->region}.");
        }

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
            'select_all_matching' => ['nullable', 'boolean'],
            'candidates_q' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'user_ids' => ['required_without:select_all_matching', 'array'],
            'user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', '!=', User::ROLE_SUPER_ADMIN)),
            ],
            'position' => ['nullable', 'string', 'max:255'],
        ], [
            'user_ids.required_without' => 'Tick at least one person to add.',
        ]);

        // "Select all matching" sends the filters rather than thousands of
        // ids: the set is re-queried here, from the same candidateQuery() the
        // table used, so it's exactly the people the admin was looking at —
        // and only ever people still unassigned.
        $people = $request->boolean('select_all_matching')
            ? $this->candidateQuery($organization, trim((string) ($validated['candidates_q'] ?? '')), trim((string) ($validated['city'] ?? '')))
            : User::whereIn('id', $validated['user_ids'])->where('role', '!=', User::ROLE_SUPER_ADMIN);

        $added = $people->update([
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
