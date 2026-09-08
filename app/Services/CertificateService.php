<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\TrainingRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    /**
     * Issue a certificate PDF for every participant on this training who
     * doesn't already have one. Safe to call repeatedly — re-saving an
     * already-completed request, or adding a participant afterward — since
     * existing certificates are never regenerated or overwritten.
     *
     * @return Collection<int, Certificate> newly issued certificates
     */
    public function generateForTrainingRequest(TrainingRequest $trainingRequest): Collection
    {
        $type = $trainingRequest->certificate_remarks ?: TrainingRequest::CERTIFICATE_REMARKS_COMPLETION;
        $issuedOn = $trainingRequest->preferred_date;
        $existingUserIds = $trainingRequest->certificates()->pluck('user_id');

        return $trainingRequest->effectiveParticipants()
            ->reject(fn (User $participant) => $existingUserIds->contains($participant->id))
            ->map(fn (User $participant) => $this->issue($trainingRequest, $participant, $type, $issuedOn))
            ->values();
    }

    private function issue(TrainingRequest $trainingRequest, User $participant, string $type, Carbon $issuedOn): Certificate
    {
        $code = sprintf('OCD-%s-%04d-%04d', $issuedOn->format('Y'), $trainingRequest->id, $participant->id);

        $pdf = Pdf::loadView('pdf.participant-certificate', [
            'participant' => $participant,
            'trainingRequest' => $trainingRequest,
            'type' => $type,
            'issuedOn' => $issuedOn,
            'code' => $code,
        ])->setPaper('a4', 'landscape');

        $path = "certificates/{$code}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        return Certificate::create([
            'training_request_id' => $trainingRequest->id,
            'user_id' => $participant->id,
            'code' => $code,
            'type' => $type,
            'file_path' => $path,
            'issued_on' => $issuedOn,
        ]);
    }
}
