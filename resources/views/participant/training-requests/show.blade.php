@php
    $statusStyles = [
        \App\Models\TrainingRequest::STATUS_SUBMITTED => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700',
        \App\Models\TrainingRequest::STATUS_UNDER_REVIEW => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700',
        \App\Models\TrainingRequest::STATUS_APPROVED => 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-700',
        \App\Models\TrainingRequest::STATUS_DECLINED => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-700',
        \App\Models\TrainingRequest::STATUS_COMPLETED => 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700',
    ];
@endphp
<x-app-layout>
    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="no-print flex items-center justify-between mb-6">
                <a href="{{ route('training-requests.index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white">
                    <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    {{ __('My Upcoming Trainings') }}
                </a>
                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                    </svg>
                    {{ __('Print / Save as PDF') }}
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-8 sm:p-10">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-8">
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">{{ $trainingRequest->reference_number ?? __('Reference pending') }}</p>
                        <h1 class="text-xl font-bold text-[#152A4E] dark:text-white">{{ $trainingRequest->training_title }}</h1>
                    </div>
                    <span class="shrink-0 inline-flex items-center text-xs font-semibold rounded-full border px-3 py-1.5 {{ $statusStyles[$trainingRequest->status] ?? 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600' }}">
                        {{ $trainingRequest->statusLabel() }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-6">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">{{ __('Date') }}</dt>
                        <dd class="text-sm text-gray-800 dark:text-gray-200">{{ $trainingRequest->preferred_date->format('F j, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">{{ __('Venue') }}</dt>
                        <dd class="text-sm text-gray-800 dark:text-gray-200">{{ $trainingRequest->venue }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">{{ __('Requesting Agency') }}</dt>
                        <dd class="text-sm text-gray-800 dark:text-gray-200">{{ $trainingRequest->requesting_agency }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">{{ __('Number of Participants') }}</dt>
                        <dd class="text-sm text-gray-800 dark:text-gray-200">{{ $trainingRequest->number_of_participants }}</dd>
                    </div>
                    @if ($trainingRequest->purpose)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">{{ __('Notes') }}</dt>
                            <dd class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed">{{ $trainingRequest->purpose }}</dd>
                        </div>
                    @endif
                    @if ($trainingRequest->certificate_file_path)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">{{ __('Certificate') }}</dt>
                            <dd class="text-sm">
                                <a href="{{ asset('storage/' . $trainingRequest->certificate_file_path) }}" target="_blank"
                                    class="no-print inline-flex items-center gap-1.5 font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D]">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3" />
                                    </svg>
                                    {{ __('Download Certificate') }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

        </div>
    </div>
</x-app-layout>
