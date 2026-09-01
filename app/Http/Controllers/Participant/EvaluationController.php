<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\ParticipantEvaluation;
use App\Models\TrainingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    const MAX_MODULE_ROWS = 8;

    const RATING_SCALE = [
        1 => '1 (Poor)',
        2 => '2 (Unsatisfactory)',
        3 => '3 (Satisfactory)',
        4 => '4 (Very Satisfactory)',
        5 => '5 (Outstanding)',
    ];

    public function edit(Request $request, TrainingRequest $trainingRequest): View
    {
        $user = $this->authorize($request, $trainingRequest);

        $trainingRequest->load('instructors');

        $existing = $trainingRequest->participantEvaluations()->where('user_id', $user->id)->first();

        $moduleRows = $existing?->module_ratings ?? collect($trainingRequest->trainingEvaluation?->module_ratings ?? [])
            ->map(fn ($row) => ['module' => $row['module'] ?? '', 'module_rating' => '', 'trainer_rating' => '', 'comment' => ''])
            ->all();
        $moduleRows = array_pad($moduleRows, self::MAX_MODULE_ROWS, ['module' => '', 'module_rating' => '', 'trainer_rating' => '', 'comment' => '']);

        $existingInstructorRatings = collect($existing?->instructor_ratings ?? [])->keyBy('instructor_id');

        return view('participant.training-requests.evaluation', [
            'trainingRequest' => $trainingRequest,
            'existing' => $existing,
            'moduleRows' => $moduleRows,
            'existingInstructorRatings' => $existingInstructorRatings,
            'ratingScale' => self::RATING_SCALE,
        ]);
    }

    public function update(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $user = $this->authorize($request, $trainingRequest);

        $assignedInstructorIds = $trainingRequest->instructors()->pluck('instructors.id')->all();

        $validated = $request->validate([
            'module' => ['nullable', 'array'],
            'module.*' => ['nullable', 'string', 'max:255'],
            'module_rating' => ['nullable', 'array'],
            'module_rating.*' => ['nullable', 'integer', 'min:1', 'max:5'],
            'trainer_rating' => ['nullable', 'array'],
            'trainer_rating.*' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'array'],
            'comment.*' => ['nullable', 'string', 'max:1000'],
            'instructor_rating' => ['nullable', 'array'],
            'instructor_rating.*' => ['nullable', 'integer', 'min:1', 'max:5'],
            'instructor_comment' => ['nullable', 'array'],
            'instructor_comment.*' => ['nullable', 'string', 'max:1000'],
            'overall_comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $rowCount = max(
            count($validated['module'] ?? []),
            count($validated['module_rating'] ?? []),
            count($validated['trainer_rating'] ?? []),
            count($validated['comment'] ?? []),
        );

        $moduleRatings = collect(range(0, $rowCount - 1))
            ->map(fn ($i) => [
                'module' => $validated['module'][$i] ?? null,
                'module_rating' => $validated['module_rating'][$i] ?? null,
                'trainer_rating' => $validated['trainer_rating'][$i] ?? null,
                'comment' => $validated['comment'][$i] ?? null,
            ])
            ->filter(fn ($row) => filled($row['module']) || filled($row['module_rating']) || filled($row['trainer_rating']) || filled($row['comment']))
            ->values()
            ->map(fn ($row, $i) => [...$row, 'module' => $row['module'] ?: 'Module '.($i + 1)])
            ->all();

        $instructorRatings = collect($assignedInstructorIds)
            ->map(fn ($instructorId) => [
                'instructor_id' => $instructorId,
                'rating' => $validated['instructor_rating'][$instructorId] ?? null,
                'comment' => $validated['instructor_comment'][$instructorId] ?? null,
            ])
            ->filter(fn ($row) => filled($row['rating']) || filled($row['comment']))
            ->values()
            ->all();

        $trainingRequest->participantEvaluations()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'module_ratings' => $moduleRatings,
                'instructor_ratings' => $instructorRatings,
                'overall_comments' => $validated['overall_comments'] ?? null,
            ],
        );

        $this->reflectInstructorRatings($assignedInstructorIds);
        Instructor::reflectRatingForTraining($trainingRequest->training_title);

        return Redirect::route('training-requests.show', $trainingRequest)->with('status', 'Thanks — your evaluation has been submitted.');
    }

    /**
     * Only a participant/owner of a completed training may evaluate it.
     */
    private function authorize(Request $request, TrainingRequest $trainingRequest)
    {
        $user = $request->user();

        $isOwner = $trainingRequest->user_id === $user->id;
        $isParticipant = $trainingRequest->participants->contains($user->id);

        abort_unless($isOwner || $isParticipant, 403);
        abort_unless($trainingRequest->status === TrainingRequest::STATUS_COMPLETED, 403);

        return $user;
    }

    /**
     * Roll every participant's rating of each instructor, across every
     * training they've been assigned to, into that instructor's profile.
     */
    private function reflectInstructorRatings(array $instructorIds): void
    {
        foreach ($instructorIds as $instructorId) {
            $ratings = ParticipantEvaluation::get()
                ->pluck('instructor_ratings')
                ->filter()
                ->flatten(1)
                ->where('instructor_id', $instructorId)
                ->pluck('rating')
                ->filter(fn ($r) => is_numeric($r));

            if ($ratings->isEmpty()) {
                continue;
            }

            Instructor::whereKey($instructorId)->update(['rating' => round($ratings->avg(), 2)]);
        }
    }
}
