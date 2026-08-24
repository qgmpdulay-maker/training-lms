<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class InstructorController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $instructorsByRegion = Instructor::orderBy('name')
                ->get()
                ->groupBy(fn (Instructor $instructor) => $instructor->region ?: 'Central / Unassigned')
                ->sortKeys();

            return view('admin.super-admin.instructors.index', [
                'instructorsByRegion' => $instructorsByRegion,
                'regions' => config('regions.list'),
            ]);
        }

        $instructors = Instructor::where('region', $user->region)->orderBy('name')->paginate(20);

        return view('admin.instructors', [
            'instructors' => $instructors,
            'regions' => config('regions.list'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sex' => ['nullable', 'string', 'in:Male,Female,Other'],
            'position' => ['nullable', 'string', 'max:255'],
            'training_type' => ['required', 'string', 'max:255'],
            'certificate_code' => ['nullable', 'string', 'max:255'],
            'deployment' => ['nullable', 'string', 'max:255'],
            'agency_organization' => ['nullable', 'string', 'max:255'],
            'lgu' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'in:'.implode(',', config('regions.list'))],
        ]);

        // Regional admins can only add instructors under their own region.
        if ($request->user()->isAdmin()) {
            $validated['region'] = $request->user()->region;
        }

        Instructor::create($validated);

        return Redirect::back()->with('status', "{$validated['name']} was added to the instructor roster.");
    }

    public function show(Instructor $instructor): View
    {
        $records = Instructor::where('name', $instructor->name)->orderBy('training_type')->get();

        $ratings = $records->pluck('rating')->filter(fn ($r) => is_numeric($r));

        return view('admin.super-admin.instructors.show', [
            'instructor' => $instructor,
            'records' => $records,
            'overallRating' => $ratings->isNotEmpty() ? round($ratings->avg(), 2) : null,
        ]);
    }

    public function updateComplaints(Request $request, Instructor $instructor): RedirectResponse
    {
        $validated = $request->validate([
            'complaints' => ['nullable', 'string', 'max:2000'],
        ]);

        $instructor->update($validated);

        return Redirect::route('admin.instructors.show', $instructor)->with('status', 'Complaints record updated.');
    }
}
