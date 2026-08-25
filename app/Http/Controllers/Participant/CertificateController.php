<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        $certificates = TrainingRequest::involvingUser($request->user())
            ->whereNotNull('certificate_file_path')
            ->orderByDesc('preferred_date')
            ->get();

        return view('participant.certificates.index', [
            'certificates' => $certificates,
            'certificateRemarksLabels' => TrainingRequest::$certificateRemarksLabels,
        ]);
    }
}
