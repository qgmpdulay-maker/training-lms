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
                    {{ __('My Training Requests') }}
                </a>
                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                    </svg>
                    {{ __('Print / Save as PDF') }}
                </button>
            </div>

            <!-- Confirmation banner -->
            <div class="no-print mb-8 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-6 sm:p-8 text-center">
                <div class="mx-auto mb-4 flex items-center justify-center w-14 h-14 rounded-full bg-green-100 dark:bg-green-800/40">
                    <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Your request has been submitted') }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">{{ __('Please keep this reference number for your records.') }}</p>
                <p class="text-2xl font-bold tracking-wide text-[#152A4E] dark:text-white mb-4">{{ $trainingRequest->reference_number }}</p>
                <span class="inline-flex items-center text-xs font-semibold rounded-full border px-3 py-1.5 {{ $statusStyles[$trainingRequest->status] ?? 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600' }}">
                    {{ $trainingRequest->statusLabel() }}
                </span>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-5 max-w-md mx-auto">
                    {{ __('CDTI will review your request and get in touch using the contact details you provided. A copy of your request letter is below — you can print it or save it as a PDF for your files.') }}
                </p>
            </div>

            <!-- Printable letter -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 sm:p-12 leading-relaxed text-gray-800">
                <p class="text-sm text-gray-500 mb-8">{{ $trainingRequest->created_at->format('F j, Y') }}</p>

                <p class="font-bold mb-0">MR. GILBERT H. CONDE</p>
                <p class="mb-0">Director III</p>
                <p class="mb-0">Civil Defense and Disaster Management Training Institute</p>
                <p class="mb-0">Office of Civil Defense</p>
                <p class="mb-6">Camp General Emilio Aguinaldo, Quezon City</p>

                <p class="mb-4">{{ __('Dear Director Conde,') }}</p>

                <p class="mb-4">
                    {{ __('On behalf of :agency, I am writing to formally request the conduct of the :training training for approximately :count participant(s), preferably on :date at :venue.', [
                        'agency' => $trainingRequest->requesting_agency,
                        'training' => $trainingRequest->training_title,
                        'count' => $trainingRequest->number_of_participants,
                        'date' => $trainingRequest->preferred_date->format('F j, Y'),
                        'venue' => $trainingRequest->venue,
                    ]) }}
                </p>

                <p class="mb-4">{{ $trainingRequest->purpose }}</p>

                <p class="mb-6">
                    {{ __('We acknowledge that this training is offered free of charge, and that our agency will be responsible for the training venue, accommodation and meals for the instructor(s) and participants, reproduction of training materials, and honoraria for instructors and facilitators.') }}
                </p>

                <p class="mb-1">{{ __('Respectfully yours,') }}</p>
                <p class="italic text-lg mt-6 mb-0">{{ $trainingRequest->signature_name }}</p>
                <div class="w-56 border-t border-gray-400 mb-1"></div>
                <p class="font-semibold mb-0">{{ $trainingRequest->contact_person }}</p>
                <p class="mb-0">{{ $trainingRequest->requesting_agency }}</p>
                <p class="text-sm text-gray-500">{{ $trainingRequest->contact_number }} &middot; {{ $trainingRequest->contact_email }}</p>

                @if ($trainingRequest->signed_letter_path)
                    <div class="no-print mt-8 pt-6 border-t border-gray-100">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('A separately signed copy was also uploaded with this request:') }}</p>
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($trainingRequest->signed_letter_path) }}" target="_blank"
                            class="text-sm font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D]">
                            {{ __('View uploaded file') }} &rarr;
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
