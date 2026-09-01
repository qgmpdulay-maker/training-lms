<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Evaluate') }} — {{ $trainingRequest->training_title }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('training-requests.show', $trainingRequest) }}" class="inline-flex items-center gap-1 text-sm text-[#152A4E] dark:text-white font-semibold hover:text-[#E2762D]">
                &larr; {{ __('Back to Training') }}
            </a>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ $trainingRequest->training_title }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    {{ $trainingRequest->preferred_date->format('F j, Y') }} &middot; {{ $trainingRequest->venue }}
                </p>

                <form method="POST" action="{{ route('training-requests.evaluation.update', $trainingRequest) }}" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <div>
                        <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Module Ratings') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ __('Rate each module you attended. Leave a row empty to skip it.') }}</p>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                        <th class="py-2 pr-3">{{ __('Module') }}</th>
                                        <th class="py-2 pr-3">{{ __('Rating') }}</th>
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
                                                <input type="text" name="comment[]" value="{{ old('comment.'.$i, $row['comment'] ?? '') }}"
                                                    class="w-48 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($trainingRequest->instructors->isNotEmpty())
                        <div>
                            <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Instructor Ratings') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ __('Rate each instructor who taught this training.') }}</p>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                            <th class="py-2 pr-3">{{ __('Instructor') }}</th>
                                            <th class="py-2 pr-3">{{ __('Rating') }}</th>
                                            <th class="py-2 pr-3">{{ __('Comments') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @foreach ($trainingRequest->instructors as $instructor)
                                            @php $existingRating = $existingInstructorRatings->get($instructor->id); @endphp
                                            <tr>
                                                <td class="py-2 pr-3 font-medium text-gray-700 dark:text-gray-200">{{ $instructor->name }}</td>
                                                <td class="py-2 pr-3">
                                                    <select name="instructor_rating[{{ $instructor->id }}]" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                        <option value="">—</option>
                                                        @foreach ($ratingScale as $value => $label)
                                                            <option value="{{ $value }}" @selected(old('instructor_rating.'.$instructor->id, $existingRating['rating'] ?? '') == $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="py-2 pr-3">
                                                    <input type="text" name="instructor_comment[{{ $instructor->id }}]" value="{{ old('instructor_comment.'.$instructor->id, $existingRating['comment'] ?? '') }}"
                                                        class="w-48 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div>
                        <x-input-label for="overall_comments" :value="__('Overall Comments (optional)')" />
                        <textarea id="overall_comments" name="overall_comments" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">{{ old('overall_comments', $existing?->overall_comments) }}</textarea>
                        <x-input-error :messages="$errors->get('overall_comments')" class="mt-1" />
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                            {{ $existing ? __('Update Evaluation') : __('Submit Evaluation') }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
