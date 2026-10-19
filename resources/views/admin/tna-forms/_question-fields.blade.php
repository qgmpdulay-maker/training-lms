{{--
    The fields for one question, shared by the "add" form and each question's
    own edit form. $question is null when adding.

    Options are posted as the whole list for the question (options[i][...]);
    clearing a label deletes that row, which keeps the markup to plain inputs
    with no per-row delete endpoint.
--}}
@php
    $question = $question ?? null;
    $prefix = $question ? 'q'.$question->id : 'new';
    $optionRows = $question
        ? $question->options->map(fn ($option) => [
            'label' => $option->label,
            'category' => $option->category,
            'points' => $option->points,
            'hours' => $option->hours,
        ])->all()
        : [];
@endphp

<div x-data="{
        kind: @js($question->kind ?? \App\Models\TnaQuestion::KIND_SINGLE_CHOICE),
        options: @js($optionRows ?: [['label' => '', 'category' => '', 'points' => '', 'hours' => '']]),
        get expectsOptions() {
            return this.kind === 'single_choice' || this.kind === 'multi_choice';
        },
    }" class="space-y-4">

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="sm:col-span-2">
            <label for="{{ $prefix }}-prompt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Question') }}</label>
            <textarea id="{{ $prefix }}-prompt" name="prompt" rows="2" required
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">{{ old('prompt', $question->prompt ?? '') }}</textarea>
        </div>
        <div>
            <label for="{{ $prefix }}-kind" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Answer type') }}</label>
            <select id="{{ $prefix }}-kind" name="kind" x-model="kind"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                @foreach ($kindLabels as $value => $label)
                    <option value="{{ $value }}" @selected(old('kind', $question->kind ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="{{ $prefix }}-group" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Section heading') }}</label>
            <input id="{{ $prefix }}-group" type="text" name="group_label" value="{{ old('group_label', $question->group_label ?? '') }}"
                placeholder="{{ __('Optional') }}"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
        </div>
        <div class="sm:col-span-2">
            <label for="{{ $prefix }}-help" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Helper text') }}</label>
            <input id="{{ $prefix }}-help" type="text" name="help_text" value="{{ old('help_text', $question->help_text ?? '') }}"
                placeholder="{{ __('Optional — shown under the question') }}"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
        </div>
    </div>

    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
        <input type="hidden" name="is_required" value="0">
        <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $question->is_required ?? true))
            class="rounded border-gray-300 text-[#152A4E] focus:ring-[#152A4E]">
        {{ __('Answer required') }}
    </label>

    {{-- Options only apply to the two choice types; a competency pair, short
         answer or number has nothing to list. --}}
    <div x-show="expectsOptions" x-cloak class="border-t border-gray-100 dark:border-gray-700 pt-4">
        <div class="flex items-center justify-between gap-3 mb-2">
            <p class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ __('Answer options') }}</p>
            <p class="text-xs text-gray-400">{{ __('Points go to the category; hours caps the training length. Clear a label to delete that option.') }}</p>
        </div>

        <div class="space-y-2">
            <template x-for="(option, index) in options" :key="index">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                    <input type="text" :name="'options[' + index + '][label]'" x-model="option.label"
                        placeholder="{{ __('Option shown to the participant') }}"
                        class="sm:col-span-6 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    <input type="text" list="tna-categories" :name="'options[' + index + '][category]'" x-model="option.category"
                        placeholder="{{ __('Category') }}"
                        class="sm:col-span-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    <input type="number" min="0" max="100" :name="'options[' + index + '][points]'" x-model="option.points"
                        placeholder="{{ __('Points') }}"
                        class="sm:col-span-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    <input type="number" min="0" max="999" :name="'options[' + index + '][hours]'" x-model="option.hours"
                        placeholder="{{ __('Hours') }}"
                        class="sm:col-span-2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                </div>
            </template>
        </div>

        <button type="button" @click="options.push({ label: '', category: '', points: '', hours: '' })"
            class="mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition">
            {{ __('+ Add option') }}
        </button>
    </div>
</div>
