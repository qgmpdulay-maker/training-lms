<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * The certificate's own participant, Super Admin, or a Regional Admin
     * for either the participant's region or the training's region — the
     * same people who can already see it listed in Summary.
     */
    public function download(Request $request, Certificate $certificate): StreamedResponse
    {
        $user = $request->user();
        $certificate->loadMissing('user', 'trainingRequest');

        $canView = $certificate->user_id === $user->id
            || $user->isSuperAdmin()
            || ($user->isAdmin() && $user->region !== null
                && in_array($user->region, [$certificate->user?->region, $certificate->trainingRequest?->region], true));

        abort_unless($canView, 403);

        // Certificates issued before they moved to the private disk may
        // still be sitting on the public one.
        $disk = collect(['local', 'public'])->first(fn (string $disk) => Storage::disk($disk)->exists($certificate->file_path));

        abort_unless($disk, 404);

        return Storage::disk($disk)->response($certificate->file_path);
    }
}
