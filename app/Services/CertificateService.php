<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\TrainingRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService
{
    /**
     * Canonical Luzon → Visayas → Mindanao → Central ordering used to number
     * graduates on a multi-region training's certificates (see
     * config/regions.php for why NIR has no participants yet but is kept
     * here for when that mapping exists).
     */
    private const REGION_ORDER = [
        'Region I', 'Region II', 'Region III', 'Region IV-A', 'MIMAROPA', 'Region V', 'NCR', 'CAR',
        'Region VI', 'Region VII', 'Region VIII', 'NIR',
        'Region IX', 'Region X', 'Region XI', 'Region XII', 'Region XIII', 'BARMM',
        'OCD Central',
    ];

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

        $newParticipants = $trainingRequest->effectiveParticipants()
            ->reject(fn (User $participant) => $existingUserIds->contains($participant->id));

        if ($newParticipants->isEmpty()) {
            return collect();
        }

        // Batch/run number: how many times this exact training curriculum
        // has been conducted before, counting historical ATAR-imported rows
        // too since those are genuinely earlier runs of the same training.
        $batchNumber = TrainingRequest::where('training_slug', $trainingRequest->training_slug)
            ->where('status', TrainingRequest::STATUS_COMPLETED)
            ->where('preferred_date', '<', $trainingRequest->preferred_date)
            ->count() + 1;

        // The graduate's certificate number reflects their position in the
        // full class roster sorted by home region (Luzon → Visayas →
        // Mindanao → Central), then name — this collapses to plain
        // alphabetical-by-name whenever every participant shares one region,
        // since the region-rank term is then constant for everyone.
        $orderedRoster = $trainingRequest->effectiveParticipants()
            ->sortBy(fn (User $participant) => sprintf(
                '%02d-%s',
                $this->regionRank($participant->region),
                $participant->name
            ))
            ->values();

        return $newParticipants
            ->map(function (User $participant) use ($trainingRequest, $type, $issuedOn, $batchNumber, $orderedRoster) {
                $sequence = $orderedRoster->search(fn (User $p) => $p->id === $participant->id) + 1;

                return $this->issue($trainingRequest, $participant, $type, $issuedOn, $batchNumber, $sequence);
            })
            ->values();
    }

    private function issue(TrainingRequest $trainingRequest, User $participant, string $type, Carbon $issuedOn, int $batchNumber, int $sequence): Certificate
    {
        $code = $this->generateCode($trainingRequest->training_title, $trainingRequest->region, $batchNumber, (int) $issuedOn->format('Y'), $sequence);

        $pdf = Pdf::loadView('pdf.participant-certificate', [
            'participant' => $participant,
            'trainingRequest' => $trainingRequest,
            'type' => $type,
            'issuedOn' => $issuedOn,
            'code' => $code,
        ])->setPaper('a4', 'landscape');

        // The code itself can contain spaces (e.g. "OCD CENTRAL"), which
        // isn't safe as a literal filename/URL segment — the stored `code`
        // keeps the human-readable format, the file path doesn't. Stored on
        // the private disk: codes are sequential and guessable, so a public
        // file would let anyone enumerate every participant's certificate.
        // Served through CertificateController::download() instead.
        $path = 'certificates/'.Str::slug($code).'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        return Certificate::create([
            'training_request_id' => $trainingRequest->id,
            'user_id' => $participant->id,
            'code' => $code,
            'type' => $type,
            'file_path' => $path,
            'issued_on' => $issuedOn,
        ]);
    }

    /**
     * The ABBREV-REGION-BATCH-YEAR-SEQUENCE format every issued certificate
     * uses (see issue() above) — extracted so anywhere that needs a code in
     * this exact pattern without actually issuing a certificate (e.g. the
     * ATAR Declaration of Graduates annex, for a name with no certificate on
     * file yet) can reuse the real format instead of re-deriving it.
     */
    public function generateCode(string $title, ?string $region, int $batchNumber, int $year, int $sequence): string
    {
        return sprintf(
            '%s-%s-%d-%d-%d',
            $this->abbreviateTitle($title),
            mb_strtoupper($region ?: 'N/A'),
            $batchNumber,
            $year,
            $sequence
        );
    }

    /**
     * Position of a region in the canonical Luzon/Visayas/Mindanao/Central
     * ordering — an unrecognized or missing region sorts after all of them.
     */
    private function regionRank(?string $region): int
    {
        $index = array_search($region, self::REGION_ORDER, true);

        return $index === false ? count(self::REGION_ORDER) : $index;
    }

    /**
     * Auto-generates a short code from a training title: if it ends in a
     * parenthetical all-caps acronym (e.g. "Incident Command System (ICS)"),
     * that's used directly; otherwise the first letter of each significant
     * word (small connector words dropped) is used instead — e.g.
     * "Community-Based Disaster Risk Reduction and Management" -> "CBDRRM".
     */
    private function abbreviateTitle(string $title): string
    {
        if (preg_match('/\(([A-Z]{2,})\)\s*$/', $title, $matches)) {
            return $matches[1];
        }

        $stopWords = ['and', 'of', 'the', 'for', 'to', 'in', 'on', 'a', 'an', 'with'];

        return collect(preg_split('/[\s\-]+/', $title))
            ->reject(fn ($word) => $word === '' || in_array(mb_strtolower($word), $stopWords, true))
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }
}
