<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Assessment') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admin.tna-forms.index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to assessments') }}
            </a>

            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="flex items-start gap-3 text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- Shared across every option's category input, so the categories that
                 already drive the training catalogue are one keystroke away. --}}
            <datalist id="tna-categories">
                @foreach ($categories as $category)
                    <option value="{{ $category }}"></option>
                @endforeach
            </datalist>

            <!-- Assessment details -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <form method="POST" action="{{ route('admin.tna-forms.update', $form) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Name') }}</label>
                            <input id="label" type="text" name="label" value="{{ old('label', $form->label) }}" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <div>
                            <label for="participant_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Given to') }}</label>
                            <select id="participant_type" name="participant_type"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                                <option value="">{{ __('Any type without its own assessment (fallback)') }}</option>
                                @foreach ($participantTypes as $type)
                                    <option value="{{ $type }}" @selected(old('participant_type', $form->participant_type) === $type)>{{ $type }}</option>
                                @endforeach
                                {{-- A type that no one is registered as yet would otherwise
                                     vanish from this list and silently detach the form. --}}
                                @if ($form->participant_type && ! in_array($form->participant_type, $participantTypes, true))
                                    <option value="{{ $form->participant_type }}" selected>{{ $form->participant_type }}</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Description') }}</label>
                        <textarea id="description" name="description" rows="2"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">{{ old('description', $form->description) }}</textarea>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $form->is_active))
                                class="rounded border-gray-300 text-[#152A4E] focus:ring-[#152A4E]">
                            {{ __('In use — participants of this type get this assessment') }}
                        </label>

                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                            {{ __('Save Details') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Questions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Questions') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ trans_choice(':count question, in the order participants answer them.|:count questions, in the order participants answer them.', $form->questions->count(), ['count' => $form->questions->count()]) }}
                </p>

                @if ($form->questions->isEmpty())
                    <p class="text-sm text-gray-400">{{ __('No questions yet — add the first one below.') }}</p>
                @else
                    <div class="space-y-2">
                        @foreach ($form->questions as $question)
                            @if ($loop->first || $question->group_label !== $form->questions[$loop->index - 1]->group_label)
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 pt-4 first:pt-0">
                                    {{ $question->group_label ?: __('Ungrouped') }}
                                </p>
                            @endif

                            {{-- Collapsed by default: these lists run to hundreds of
                                 questions on the council tools, and expanding one at a
                                 time keeps the page navigable. --}}
                            <div x-data="{ open: false }" class="border border-gray-100 dark:border-gray-700 rounded-lg">
                                <div class="flex items-center gap-3 px-4 py-3">
                                    <button type="button" @click="open = ! open" class="flex items-start gap-3 min-w-0 flex-1 text-left">
                                        <svg class="w-4 h-4 mt-0.5 shrink-0 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                        </svg>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-[#152A4E] dark:text-white">{{ $question->prompt }}</span>
                                            <span class="block text-xs text-gray-400 mt-0.5">
                                                {{ $kindLabels[$question->kind] ?? $question->kind }}
                                                @if ($question->expectsOptions())
                                                    · {{ trans_choice(':count option|:count options', $question->options->count(), ['count' => $question->options->count()]) }}
                                                @endif
                                                @unless ($question->is_required)
                                                    · {{ __('optional') }}
                                                @endunless
                                            </span>
                                        </span>
                                    </button>

                                    <div class="shrink-0 flex items-center gap-1">
                                        <form method="POST" action="{{ route('admin.tna-questions.move', $question) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" title="{{ __('Move up') }}" @disabled($loop->first)
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-400 hover:text-[#152A4E] dark:hover:text-white disabled:opacity-30 transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.tna-questions.move', $question) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" title="{{ __('Move down') }}" @disabled($loop->last)
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-400 hover:text-[#152A4E] dark:hover:text-white disabled:opacity-30 transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div x-show="open" x-cloak class="border-t border-gray-100 dark:border-gray-700 p-4 sm:p-5 bg-gray-50/60 dark:bg-gray-900/20">
                                    <form method="POST" action="{{ route('admin.tna-questions.update', $question) }}" class="space-y-4">
                                        @csrf
                                        @method('PUT')
                                        @include('admin.tna-forms._question-fields', ['question' => $question])

                                        <div class="flex items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-700 pt-4">
                                            <button type="submit" form="delete-question-{{ $question->id }}"
                                                onclick="return confirm('{{ __('Remove this question? Submissions already made keep their answers.') }}')"
                                                class="text-xs font-semibold text-red-600 dark:text-red-400 hover:text-red-700 transition">
                                                {{ __('Remove question') }}
                                            </button>
                                            <button type="submit"
                                                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                                                {{ __('Save Question') }}
                                            </button>
                                        </div>
                                    </form>

                                    {{-- Outside the edit form: nesting a second form inside it
                                         is invalid HTML, so the delete button above targets this. --}}
                                    <form id="delete-question-{{ $question->id }}" method="POST" action="{{ route('admin.tna-questions.destroy', $question) }}" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Add a question -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-5">{{ __('Add a Question') }}</h2>

                <form method="POST" action="{{ route('admin.tna-forms.questions.store', $form) }}" class="space-y-4">
                    @csrf
                    @include('admin.tna-forms._question-fields', ['question' => null])

                    <div class="flex justify-end border-t border-gray-100 dark:border-gray-700 pt-4">
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                            {{ __('Add Question') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="flex justify-end">
                <form method="POST" action="{{ route('admin.tna-forms.destroy', $form) }}"
                    onsubmit="return confirm('{{ __('Delete this whole assessment? Submissions already made keep their answers.') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-semibold text-red-600 dark:text-red-400 hover:text-red-700 transition">
                        {{ __('Delete this assessment') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
