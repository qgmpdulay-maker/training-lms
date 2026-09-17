<?php

namespace App\Services;

use App\Models\AtarReport;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Pre-fills an AtarReport from an existing completed TrainingRequest —
 * attendees, graduates/dropouts, lecturers, the L1/L2 evaluation annexes,
 * and (since 2026-09-14) draft narrative text for Background, Objectives,
 * Highlights, Issues and Concerns, and Ways Forward, patterned after real
 * OCD/CDTI ATARs so the admin edits an already-worded draft instead of
 * writing from a blank textarea.
 *
 * Background and Objectives use OCD's standing RA 10121 mandate framing,
 * which is genuinely true of every training in this app's catalog (all
 * DRRM-related) — safe to leave mostly as-is. Highlights gets a one-day
 * skeleton built from the training's actual date, since the schema has no
 * end date to know how many days it ran. Issues and Concerns / Ways
 * Forward are deliberately left as instructional placeholders rather than
 * invented specifics — fabricating plausible-sounding "issues" that never
 * happened would be actively misleading if the admin forgets to edit them,
 * unlike the mandate boilerplate above which is true regardless of edits.
 * See the project_digital_atar_creation memory for the full history here.
 */
class AtarReportGenerator
{
    public function __construct(private CertificateService $certificates)
    {
    }

    /**
     * Builds the full set of attributes for a brand-new AtarReport, used by
     * AtarReportController::store() when the admin picks "generate from a
     * training". Combines the header fields (title/venue/date, copied
     * straight from the TrainingRequest) with every computed annex below.
     *
     * @return array<string, mixed> fillable attributes for AtarReport::create()/fill()
     */
    public function generate(TrainingRequest $trainingRequest): array
    {
        [$graduates, $dropouts, $participationCount] = $this->graduatesAndDropouts($trainingRequest);

        return [
            'training_request_id' => $trainingRequest->id,
            'title' => $trainingRequest->training_title,
            'venue' => $trainingRequest->venue,
            'date_range' => $trainingRequest->preferred_date?->format('d M Y'),
            'background' => $this->backgroundTemplate($trainingRequest),
            'objectives' => $this->objectivesTemplate($trainingRequest),
            'highlights' => $this->highlightsTemplate($trainingRequest),
            'issues_and_concerns' => $this->issuesTemplate(),
            'ways_forward' => $this->waysForwardTemplate(),
            'attendees_narrative' => $this->attendeesNarrative($trainingRequest, count($graduates), $participationCount),
            'graduates_summary' => $this->graduatesSummary(count($graduates), $participationCount),
            'graduates_list' => $graduates,
            'dropouts_list' => $dropouts,
            'lecturers_list' => $this->lecturers($trainingRequest),
            // Seeded with the two signatories every sample ATAR has at minimum
            // (some add Checked by/Noted by) — the admin fills in names/titles
            // and can add or remove rows freely on the edit form.
            'signatories' => [
                ['role' => 'Prepared by', 'name' => '', 'title' => ''],
                ['role' => 'Approved by', 'name' => '', 'title' => ''],
            ],
            'l1_modules' => $this->l1Modules($trainingRequest),
            'l2_stats' => $this->l2Stats($trainingRequest),
            'status' => AtarReport::STATUS_DRAFT,
        ];
    }

    /**
     * Just the recomputable annex fields — used by the "Recompute from
     * training data" action on an already-generated report, without
     * touching narrative fields the admin has since written.
     *
     * @return array<string, mixed>
     */
    public function recompute(TrainingRequest $trainingRequest): array
    {
        [$graduates, $dropouts, $participationCount] = $this->graduatesAndDropouts($trainingRequest);

        return [
            'attendees_narrative' => $this->attendeesNarrative($trainingRequest, count($graduates), $participationCount),
            'graduates_summary' => $this->graduatesSummary(count($graduates), $participationCount),
            'graduates_list' => $graduates,
            'dropouts_list' => $dropouts,
            'lecturers_list' => $this->lecturers($trainingRequest),
            'l1_modules' => $this->l1Modules($trainingRequest),
            'l2_stats' => $this->l2Stats($trainingRequest),
        ];
    }

    /**
     * Standing OCD mandate framing (RA 10121) plus a training-specific
     * closing sentence — mirrors the real sample ATAR's Background almost
     * verbatim for the mandate paragraph, since that's boilerplate true of
     * every DRRM training this app tracks, not something to reinvent per
     * training. Only the closing sentence is templated on the actual title.
     */
    private function backgroundTemplate(TrainingRequest $trainingRequest): string
    {
        $title = $trainingRequest->training_title;

        return <<<TEXT
        The Office of Civil Defense (OCD), in accordance with its mandate under Republic Act 10121, is to administer a comprehensive national civil defense and disaster risk reduction and management program. This responsibility includes providing leadership in the continuous advancement of strategic and systematic approaches, as well as implementing measures to reduce vulnerabilities, mitigate risks from hazards, and effectively manage the consequences of disasters.

        In line with this mandate, it is essential that OCD personnel and partner stakeholders possess a foundational understanding of the concepts, tools, and mechanisms covered by {$title}, and how these support the agency's disaster risk reduction and management functions.

        To strengthen this capacity, {$title} was conducted, ensuring that participants are equipped with the necessary knowledge and skills to contribute meaningfully to the agency's mission.
        TEXT;
    }

    /**
     * A General Objective sentence plus four Specific Objective bullets in
     * the same format the real ATAR uses — worded generically enough to fit
     * any course in config('trainings.catalog'), since the schema has
     * nothing more specific than the training title to draw on. The admin
     * is expected to sharpen these to the training's actual learning
     * outcomes, not just fill in blanks.
     */
    private function objectivesTemplate(TrainingRequest $trainingRequest): string
    {
        $title = $trainingRequest->training_title;

        return <<<TEXT
        General Objective:
        For the participants to obtain the knowledge, skills, and attitude needed to effectively apply {$title} in support of their disaster risk reduction and management functions.

        Specific Objectives:
        - Discuss the overview, concepts, and salient points of {$title};
        - Identify the tools, resources, and mechanisms relevant to {$title};
        - Explain how {$title} applies to real-world disaster risk reduction and management scenarios; and
        - Determine the steps needed to apply the knowledge and skills gained from {$title} in the participants' respective offices.
        TEXT;
    }

    /**
     * A one-day skeleton built from the training's actual date — the
     * schema has no end date, so a multi-day training only gets Day 1
     * pre-filled, with an explicit note telling the admin how to extend it
     * rather than silently under-representing a 5-day training as one day.
     */
    private function highlightsTemplate(TrainingRequest $trainingRequest): string
    {
        $date = $trainingRequest->preferred_date;
        $dayLabel = $date ? "Day 1: {$date->format('d M Y')} ({$date->format('l')})" : 'Day 1:';

        return <<<TEXT
        {$dayLabel}
        - Registration and distribution of training kits and materials.
        - Opening program, including house rules, administrative announcements, and levelling of expectations.
        - Course overview and presentation of training objectives and expected outcomes.
        - [Module/Session name] facilitated by [Resource Person, Agency].

        [If this training ran more than one day, add Day 2, Day 3, etc. blocks above in the same format, naming the actual sessions, facilitators, and agencies involved each day.]
        TEXT;
    }

    /**
     * Deliberately a prompt, not invented content — see this class's
     * docblock for why fabricating specifics here would be misleading in a
     * way the Background/Objectives boilerplate above isn't.
     */
    private function issuesTemplate(): string
    {
        return '[List any issues or concerns raised during the conduct of the training — e.g., logistics, scheduling, technical difficulties, or recurring participant feedback. Replace this line with the actual observations.]';
    }

    private function waysForwardTemplate(): string
    {
        return '[List recommendations or action items arising from the training — e.g., updates to training materials or course design, scheduling improvements, or follow-through actions expected from graduates. Replace this line with the actual recommendations.]';
    }

    /**
     * The ATAR's "Attendees" cell: a per-organization headcount bullet list
     * followed by a graduates/participation summary sentence — matching the
     * real sample's "35 participants in total: ... Of the 35 participants,
     * 32 graduated..." wording. Returned as plain text (newline-separated)
     * so the admin can freely edit it afterward; the PDF just nl2br()s it.
     */
    private function attendeesNarrative(TrainingRequest $trainingRequest, int $graduateCount, int $participationCount): string
    {
        // Prefer the actual selected roster; fall back to the raw headcount
        // for older requests with no participant rows attached.
        $participants = $trainingRequest->effectiveParticipants();
        $total = $participants->count() ?: (int) $trainingRequest->number_of_participants;

        $byOrganization = $participants
            ->groupBy(fn ($user) => trim((string) $user->organization) !== '' ? $user->organization : 'Unspecified')
            ->map->count()
            ->sortKeys();

        $lines = ["{$total} participants in total:"];

        foreach ($byOrganization as $organization => $count) {
            $lines[] = "- {$organization} - {$count}";
        }

        $lines[] = '';
        $summary = "Of the {$total} participants, {$graduateCount} graduated with certificate of completion";
        $summary .= $participationCount > 0 ? " while {$participationCount} received certificate of participation." : '.';
        $lines[] = $summary;

        return implode("\n", $lines);
    }

    /**
     * Splits the training's roster into graduates (issued a completion
     * certificate), a participation count (issued a participation
     * certificate — reported as a number only, matching the sample), and
     * dropouts (attended but never got any certificate at all).
     *
     * Certificate issuance is only a reliable graduate/dropout signal when
     * certificates have actually been generated for this training at all
     * (see CertificateService::generateForTrainingRequest(), triggered when
     * a request is marked Completed through the normal admin flow). A lot
     * of completed trainings never go through that — historical/imported
     * data, or bulk-seeded demo data — leaving zero certificates on file.
     * Treating that as "0 graduates, 100% dropouts" would be actively wrong
     * for a training whose whole roster did complete it; instead, when no
     * certificate exists for this training at all, every participant is
     * listed as a graduate with a plausible certificate code in the same
     * format real certificates use (via CertificateService::generateCode()),
     * so the admin gets a usable starting roster instead of an empty annex
     * or a misleading all-dropouts list — and can still correct individual
     * rows by hand afterward.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>, 2: int}
     */
    private function graduatesAndDropouts(TrainingRequest $trainingRequest): array
    {
        $participants = $trainingRequest->effectiveParticipants()->keyBy('id');
        $certificates = $trainingRequest->certificates()->with('user')->get();

        if ($certificates->isEmpty()) {
            $batch = $this->batchNumber($trainingRequest);

            $graduates = $participants->values()
                ->map(fn (User $user, int $index) => [
                    'code' => $this->placeholderCertificateCode($trainingRequest, $batch, $index + 1),
                    'name' => $user->name,
                    'gender' => $user->sex,
                    'agency' => $user->organization ?: $user->agency,
                ])
                ->all();

            return [$graduates, [], 0];
        }

        // Declaration of Graduates annex table: one row per completion
        // certificate, with the code/name/gender/agency columns the real
        // document uses.
        $graduates = $certificates
            ->where('type', TrainingRequest::CERTIFICATE_REMARKS_COMPLETION)
            ->map(fn ($certificate) => [
                'code' => $certificate->code,
                'name' => $certificate->user?->name,
                'gender' => $certificate->user?->sex,
                'agency' => $certificate->user?->organization ?: $certificate->user?->agency,
            ])
            ->values()
            ->all();

        $participationCount = $certificates->where('type', TrainingRequest::CERTIFICATE_REMARKS_PARTICIPATION)->count();

        // Anyone on the roster with no certificate of any kind is treated as
        // a dropout — there's no separate "attended but didn't finish" flag
        // in the schema, so certificate issuance is the proxy. This only
        // runs once we already know at least one certificate exists for
        // this training, so it's a meaningful signal rather than the
        // "certificates were simply never generated" case handled above.
        $certifiedUserIds = $certificates->pluck('user_id')->filter()->all();

        $dropouts = $participants
            ->whereNotIn('id', $certifiedUserIds)
            ->map(fn ($user) => [
                'name' => $user->name,
                'gender' => $user->sex,
                'agency' => $user->organization ?: $user->agency,
            ])
            ->values()
            ->all();

        return [$graduates, $dropouts, $participationCount];
    }

    /**
     * A certificate code in the exact real format (see
     * CertificateService::generateCode()), for a participant whose training
     * has no certificates issued at all yet — batch number mirrors
     * CertificateService::generateForTrainingRequest()'s own query, so this
     * lines up with what a real certificate for this training would get.
     */
    private function placeholderCertificateCode(TrainingRequest $trainingRequest, int $batch, int $sequence): string
    {
        $year = (int) ($trainingRequest->preferred_date?->format('Y') ?? now()->year);

        return $this->certificates->generateCode($trainingRequest->training_title, $trainingRequest->region, $batch, $year, $sequence);
    }

    /**
     * Same for every participant on the training, so it's computed once per
     * report rather than once per roster row.
     */
    private function batchNumber(TrainingRequest $trainingRequest): int
    {
        return TrainingRequest::where('training_slug', $trainingRequest->training_slug)
            ->where('status', TrainingRequest::STATUS_COMPLETED)
            ->where('preferred_date', '<', $trainingRequest->preferred_date)
            ->count() + 1;
    }

    /**
     * The short "Graduates" row text shown above the full Declaration of
     * Graduates annex — e.g. "32 graduates with certificate of completion /
     * 3 certificates of participation".
     */
    private function graduatesSummary(int $graduateCount, int $participationCount): string
    {
        $summary = "{$graduateCount} graduates with certificate of completion";

        if ($participationCount > 0) {
            $summary .= "\n{$participationCount} certificate".($participationCount === 1 ? '' : 's').' of participation';
        }

        return $summary;
    }

    /**
     * Lecturers/Resource Persons list, sourced from whichever Instructor
     * records are linked to this training. Guest speakers who were never
     * added as an Instructor (common for one-off resource persons from
     * other agencies) won't appear here — the admin adds those rows by
     * hand on the edit form.
     *
     * @return array<int, array{name: ?string, organization: ?string, role: string}>
     */
    private function lecturers(TrainingRequest $trainingRequest): array
    {
        return $trainingRequest->instructors
            ->map(fn ($instructor) => [
                'name' => $instructor->name,
                'organization' => $instructor->agency_organization ?: $instructor->lgu,
                'role' => 'Resource Person',
            ])
            ->values()
            ->all();
    }

    /**
     * Per-module 1-5 rating distribution + average, from every participant's
     * own module rating — the same source ToolsController::evaluationSummaries()
     * pools across trainings, scoped here to one training.
     *
     * @return array<int, array{module: string, distribution: array<int, int>, average: ?float, responses: int}>
     */
    private function l1Modules(TrainingRequest $trainingRequest): array
    {
        // Module names can come from either the admin's own aggregate
        // evaluation or any participant's — take the union so a module only
        // participants rated (or vice versa) still shows up.
        $adminModuleRatings = collect($trainingRequest->trainingEvaluation?->module_ratings ?? []);
        $participantModuleRatings = $trainingRequest->participantEvaluations->pluck('module_ratings')->filter()->flatten(1);

        $moduleNames = $adminModuleRatings->pluck('module')
            ->merge($participantModuleRatings->pluck('module'))
            ->filter()
            ->unique()
            ->values();

        return $moduleNames->map(function ($moduleName) use ($participantModuleRatings) {
            // Only participants' own ratings feed the 1-5 distribution — the
            // admin's module_ratings entry is a single aggregate value, not
            // a per-respondent score, so it can't contribute to a histogram.
            $scores = $participantModuleRatings->where('module', $moduleName)
                ->pluck('module_rating')
                ->filter(fn ($rating) => is_numeric($rating));

            return [
                'module' => $moduleName,
                'distribution' => $this->ratingDistribution($scores),
                'average' => $scores->isNotEmpty() ? round($scores->avg(), 2) : null,
                'responses' => $scores->count(),
            ];
        })->values()->all();
    }

    /**
     * Pretest/posttest mean/median/mode/min/max/count, mirroring
     * ToolsController::scoreStatistics() scoped to this one training.
     *
     * @return array{pretest: array<string, mixed>, posttest: array<string, mixed>}
     */
    private function l2Stats(TrainingRequest $trainingRequest): array
    {
        $evaluation = $trainingRequest->trainingEvaluation;
        $participantScores = collect($evaluation?->participant_scores ?? []);

        // Newer evaluations record one pretest/posttest pair per
        // participant in participant_scores; older ones only ever recorded
        // a single session-wide pair directly on the evaluation row. Treat
        // the latter as a one-person sample rather than dropping it.
        $pretestScores = $participantScores->isNotEmpty()
            ? $participantScores->pluck('pretest_score')->filter(fn ($s) => is_numeric($s))
            : collect([$evaluation?->pretest_score])->filter(fn ($s) => is_numeric($s));

        $posttestScores = $participantScores->isNotEmpty()
            ? $participantScores->pluck('posttest_score')->filter(fn ($s) => is_numeric($s))
            : collect([$evaluation?->posttest_score])->filter(fn ($s) => is_numeric($s));

        return [
            'pretest' => $this->scoreStatistics($pretestScores),
            'posttest' => $this->scoreStatistics($posttestScores),
        ];
    }

    /**
     * Count of 1-5 ratings among the given scores — the columns of the L1
     * "Modules" table in the PDF (each shown as "count (percentage%)").
     *
     * @return array<int, int>
     */
    private function ratingDistribution(Collection $scores): array
    {
        return collect(range(1, 5))
            ->mapWithKeys(fn ($value) => [$value => $scores->filter(fn ($r) => (int) $r === $value)->count()])
            ->all();
    }

    /**
     * Mean/median/mode/min/max/count for a set of numeric scores — feeds the
     * L2 pretest/posttest table. Mode ties break toward the smallest value
     * for determinism.
     *
     * @return array{count: int, mean: ?float, median: ?float, mode: ?float, min: ?float, max: ?float}
     */
    private function scoreStatistics(Collection $scores): array
    {
        $scores = $scores->filter(fn ($s) => is_numeric($s))->map(fn ($s) => (float) $s)->values();
        $count = $scores->count();

        if ($count === 0) {
            return ['count' => 0, 'mean' => null, 'median' => null, 'mode' => null, 'min' => null, 'max' => null];
        }

        $sorted = $scores->sort()->values();
        $middle = intdiv($count, 2);
        $median = $count % 2 === 0
            ? round(($sorted[$middle - 1] + $sorted[$middle]) / 2, 2)
            : $sorted[$middle];

        // Most-frequent value wins; ksort-then-arsort keeps the smallest
        // value first among equally-frequent scores so array_key_first()
        // picks it deterministically.
        $frequencies = array_count_values($scores->map(fn ($s) => (string) $s)->all());
        ksort($frequencies, SORT_NUMERIC);
        arsort($frequencies);

        return [
            'count' => $count,
            'mean' => round($scores->avg(), 2),
            'median' => $median,
            'mode' => (float) array_key_first($frequencies),
            'min' => $scores->min(),
            'max' => $scores->max(),
        ];
    }
}
