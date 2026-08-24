<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Evaluation') }} — {{ $trainingRequest->training_title }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admin.tools') }}" class="inline-flex items-center gap-1 text-sm text-[#152A4E] dark:text-white font-semibold hover:text-[#E2762D]">
                &larr; {{ __('Back to Tools') }}
            </a>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ $trainingRequest->training_title }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    {{ $trainingRequest->user->name }} &middot; {{ $trainingRequest->preferred_date->format('F j, Y') }}
                </p>

                <form method="POST" action="{{ route('admin.evaluations.update', $trainingRequest) }}" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <!-- L2: Pre/Post Test -->
                    <div>
                        <h3 class="font-bold text-[#152A4E] dark:text-white mb-3">{{ __('L2 Evaluation — Pre/Post Test') }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-md">
                            <div>
                                <x-input-label for="pretest_score" :value="__('Pre-Test Score (0–100)')" />
                                <x-text-input id="pretest_score" name="pretest_score" type="number" min="0" max="100" class="mt-1 block w-full"
                                    value="{{ old('pretest_score', $trainingRequest->trainingEvaluation?->pretest_score) }}" />
                                <x-input-error :messages="$errors->get('pretest_score')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="posttest_score" :value="__('Post-Test Score (0–100)')" />
                                <x-text-input id="posttest_score" name="posttest_score" type="number" min="0" max="100" class="mt-1 block w-full"
                                    value="{{ old('posttest_score', $trainingRequest->trainingEvaluation?->posttest_score) }}" />
                                <x-input-error :messages="$errors->get('posttest_score')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- L1: Module & Trainer Ratings -->
                    <div>
                        <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('L1 Evaluation — Module & Trainer Ratings') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ __("Leave a row completely empty to skip it. If you enter a rating but skip the module code, it'll just be saved as \"Module 1\", \"Module 2\", and so on — nothing gets lost. Up to :max modules.", ['max' => \App\Http\Controllers\Admin\EvaluationController::MAX_MODULE_ROWS]) }}</p>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                        <th class="py-2 pr-3">{{ __('Module') }}</th>
                                        <th class="py-2 pr-3">{{ __('Module Rating') }}</th>
                                        <th class="py-2 pr-3">{{ __('Trainer Rating') }}</th>
                                        <th class="py-2 pr-3">{{ __('Comments') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($moduleRows as $i => $row)
                                        <tr>
                                            <td class="py-2 pr-3">
                                                <input type="text" name="module[]" value="{{ old('module.'.$i, $row['module'] ?? '') }}" placeholder="{{ __('e.g. M1S1') }}"
                                                    class="w-32 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                            </td>
                                            <td class="py-2 pr-3">
                                                <select name="module_rating[]" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                    <option value="">—</option>
                                                    @foreach ($ratingScale as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('module_rating.'.$i, $row['module_rating'] ?? '') == $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="py-2 pr-3">
                                                <select name="trainer_rating[]" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                    <option value="">—</option>
                                                    @foreach ($ratingScale as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('trainer_rating.'.$i, $row['trainer_rating'] ?? '') == $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="py-2 pr-3">
                                                <input type="text" name="comment[]" value="{{ old('comment.'.$i, $row['comment'] ?? '') }}"
                                                    class="w-48 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                            {{ __('Save Evaluation') }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
