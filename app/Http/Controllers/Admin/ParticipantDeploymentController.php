<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParticipantDeployment;
use App\Models\User;
use App\Support\CityDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Recording where graduates have been deployed after finishing a training,
 * and which city each is counted under on the Graduates by Location map.
 *
 * These are the only things a Regional Admin may change — everything else
 * about trainings and certificates is Super Admin only (see routes/web.php).
 * The reasoning: regional staff are the ones who actually know which of
 * their graduates got sent to an operation, and where they're based, so they
 * maintain it for their own region. Super Admin can do it for any region.
 */
class ParticipantDeploymentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        // A Regional Admin is always held to their own region; Super Admin
        // can narrow to one, or leave it blank for the whole country.
        $region = $user->isAdmin()
            ? $user->region
            : (in_array($request->query('region'), config('regions.list'), true) ? $request->query('region') : null);

        // A Regional Admin browses their region by city instead: the home
        // cities their graduates are counted under, each with its spellings.
        $cities = $user->isAdmin() ? $this->homeCities($region) : collect();
        $city = $cities->has($request->query('city')) ? $request->query('city') : null;

        $inScope = fn () => User::graduates()
            ->when($region, fn ($query) => $query->where('region', $region))
            ->when($city, fn ($query) => $query->whereIn('city', $cities[$city]['values']));

        $graduates = $inScope()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('organization', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            // Lets an admin jump straight to who still needs recording, rather
            // than paging through graduates who are already accounted for.
            // no_city isn't in the dropdown; it's reached from the "missing a
            // home city" notice above the list.
            ->when($status === 'deployed', fn ($query) => $query->has('deployments'))
            ->when($status === 'not_deployed', fn ($query) => $query->doesntHave('deployments'))
            ->when($status === 'no_city', fn ($query) => $this->notOnMap($query))
            ->with(['deployments.recordedBy:id,name'])
            ->orderBy('name')
            ->paginate(10, ['*'], 'graduates')
            ->withQueryString()
            ->fragment('deployment-graduates');

        // The counts across the top of the list. They follow the region and
        // city filters but not the search box, so they describe the whole
        // region (or city) rather than whatever's been typed.
        $total = $inScope()->count();
        $deployed = $inScope()->has('deployments')->count();
        $counts = [
            'total' => $total,
            'deployed' => $deployed,
            'not_deployed' => $total - $deployed,
            'no_city' => tap($inScope(), fn ($query) => $this->notOnMap($query))->count(),
        ];

        $payload = [
            'graduates' => $graduates,
            'search' => $search,
            'status' => $status,
            'region' => $region,
            'regions' => config('regions.list'),
            'city' => $city,
            'cities' => $cities,
            'counts' => $counts,
        ];

        // Typing re-requests this route and swaps in just the list, so the page
        // neither reloads nor jumps back to the top — same live-search wiring
        // the Summary and Organizations tabs use.
        if ($request->ajax() && $request->query('_section') === 'graduates') {
            return view('admin.partials.deployment-graduates', $payload);
        }

        return view('admin.deployments.index', $payload);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $this->authorizeFor($request, $user);

        $validated = $request->validate([
            'deployment' => ['required', 'string', 'max:255'],
            'deployment_date' => ['required', 'date', 'before_or_equal:today'],
            'deployment_role' => ['nullable', 'string', 'max:255'],
            // Where they were sent: any number of cities, each one picked
            // from CityDirectory's own list. Not narrowed to this admin's
            // region — a graduate sent to help elsewhere is the normal case.
            'cities' => ['nullable', 'array', 'max:50'],
            'cities.*' => ['string', Rule::in(CityDirectory::labels())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'deployment_date.before_or_equal' => 'A deployment is recorded after it happens, so the date cannot be in the future.',
        ]);

        // One record per city, so a graduate sent to several places shows
        // each of them separately and any one can be removed on its own. No
        // city picked still records the deployment itself.
        $cities = array_values(array_unique($validated['cities'] ?? [])) ?: [null];
        unset($validated['cities']);

        foreach ($cities as $city) {
            $user->deployments()->create([
                ...$validated,
                'city' => $city,
                'recorded_by' => $request->user()->id,
            ]);
        }

        return Redirect::back()->with('status', count($cities) > 1
            ? "Deployment recorded for {$user->name} in ".count($cities).' cities.'
            : "Deployment recorded for {$user->name}.");
    }

    /**
     * Sets the city a graduate is counted under on the Graduates by Location
     * map — usually where they live. It has to be a city or municipality the
     * map knows, or they'd still be left off it, so it's saved in the list's
     * own spelling ("City of Malolos, Bulacan").
     */
    public function updateCity(Request $request, User $user): RedirectResponse
    {
        $this->authorizeFor($request, $user);

        $validated = $request->validate(['city' => ['required', 'string', 'max:255']]);
        $city = CityDirectory::match($validated['city'], $user->region);

        if (! $city) {
            return Redirect::back()
                ->withErrors(['city' => __('Pick a city or municipality from the list so it can be placed on the map.')], "city_{$user->id}")
                ->withInput();
        }

        $user->update(['city' => CityDirectory::label($city)]);

        return Redirect::back()->with('status', __(':name is now counted under :city.', [
            'name' => $user->name,
            'city' => CityDirectory::label($city),
        ]));
    }

    public function destroy(Request $request, ParticipantDeployment $deployment): RedirectResponse
    {
        $deployment->loadMissing('user');
        $this->authorizeFor($request, $deployment->user);

        $deployment->delete();

        return Redirect::back()->with('status', 'Deployment record removed.');
    }

    /**
     * Graduates the map can't place: no City at all, or one CityDirectory
     * can't match. Matching happens in PHP, so the distinct City values are
     * checked once and the misses turned back into a query.
     */
    private function notOnMap($query): void
    {
        $unmatched = User::graduates()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->get(['city', 'region'])
            ->reject(fn (User $row) => CityDirectory::match($row->city, $row->region));

        $query->where(function ($q) use ($unmatched) {
            $q->whereNull('city')->orWhere('city', '');

            foreach ($unmatched as $row) {
                $q->orWhere(fn ($pair) => $pair->where('city', $row->city)->where('region', $row->region));
            }
        });
    }

    /**
     * The home cities a region's graduates are counted under, as label =>
     * ['values' => the City spellings that mean it, 'count' => graduates].
     * Matched in PHP like notOnMap(), so "Angeles, Pampanga" and "Angeles
     * City" are one option; anything the map can't place is left out, since
     * the "no home city" notice already covers those.
     */
    private function homeCities(?string $region): Collection
    {
        return User::graduates()
            ->where('region', $region)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->selectRaw('city, count(*) as total')
            ->groupBy('city')
            ->get()
            ->groupBy(fn (User $row) => ($match = CityDirectory::match($row->city, $region)) ? CityDirectory::label($match) : '')
            ->forget('')
            ->map(fn (Collection $rows) => [
                'values' => $rows->pluck('city')->all(),
                'count' => (int) $rows->sum('total'),
            ])
            ->sortKeys();
    }

    /**
     * Deployments and cities are only recorded against graduates, and a
     * Regional Admin only for their own region.
     */
    private function authorizeFor(Request $request, ?User $participant): void
    {
        abort_unless($participant && $participant->isGraduate(), 404);

        $actor = $request->user();
        abort_if($actor->isAdmin() && $participant->region !== $actor->region, 403);
    }
}
