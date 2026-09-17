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
     * Sends one certificate PDF to the browser (opens in a new tab).
     *
     * Certificate files are private — they can't be opened by typing a
     * /storage/... address — so every certificate link in the app points
     * here instead. Allowed viewers: the certificate's own participant,
     * the Super Admin, or a Regional Admin for either the participant's
     * region or the training's region — the same people who can already see
     * it listed in Summary. Anyone else gets 403 (forbidden); a missing file
     * gets 404 (not found).
     */
    public function download(Request $request, Certificate $certificate): StreamedResponse
    {
        $user = $request->user();
        $certificate->loadMissing('user', 'trainingRequest');

        // Who is allowed to open this certificate (see the list above).
        $canView = $certificate->user_id === $user->id
            || $user->isSuperAdmin()
            || ($user->isAdmin() && $user->region !== null
                && in_array($user->region, [$certificate->user?->region, $certificate->trainingRequest?->region], true));

        abort_unless($canView, 403);

        // New certificates are saved on the private disk (storage/app/private).
        // Certificates issued before that change may still be sitting on the
        // public disk (public/storage), so look in both, private first.
        $disk = collect(['local', 'public'])->first(fn (string $disk) => Storage::disk($disk)->exists($certificate->file_path));

        abort_unless($disk, 404);

        return Storage::disk($disk)->response($certificate->file_path);
    }
}
