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
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white mb-1">{{ __('My Training Requests') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Here are the trainings you have requested and their current status.') }}</p>
                </div>
                <a href="{{ route('trainings.index') }}"
                    class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] hover:bg-[#1E3A66] text-white text-sm font-semibold rounded-lg px-5 py-3 transition">
                    {{ __('+ Request a Training') }}
                </a>
            </div>

            @if (session('status'))
                <div class="mb-6 flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($requests->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-10 text-center">
                    <p class="text-gray-500 dark:text-gray-400 mb-4">{{ __("You haven't requested any trainings yet.") }}</p>
                    <a href="{{ route('trainings.index') }}" class="text-[#152A4E] dark:text-white font-semibold hover:text-[#E2762D]">
                        {{ __('Browse available trainings') }} &rarr;
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($requests as $request)
                        <a href="{{ route('training-requests.show', $request) }}"
                            class="block bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition p-5 sm:p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">{{ $request->reference_number ?? __('Processing...') }}</p>
                                    <h2 class="font-bold text-[#152A4E] dark:text-white">{{ $request->training_title }}</h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ __('Requested on :date', ['date' => $request->created_at->format('F j, Y')]) }}
                                    </p>
                                </div>
                                <span class="shrink-0 inline-flex items-center text-xs font-semibold rounded-full border px-3 py-1.5 {{ $statusStyles[$request->status] ?? 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600' }}">
                                    {{ $request->statusLabel() }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
