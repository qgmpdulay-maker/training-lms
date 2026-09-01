<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Training Needs Assessment') }}
            </h2>
            @if (Auth::user()->isSuperAdmin())
                <a href="{{ route('admin.tna-submissions.index') }}"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-[#152A4E] dark:text-white hover:text-[#E2762D] dark:hover:text-[#E2762D]">
                    {{ __('LGU/Org TNA Forms & Results') }} &rarr;
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (Auth::user()->isSuperAdmin())
                <div class="flex items-center flex-wrap gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm px-5 py-3">
                    <span class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ __('Region') }}</span>
                    <form method="GET" action="{{ route('admin.training-needs-assessment') }}" class="flex items-center gap-2">
                        @if ($submissionSearch !== '')
                            <input type="hidden" name="tna_q" value="{{ $submissionSearch }}">
                        @endif
                        <select name="region" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="">{{ __('All Regions (Philippines)') }}</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption }}" @selected($selectedRegion === $regionOption)>{{ $regionOption }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if ($selectedRegion)
                        <a href="{{ route('admin.training-needs-assessment', array_filter(['tna_q' => $submissionSearch ?: null])) }}"
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Reset to all regions') }}
                        </a>
                    @endif
                </div>
            @endif

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

            @if (Auth::user()->isSuperAdmin())
                <div id="org-breakdown" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Needs Assessment per LGU / Organization') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Training Needs Assessment submissions grouped by the participant\'s LGU or organization.') }}</p>

                    @if ($organizationBreakdown->isEmpty())
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No Training Needs Assessment submissions yet.') }}
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                        <th class="py-2 pr-4">{{ __('LGU / Organization') }}</th>
                                        <th class="py-2 pr-4">{{ __('Region') }}</th>
                                        <th class="py-2 pr-4">{{ __('Submissions') }}</th>
                                        <th class="py-2 pr-4">{{ __('Most Needed Training') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($organizationBreakdown as $row)
                                        <tr>
                                            <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $row['organization'] }}</td>
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['region'] }}</td>
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['submissions'] }}</td>
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['top_training'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-5">
                            {{ $organizationBreakdown->links() }}
                        </div>
                    @endif
                </div>
            @endif

            <div id="tna-submissions" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between flex-wrap gap-3 mb-1">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">{{ __('TNA Submissions') }}</h2>
                    <form data-live-form data-live-section="tna-submissions" data-live-target="tna-submissions-results"
                        method="GET" action="{{ route('admin.training-needs-assessment') }}#tna-submissions" class="flex items-center flex-wrap gap-2">
                        <input type="hidden" name="_section" value="tna-submissions">
                        @if ($selectedRegion)
                            <input type="hidden" name="region" value="{{ $selectedRegion }}">
                        @endif
                        <input type="text" name="tna_q" value="{{ $submissionSearch }}" placeholder="{{ __('Search participant, organization, category, or date…') }}"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-72">
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                        @if ($submissionSearch !== '')
                            <a href="{{ route('admin.training-needs-assessment', array_filter(['region' => $selectedRegion ?: null])) }}#tna-submissions"
                                class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </form>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    @if (Auth::user()->isAdmin())
                        {{ __('Training Needs Assessments submitted by participants in :region, most recent first.', ['region' => Auth::user()->region]) }}
                    @elseif ($selectedRegion)
                        {{ __('Training Needs Assessments submitted by participants in :region, most recent first.', ['region' => $selectedRegion]) }}
                    @else
                        {{ __('Every Training Needs Assessment participants have submitted, across all regions, most recent first.') }}
                    @endif
                </p>

                <div id="tna-submissions-results">
                    @include('admin.partials.tna-submissions-results')
                </div>
            </div>

        </div>
    </div>

    @include('admin.partials.live-search-script')
</x-app-layout>
