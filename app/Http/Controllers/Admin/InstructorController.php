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
    public function index(): View
    {
        $instructors = Instructor::orderBy('name')->paginate(20);

        return view('admin.instructors', [
            'instructors' => $instructors,
            'regions' => config('regions.list'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
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

        return Redirect::route('admin.instructors.index')->with('status', "{$validated['name']} was added to the instructor roster.");
    }
}
