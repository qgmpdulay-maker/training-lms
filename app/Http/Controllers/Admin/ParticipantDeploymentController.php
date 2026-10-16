<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParticipantDeployment;
use App\Models\User;
use App\Support\CityDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
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

        $graduates = User::graduates()
            ->when($user->isAdmin(), fn ($query) => $query->where('region', $user->region))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('organization', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            // Lets an admin jump straight to who still needs recording, rather
            // than paging through graduates who are already accounted for.
            ->when($status === 'deployed', fn ($query) => $query->has('deployments'))
            ->when($status === 'not_deployed', fn ($query) => $query->doesntHave('deployments'))
            ->when($status === 'no_city', fn ($query) => $this->notOnMap($query))
            ->with(['deployments.recordedBy:id,name'])
            ->orderBy('name')
            ->paginate(10, ['*'], 'graduates')
            ->withQueryString()
            ->fragment('deployment-graduates');

        $payload = [
            'graduates' => $graduates,
            'search' => $search,
            'status' => $status,
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
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'deployment_date.before_or_equal' => 'A deployment is recorded after it happens, so the date cannot be in the future.',
        ]);

        $user->deployments()->create([
            ...$validated,
            'recorded_by' => $request->user()->id,
        ]);

        return Redirect::back()->with('status', "Deployment recorded for {$user->name}.");
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
