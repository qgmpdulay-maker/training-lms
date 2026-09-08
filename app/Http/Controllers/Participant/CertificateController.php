<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        $certificates = Certificate::where('user_id', $request->user()->id)
            ->with('trainingRequest')
            ->orderByDesc('issued_on')
            ->get();

        return view('participant.certificates.index', [
            'certificates' => $certificates,
        ]);
    }
}
