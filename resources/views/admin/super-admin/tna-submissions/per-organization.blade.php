<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('TNA Data per LGU / Organization') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <a href="{{ route('admin.tna-submissions.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-[#152A4E] dark:text-white hover:text-[#E2762D]">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to Submissions') }}
            </a>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach ([
                    ['label' => 'TNA Submissions', 'value' => $summary['submissions'], 'hint' => $summary['reviewed'].' reviewed'],
                    ['label' => 'LGUs / Organizations', 'value' => $summary['organizations'], 'hint' => null],
                    ['label' => 'Training Topics', 'value' => $summary['topics'], 'hint' => null],
                    ['label' => 'Personnel Assessed', 'value' => $summary['personnel'], 'hint' => null],
                    ['label' => 'With Results PDF', 'value' => $summary['with_results'], 'hint' => null],
                ] as $card)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __($card['label']) }}</div>
                        <div class="text-2xl font-bold text-[#152A4E] dark:text-white mt-1">{{ $card['value'] }}</div>
                        @if ($card['hint'])
                            <div class="text-xs text-gray-400 mt-0.5">{{ $card['hint'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Per organization -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-5">{{ __('TNA Data per LGU / Organization') }}</h2>

                @if ($organizationBreakdown->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No submissions match the current filters.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('LGU / Organization') }}</th>
                                    <th class="py-2 pr-4">{{ __('Region(s)') }}</th>
                                    <th class="py-2 pr-4">{{ __('Submissions') }}</th>
                                    <th class="py-2 pr-4">{{ __('Topics') }}</th>
                                    <th class="py-2 pr-4">{{ __('Personnel') }}</th>
                                    <th class="py-2 pr-4">{{ __('With Results') }}</th>
                                    <th class="py-2 pr-4">{{ __('Pending') }}</th>
                                    <th class="py-2 pr-4">{{ __('Reviewed') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($organizationBreakdown as $row)
                                    <tr>
                                        <td class="py-3 pr-4">
                                            <div class="font-medium text-[#152A4E] dark:text-white">{{ $row['organization'] }}</div>
                                            @if ($row['agency_type'])
                                                <div class="text-xs text-gray-400">{{ $row['agency_type'] }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['regions'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['submissions'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['topics'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['personnel'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['with_results'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['pending'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['reviewed'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Per topic -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-5">{{ __('Most Requested Training Topics') }}</h2>

                @if ($topicBreakdown->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No submissions match the current filters.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Topic') }}</th>
                                    <th class="py-2 pr-4">{{ __('Submissions') }}</th>
                                    <th class="py-2 pr-4">{{ __('Organizations') }}</th>
                                    <th class="py-2 pr-4">{{ __('Personnel') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($topicBreakdown as $row)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $row['topic'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['submissions'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['organizations'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['personnel'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
