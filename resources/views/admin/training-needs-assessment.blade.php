<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Training Needs Assessment') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Training Demand -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('What Training Is Needed') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __("Based on what participants answered on their Training Needs Assessment — not a guess, this is literally what the assessment recommended for each of them.") }}</p>

                @if (empty($trainingDemand['bars']))
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No Training Needs Assessment submissions yet — this fills in once participants start taking it.') }}
                    </div>
                @else
                    <div class="flex items-start gap-3 text-sm text-blue-800 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg px-4 py-3 mb-5">
                        <svg class="w-5 h-5 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                        </svg>
                        <span>
                            {{ __('Most needed right now: :title', ['title' => $trainingDemand['top']['title']]) }}
                            — {{ __('recommended to :count of :total participants who took the assessment (:percent%).', ['count' => $trainingDemand['top']['count'], 'total' => $trainingDemand['total'], 'percent' => $trainingDemand['top']['percent']]) }}
                        </span>
                    </div>

                    <div class="space-y-3">
                        @foreach ($trainingDemand['bars'] as $bar)
                            <div class="flex items-center gap-3">
                                <div class="w-56 shrink-0 text-xs font-medium text-gray-600 dark:text-gray-300 truncate">{{ $bar['title'] }}</div>
                                <div class="flex-1 h-5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full bg-[#152A4E] dark:bg-[#E2762D] transition-all" style="width: {{ $bar['percent'] }}%;"></div>
                                </div>
                                <div class="w-20 text-right text-xs font-semibold text-gray-700 dark:text-gray-200 tabular-nums">{{ $bar['count'] }} ({{ $bar['percent'] }}%)</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('TNA Submissions') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ Auth::user()->isAdmin()
                        ? __('Training Needs Assessments submitted by participants in :region, most recent first.', ['region' => Auth::user()->region])
                        : __('Every Training Needs Assessment participants have submitted, across all regions, most recent first.') }}
                </p>

                @if ($submissions->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No Training Needs Assessment submissions yet.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Participant') }}</th>
                                    <th class="py-2 pr-4">{{ __('Organization') }}</th>
                                    <th class="py-2 pr-4">{{ __('Top Category') }}</th>
                                    <th class="py-2 pr-4">{{ __('Recommended Training') }}</th>
                                    <th class="py-2 pr-4">{{ __('Max Hours') }}</th>
                                    <th class="py-2 pr-4">{{ __('Submitted') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($submissions as $submission)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">
                                            {{ $submission->user->name }}
                                            <div class="text-xs text-gray-400 font-normal">{{ $submission->user->email }}</div>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->user->organization }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->top_category ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->recommended_training_title }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->max_hours ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->created_at->format('M j, Y') }}</td>
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
