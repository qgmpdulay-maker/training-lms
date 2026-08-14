<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Calendar') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-start gap-3 text-sm text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3">
                <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <span>{{ __('Entries are color-coded by request status rather than APB / Technical Assistance category, since trainings aren\'t tagged that way yet. Shown across all regions for the same reason as Summary.') }}</span>
            </div>

            @if ($groupedByMonth->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No scheduled trainings yet.') }}
                    </div>
                </div>
            @else
                @foreach ($groupedByMonth as $month => $requests)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                        <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-5">{{ $month }}</h2>
                        <ul class="space-y-3">
                            @foreach ($requests as $request)
                                <li class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 dark:border-gray-700 p-4">
                                    <div>
                                        <p class="font-semibold text-[#152A4E] dark:text-white text-sm">{{ $request->training_title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ $request->preferred_date->format('F j, Y') }} &middot; {{ $request->user->name }} &middot; {{ $request->venue ?? __('Venue TBD') }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 inline-flex items-center text-xs font-semibold rounded-full border px-3 py-1.5 {{ $statusColors[$request->status] ?? '' }}">
                                        {{ $request->statusLabel() }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @endif

        </div>
    </div>
</x-app-layout>
