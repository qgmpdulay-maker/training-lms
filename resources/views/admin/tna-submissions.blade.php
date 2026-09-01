<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Training Needs Assessment — Organization Submissions') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Formal TNA reports collected from LGUs and NGAs in :region, separate from participant self-assessments. Super Admin reviews aggregated copies of everything logged here.', ['region' => Auth::user()->region]) }}</p>
                <a href="{{ route('admin.tna-submissions.form') }}" target="_blank"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-md border border-gray-200 dark:border-gray-600 text-[#152A4E] dark:text-white px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">
                    {{ __('Download Blank Form') }}
                </a>
            </div>

            <!-- Record a Submission -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Record a TNA Submission') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Log a formal Training Needs Assessment report received from an LGU or NGA in your region.') }}</p>

                <form method="POST" action="{{ route('admin.tna-submissions.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @csrf

                    <div>
                        <x-input-label :value="__('Region')" />
                        <x-text-input type="text" class="mt-1 block w-full bg-gray-50 dark:bg-gray-700" value="{{ Auth::user()->region }}" disabled />
                        <p class="text-xs text-gray-400 mt-1">{{ __('Locked to your region.') }}</p>
                    </div>

                    <div>
                        <x-input-label for="agency_type" :value="__('Type of Organization')" />
                        <select id="agency_type" name="agency_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">{{ __('Unspecified') }}</option>
                            @foreach ($agencyTypeLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('agency_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('agency_type')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="organization" :value="__('Organization Name')" />
                        <x-text-input id="organization" name="organization" type="text" class="mt-1 block w-full" placeholder="e.g. City Government of Baguio" value="{{ old('organization') }}" />
                        <x-input-error :messages="$errors->get('organization')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="training_topic" :value="__('Training Topic')" />
                        <x-text-input id="training_topic" name="training_topic" type="text" class="mt-1 block w-full" required value="{{ old('training_topic') }}" />
                        <x-input-error :messages="$errors->get('training_topic')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="personnel_assessed" :value="__('Personnel Assessed')" />
                        <x-text-input id="personnel_assessed" name="personnel_assessed" type="number" min="0" class="mt-1 block w-full" value="{{ old('personnel_assessed', 0) }}" />
                        <x-input-error :messages="$errors->get('personnel_assessed')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="date_assessed" :value="__('Date Assessed')" />
                        <x-text-input id="date_assessed" name="date_assessed" type="date" class="mt-1 block w-full" required value="{{ old('date_assessed', now()->toDateString()) }}" />
                        <x-input-error :messages="$errors->get('date_assessed')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="submitted_by" :value="__('Submitted By')" />
                        <x-text-input id="submitted_by" name="submitted_by" type="text" class="mt-1 block w-full" required value="{{ old('submitted_by', Auth::user()->name) }}" />
                        <x-input-error :messages="$errors->get('submitted_by')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'pending') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <x-input-label for="results_pdf" :value="__('Completed TNA Form (PDF)')" />
                        <input id="results_pdf" name="results_pdf" type="file" accept="application/pdf"
                            class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-100 dark:file:bg-gray-700 file:text-[#152A4E] dark:file:text-white hover:file:bg-gray-200 dark:hover:file:bg-gray-600">
                        <p class="text-xs text-gray-400 mt-1">{{ __('Optional. Attach the filled-out copy of the downloadable TNA form once the LGU / NGA returns it — you can also upload it later from the list below.') }}</p>
                        <x-input-error :messages="$errors->get('results_pdf')" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3 flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                            {{ __('Record Submission') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Submissions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Submissions') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('TNA reports logged for :region.', ['region' => Auth::user()->region]) }}</p>

                @if ($submissions->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No TNA submissions on file yet.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('LGU / Organization') }}</th>
                                    <th class="py-2 pr-4">{{ __('Topic') }}</th>
                                    <th class="py-2 pr-4">{{ __('Personnel') }}</th>
                                    <th class="py-2 pr-4">{{ __('Date Assessed') }}</th>
                                    <th class="py-2 pr-4">{{ __('Completed Form') }}</th>
                                    <th class="py-2 pr-4">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($submissions as $submission)
                                    <tr>
                                        <td class="py-3 pr-4">
                                            <div class="font-medium text-[#152A4E] dark:text-white">{{ $submission->organization ?? '—' }}</div>
                                            @if ($submission->agencyTypeLabel())
                                                <div class="text-xs text-gray-400">{{ $submission->agencyTypeLabel() }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->training_topic }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->personnel_assessed }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->date_assessed->format('M j, Y') }}</td>
                                        <td class="py-3 pr-4">
                                            @if ($submission->hasResultsPdf())
                                                <a href="{{ asset('storage/'.$submission->results_pdf_path) }}" target="_blank"
                                                    class="text-xs font-semibold text-green-700 dark:text-green-400 hover:underline">{{ __('View PDF') }}</a>
                                            @else
                                                <form method="POST" action="{{ route('admin.tna-submissions.results', $submission) }}" enctype="multipart/form-data" class="flex items-center gap-1.5">
                                                    @csrf
                                                    <input type="file" name="results_pdf" accept="application/pdf" required class="text-xs w-32">
                                                    <button type="submit" class="text-xs font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D]">{{ __('Upload') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4">
                                            <form method="POST" action="{{ route('admin.tna-submissions.update', $submission) }}" x-data>
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="agency_type" value="{{ $submission->agency_type }}">
                                                <input type="hidden" name="organization" value="{{ $submission->organization }}">
                                                <input type="hidden" name="training_topic" value="{{ $submission->training_topic }}">
                                                <input type="hidden" name="personnel_assessed" value="{{ $submission->personnel_assessed }}">
                                                <input type="hidden" name="date_assessed" value="{{ $submission->date_assessed->toDateString() }}">
                                                <input type="hidden" name="submitted_by" value="{{ $submission->submitted_by }}">
                                                <input type="hidden" name="notes" value="{{ $submission->notes }}">
                                                <select name="status" onchange="this.form.submit()"
                                                    @class([
                                                        'text-xs rounded-full border pl-2.5 pr-5 py-1',
                                                        'bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600' => $submission->status === \App\Models\TnaSubmission::STATUS_PENDING,
                                                        'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-700' => $submission->status === \App\Models\TnaSubmission::STATUS_REVIEWED,
                                                    ])>
                                                    @foreach ($statusLabels as $value => $label)
                                                        <option value="{{ $value }}" @selected($submission->status === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5">
                        {{ $submissions->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
