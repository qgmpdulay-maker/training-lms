<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TnaForm;
use App\Models\TnaQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Editing the Training Needs Assessment questionnaires — Super Admin only.
 *
 * There is one form per participant type (Academe, N&RDRRMC, LGU, Volunteer)
 * plus a fallback for types that have none, and the Super Admin can add types
 * of their own. Everything is stored in the tna_* tables; the questions used to
 * live in config files, which meant a developer had to change them.
 *
 * Note for whoever edits the four official tools: those were digitizations of
 * CDTI's own instruments, so a reworded question no longer matches the paper
 * form it came from. That's a deliberate decision (2026-10-19), not an
 * oversight — but it is worth knowing before changing their wording.
 */
class TnaFormController extends Controller
{
    public function index(): View
    {
        return view('admin.tna-forms.index', [
            'forms' => TnaForm::withCount('questions')->orderBy('label')->get(),
            'participantTypes' => $this->participantTypes(),
        ]);
    }

    public function create(): View
    {
        return view('admin.tna-forms.create', [
            'participantTypes' => $this->participantTypes(),
            'takenTypes' => TnaForm::whereNotNull('participant_type')->pluck('participant_type')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'participant_type' => ['nullable', 'string', 'max:255', Rule::unique('tna_forms', 'participant_type')],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $form = TnaForm::create([
            'key' => $this->uniqueKey($validated['label']),
            'label' => $validated['label'],
            'participant_type' => $validated['participant_type'] ?? null,
            'description' => $validated['description'] ?? null,
            // New forms start inactive: an empty questionnaire should never be
            // what a participant is handed, so it goes live once it has questions.
            'is_active' => false,
        ]);

        return Redirect::route('admin.tna-forms.edit', $form)
            ->with('status', "{$form->label} created — add its questions below.");
    }

    public function edit(Request $request, TnaForm $tnaForm): View
    {
        $tnaForm->load(['questions' => fn ($query) => $query->with('options')]);

        return view('admin.tna-forms.edit', [
            'form' => $tnaForm,
            'participantTypes' => $this->participantTypes(),
            'kindLabels' => TnaQuestion::$kindLabels,
            'categories' => collect(config('trainings.catalog'))->pluck('category')->filter()->unique()->sort()->values()->all(),
        ]);
    }

    public function update(Request $request, TnaForm $tnaForm): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'participant_type' => ['nullable', 'string', 'max:255', Rule::unique('tna_forms', 'participant_type')->ignore($tnaForm)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tnaForm->update([
            'label' => $validated['label'],
            'participant_type' => $validated['participant_type'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return Redirect::route('admin.tna-forms.edit', $tnaForm)->with('status', 'Assessment details saved.');
    }

    public function destroy(TnaForm $tnaForm): RedirectResponse
    {
        // Submissions keep their own copy of the answers (TrainingNeedsAssessment
        // stores the question text with each answer), so deleting a form never
        // takes away what people already submitted.
        $label = $tnaForm->label;
        $tnaForm->delete();

        return Redirect::route('admin.tna-forms.index')->with('status', "{$label} deleted.");
    }

    public function storeQuestion(Request $request, TnaForm $tnaForm): RedirectResponse
    {
        $validated = $this->validateQuestion($request);

        $question = $tnaForm->questions()->create([
            'position' => (int) $tnaForm->questions()->max('position') + 1,
            'group_label' => $validated['group_label'] ?? null,
            'prompt' => $validated['prompt'],
            'help_text' => $validated['help_text'] ?? null,
            'kind' => $validated['kind'],
            'is_required' => (bool) ($validated['is_required'] ?? false),
        ]);

        $this->syncOptions($question, $validated['options'] ?? []);

        return Redirect::route('admin.tna-forms.edit', $tnaForm)->with('status', 'Question added.');
    }

    public function updateQuestion(Request $request, TnaQuestion $tnaQuestion): RedirectResponse
    {
        $validated = $this->validateQuestion($request);

        $tnaQuestion->update([
            'group_label' => $validated['group_label'] ?? null,
            'prompt' => $validated['prompt'],
            'help_text' => $validated['help_text'] ?? null,
            'kind' => $validated['kind'],
            'is_required' => (bool) ($validated['is_required'] ?? false),
        ]);

        $this->syncOptions($tnaQuestion, $validated['options'] ?? []);

        return Redirect::route('admin.tna-forms.edit', $tnaQuestion->tna_form_id)->with('status', 'Question saved.');
    }

    public function destroyQuestion(TnaQuestion $tnaQuestion): RedirectResponse
    {
        $formId = $tnaQuestion->tna_form_id;
        $tnaQuestion->delete();

        return Redirect::route('admin.tna-forms.edit', $formId)->with('status', 'Question removed.');
    }

    /**
     * Swaps a question with its neighbour. Order matters on the participant's
     * side, and dragging rows is not worth the complexity for a list this long.
     */
    public function moveQuestion(Request $request, TnaQuestion $tnaQuestion): RedirectResponse
    {
        $direction = $request->input('direction') === 'up' ? 'up' : 'down';

        $neighbour = TnaQuestion::where('tna_form_id', $tnaQuestion->tna_form_id)
            ->when($direction === 'up',
                fn ($query) => $query->where('position', '<', $tnaQuestion->position)->orderByDesc('position'),
                fn ($query) => $query->where('position', '>', $tnaQuestion->position)->orderBy('position'),
            )
            ->first();

        if ($neighbour) {
            $position = $tnaQuestion->position;
            $tnaQuestion->update(['position' => $neighbour->position]);
            $neighbour->update(['position' => $position]);
        }

        return Redirect::route('admin.tna-forms.edit', $tnaQuestion->tna_form_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'prompt' => ['required', 'string', 'max:2000'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'group_label' => ['nullable', 'string', 'max:255'],
            'kind' => ['required', 'string', Rule::in(array_keys(TnaQuestion::$kindLabels))],
            'is_required' => ['nullable', 'boolean'],
            'options' => ['array'],
            'options.*.label' => ['nullable', 'string', 'max:2000'],
            'options.*.category' => ['nullable', 'string', 'max:255'],
            'options.*.points' => ['nullable', 'integer', 'min:0', 'max:100'],
            'options.*.hours' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }

    /**
     * Options are submitted as the full list for the question, so they're
     * rewritten wholesale — blank labels are how a row is deleted.
     *
     * @param  array<int, array<string, mixed>>  $options
     */
    private function syncOptions(TnaQuestion $question, array $options): void
    {
        $question->options()->delete();

        if (! $question->expectsOptions()) {
            return;
        }

        $position = 0;

        foreach ($options as $option) {
            if (blank($option['label'] ?? null)) {
                continue;
            }

            $question->options()->create([
                'position' => ++$position,
                'label' => $option['label'],
                'category' => $option['category'] ?: null,
                'points' => $option['points'] !== null && $option['points'] !== '' ? (int) $option['points'] : null,
                'hours' => $option['hours'] !== null && $option['hours'] !== '' ? (int) $option['hours'] : null,
            ]);
        }
    }

    private function uniqueKey(string $label): string
    {
        $base = Str::slug($label) ?: 'assessment';
        $key = $base;
        $suffix = 2;

        while (TnaForm::where('key', $key)->exists()) {
            $key = "{$base}-{$suffix}";
            $suffix++;
        }

        return $key;
    }

    /**
     * The participant types a form can be attached to — the ones people
     * actually register as.
     *
     * @return array<int, string>
     */
    private function participantTypes(): array
    {
        return \App\Models\User::query()
            ->whereNotNull('participant_type')
            ->distinct()
            ->orderBy('participant_type')
            ->pluck('participant_type')
            ->all();
    }
}
