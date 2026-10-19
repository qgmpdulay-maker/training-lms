@php $byRegion = $groupBy === \App\Http\Controllers\Admin\TrainingNeedsAssessmentController::GROUP_REGION; @endphp

@if ($groups->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ $submissionSearch !== ''
            ? ($byRegion ? __('No regions match your search.') : __('No cities match your search.'))
            : __('No Training Needs Assessment submissions yet.') }}
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <th class="py-2 pr-4">{{ $byRegion ? __('Region') : __('City') }}</th>
                    <th class="py-2 pr-4">{{ __('Assessments') }}</th>
                    <th class="py-2 pr-4">{{ __('Top Training Needs') }}</th>
                    <th class="py-2 pr-4">{{ __('Most Recommended') }}</th>
                    <th class="py-2 pr-4">{{ __('Latest') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($groups as $group)
                    <tr>
                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">
                            {{ $group['area'] }}
                            {{-- In city mode the region is worth naming: two cities can
                                 share a name across regions. --}}
                            @if (! $byRegion && $group['region'])
                                <div class="text-xs text-gray-400 font-normal">{{ $group['region'] }}</div>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                            {{ trans_choice(':count assessment|:count assessments', $group['submissions'], ['count' => $group['submissions']]) }}
                            <div class="text-xs text-gray-400">
                                {{ trans_choice('from :count participant|from :count participants', $group['participants'], ['count' => $group['participants']]) }}
                            </div>
                        </td>
                        <td class="py-3 pr-4">
                            @if ($group['top_needs'] === [])
                                <span class="text-gray-400">—</span>
                            @else
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($group['top_needs'] as $need)
                                        <span class="inline-flex items-center gap-1 text-xs font-medium rounded-full border px-2 py-0.5
                                            {{ $loop->first
                                                ? 'bg-[#152A4E]/5 dark:bg-white/10 text-[#152A4E] dark:text-white border-[#152A4E]/20 dark:border-white/20'
                                                : 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600' }}">
                                            {{ $need['category'] }}
                                            <span class="tabular-nums text-gray-400">{{ $need['total'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 max-w-xs">
                            <span class="block truncate" title="{{ $group['top_training'] }}">{{ $group['top_training'] ?? '—' }}</span>
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                            {{ $group['latest'] ? \Illuminate\Support\Carbon::parse($group['latest'])->format('M j, Y') : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5 tna-submissions-pagination">
        {{ $groups->links() }}
    </div>
@endif
