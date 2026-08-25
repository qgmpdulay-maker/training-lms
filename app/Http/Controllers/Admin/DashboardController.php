<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\TnaSubmission;
use App\Models\TrainingRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            return view('admin.dashboard-regional', ['user' => $user]);
        }

        return view('admin.super-admin.dashboard', [
            'user' => $user,
            'stats' => [
                'instructors' => Instructor::count(),
                'tna_submissions' => TnaSubmission::count(),
                'upcoming_trainings' => TrainingRequest::where('preferred_date', '>=', today())
                    ->whereIn('status', [TrainingRequest::STATUS_APPROVED, TrainingRequest::STATUS_UNDER_REVIEW])
                    ->count(),
            ],
        ]);
    }
}
