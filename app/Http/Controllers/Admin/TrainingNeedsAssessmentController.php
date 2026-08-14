<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingNeedsAssessment;
use Illuminate\View\View;

class TrainingNeedsAssessmentController extends Controller
{
    public function index(): View
    {
        $submissions = TrainingNeedsAssessment::with('user')
            ->latest()
            ->paginate(20);

        return view('admin.training-needs-assessment', [
            'submissions' => $submissions,
        ]);
    }
}
