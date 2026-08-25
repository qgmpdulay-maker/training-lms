<x-app-layout>
    <div class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white mb-1">{{ __('My Certificates') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Certificates issued for trainings you attended.') }}</p>
            </div>

            @if ($certificates->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-10 text-center">
                    <p class="text-gray-500 dark:text-gray-400">{{ __("No certificates on file yet — they'll show up here once your OCD Regional Office uploads them.") }}</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($certificates as $certificate)
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 sm:p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <h2 class="font-bold text-[#152A4E] dark:text-white">{{ $certificate->training_title }}</h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $certificate->preferred_date->format('F j, Y') }}
                                        @if ($certificate->certificate_remarks)
                                            &middot; {{ $certificateRemarksLabels[$certificate->certificate_remarks] ?? ucfirst($certificate->certificate_remarks) }}
                                        @endif
                                    </p>
                                </div>
                                <a href="{{ asset('storage/'.$certificate->certificate_file_path) }}" target="_blank"
                                    class="shrink-0 inline-flex items-center gap-1.5 bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3" />
                                    </svg>
                                    {{ __('Download') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
