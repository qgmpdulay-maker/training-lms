<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Training Request') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admin.summary') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to Summary') }}
            </a>

            @if ($errors->any())
                <div class="flex items-start gap-3 text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ __('Please check the highlighted fields below.') }}</span>
                </div>
            @endif

            <!-- Overview -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h1 class="text-xl font-bold text-[#152A4E] dark:text-white mb-1">{{ $record->training_title }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ $record->reference_number ?? __('No reference number yet') }}</p>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-0.5">{{ __('Requesting Agency') }}</dt>
                        <dd class="text-gray-700 dark:text-gray-200">{{ $record->requesting_agency }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-0.5">{{ __('Contact') }}</dt>
                        <dd class="text-gray-700 dark:text-gray-200">{{ $record->contact_person }} &middot; {{ $record->contact_number }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-0.5">
                            {{ trans_choice(':count Participant|:count Participants', $participants->count(), ['count' => $participants->count()]) }}
                        </dt>
                        <dd class="text-gray-700 dark:text-gray-200">
                            @if ($participants->isEmpty())
                                <span class="text-gray-400">{{ __('None on file') }}</span>
                            @else
                                {{ $participants->pluck('name')->join(', ') }}
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <form method="POST" action="{{ route('admin.summary.update', $record) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Status -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Status') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Where this request stands right now.') }}</p>

                    <select id="status" name="status"
                        class="w-full sm:w-72 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $record->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                </div>

                <!-- Reschedule -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Date & Venue') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __("Only change these if the training needs to move — for example, because of weather. If it's already Approved, remember to update the Status above too.") }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="preferred_date" :value="__('Date')" />
                            <x-text-input id="preferred_date" name="preferred_date" type="date" class="mt-1 block w-full" required
                                value="{{ old('preferred_date', $record->preferred_date->format('Y-m-d')) }}" />
                            <x-input-error :messages="$errors->get('preferred_date')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="venue" :value="__('Venue')" />
                            <x-text-input id="venue" name="venue" type="text" class="mt-1 block w-full" required
                                value="{{ old('venue', $record->venue) }}" />
                            <x-input-error :messages="$errors->get('venue')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Category & Region -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Category & Region') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Used to filter the Calendar tab.') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="category" :value="__('Category')" />
                            <select id="category" name="category"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                                <option value="">{{ __('No category') }}</option>
                                @foreach ($categoryLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('category', $record->category) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('category')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label :value="__('Region')" />
                            @if (Auth::user()->isAdmin())
                                <x-text-input type="text" class="mt-1 block w-full bg-gray-50 dark:bg-gray-700" value="{{ Auth::user()->region }}" disabled />
                                <p class="text-xs text-gray-400 mt-1">{{ __('Locked to your region.') }}</p>
                            @else
                                <select name="region"
                                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                                    <option value="">{{ __('No region') }}</option>
                                    @foreach ($regions as $region)
                                        <option value="{{ $region }}" @selected(old('region', $record->region) === $region)>{{ $region }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('region')" class="mt-1" />
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Monitoring Data -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Monitoring Data') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Feeds Regional Monitoring and the Graduates Map. Graduate counts by sex and age are calculated automatically from the participant list once this training is marked Completed — only these two fields need to be set by hand.') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="agency_type" :value="__('Requesting Agency Type')" />
                            <select id="agency_type" name="agency_type"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                                <option value="">{{ __('Not set') }}</option>
                                @foreach ($agencyTypeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('agency_type', $record->agency_type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('agency_type')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="teams_organized" :value="__('Teams Organized')" />
                            <x-text-input id="teams_organized" name="teams_organized" type="number" min="0" class="mt-1 block w-full"
                                value="{{ old('teams_organized', $record->teams_organized) }}" />
                            <x-input-error :messages="$errors->get('teams_organized')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Certificate Details -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Certificate Details') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Applies to every participant listed above.') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <x-input-label for="lgu" :value="__('LGU')" />
                            <x-text-input id="lgu" name="lgu" type="text" class="mt-1 block w-full" value="{{ old('lgu', $record->lgu) }}" />
                            <x-input-error :messages="$errors->get('lgu')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="certificate_code" :value="__('Certificate Code')" />
                            <x-text-input id="certificate_code" name="certificate_code" type="text" class="mt-1 block w-full" value="{{ old('certificate_code', $record->certificate_code) }}" />
                            <x-input-error :messages="$errors->get('certificate_code')" class="mt-1" />
                        </div>
                    </div>

                    <x-input-label for="certificate_remarks" :value="__('Certificate Remarks')" />
                    <select id="certificate_remarks" name="certificate_remarks"
                        class="mt-1 block w-full sm:w-72 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                        <option value="">{{ __('No remarks') }}</option>
                        @foreach ($certificateRemarksLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('certificate_remarks', $record->certificate_remarks) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('certificate_remarks')" class="mt-1" />
                </div>

                <!-- Instructors -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Instructors') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Who taught this training — shown by name to participants when they submit their evaluation.') }}</p>

                    @if ($availableInstructors->isEmpty())
                        <p class="text-sm text-gray-400">{{ __('No instructors on file for this region yet. Add one from the Instructors tab first.') }}</p>
                    @else
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @php $selectedInstructorIds = old('instructor_ids', $record->instructors->pluck('id')->all()); @endphp
                            @foreach ($availableInstructors as $instructor)
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" name="instructor_ids[]" value="{{ $instructor->id }}"
                                        @checked(in_array($instructor->id, $selectedInstructorIds))
                                        class="rounded border-gray-300 dark:border-gray-600 text-[#152A4E] focus:ring-[#152A4E]">
                                    {{ $instructor->name }}
                                    @if ($instructor->training_type)
                                        <span class="text-xs text-gray-400">&middot; {{ $instructor->training_type }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <x-input-error :messages="$errors->get('instructor_ids')" class="mt-1" />
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-8 py-3 hover:bg-[#1E3A66] transition">
                        {{ __('Save Changes') }}
                    </button>
                </div>
            </form>

            <!-- Participant Evaluations -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Participant Evaluations') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ trans_choice(':count of :total participant|:count of :total participants have submitted an evaluation.', $record->participantEvaluations->count(), ['count' => $record->participantEvaluations->count(), 'total' => $participants->count()]) }}
                </p>

                @if ($record->participantEvaluations->isEmpty())
                    <p class="text-sm text-gray-400">{{ __('No evaluations submitted yet.') }}</p>
                @else
                    @php $instructorsById = $record->instructors->keyBy('id'); @endphp
                    <div class="space-y-6">
                        @foreach ($record->participantEvaluations as $evaluation)
                            <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4 sm:p-5">
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <h3 class="font-semibold text-[#152A4E] dark:text-white">{{ $evaluation->user->name ?? __('Unknown participant') }}</h3>
                                    <span class="text-xs text-gray-400">{{ $evaluation->updated_at->format('M j, Y') }}</span>
                                </div>

                                @if (! empty($evaluation->module_ratings))
                                    <div class="overflow-x-auto mb-3">
                                        <table class="min-w-full text-xs">
                                            <thead>
                                                <tr class="text-left font-semibold uppercase tracking-wide text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                    <th class="py-1.5 pr-3">{{ __('Module') }}</th>
                                                    <th class="py-1.5 pr-3">{{ __('Rating') }}</th>
                                                    <th class="py-1.5 pr-3">{{ __('Comment') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                @foreach ($evaluation->module_ratings as $row)
                                                    <tr>
                                                        <td class="py-1.5 pr-3 text-gray-700 dark:text-gray-200">{{ $row['module'] ?? '—' }}</td>
                                                        <td class="py-1.5 pr-3 text-gray-700 dark:text-gray-200">{{ $row['module_rating'] ?? '—' }}</td>
                                                        <td class="py-1.5 pr-3 text-gray-500 dark:text-gray-400">{{ $row['comment'] ?? '' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if (! empty($evaluation->instructor_ratings))
                                    <div class="overflow-x-auto mb-3">
                                        <table class="min-w-full text-xs">
                                            <thead>
                                                <tr class="text-left font-semibold uppercase tracking-wide text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                    <th class="py-1.5 pr-3">{{ __('Instructor') }}</th>
                                                    <th class="py-1.5 pr-3">{{ __('Rating') }}</th>
                                                    <th class="py-1.5 pr-3">{{ __('Comment') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                @foreach ($evaluation->instructor_ratings as $row)
                                                    <tr>
                                                        <td class="py-1.5 pr-3 text-gray-700 dark:text-gray-200">{{ $instructorsById->get($row['instructor_id'])?->name ?? __('Unknown instructor') }}</td>
                                                        <td class="py-1.5 pr-3 text-gray-700 dark:text-gray-200">{{ $row['rating'] ?? '—' }}</td>
                                                        <td class="py-1.5 pr-3 text-gray-500 dark:text-gray-400">{{ $row['comment'] ?? '' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if ($evaluation->overall_comments)
                                    <p class="text-sm text-gray-600 dark:text-gray-300"><span class="font-semibold text-gray-700 dark:text-gray-200">{{ __('Overall:') }}</span> {{ $evaluation->overall_comments }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
