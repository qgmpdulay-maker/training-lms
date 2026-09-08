{{--
    Shared "Graduates by LGU" card, used by both the regional-admin and
    super-admin dashboards. Expects $graduatesByLgu (array keyed by region
    title, see DashboardController::graduatesByLgu()) and $region (string
    region code when scoped to one region, or null).
--}}
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates by LGU') }}</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        {{ __('Completed trainings grouped by the LGU recorded on Summary.') }}
        {{ __('For a point-level map of graduates by LGU / Volunteers / RDRRMC member agencies, see the') }}
        <a href="{{ route('admin.monitoring.map', $region ? ['regions' => [$region]] : []) }}" class="font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D]">{{ __('Graduates Map') }}</a>.
    </p>
    <p class="text-xs text-amber-700 dark:text-amber-400 mb-5">
        {{ __('"Teams Organized" isn\'t shown here since there\'s no team data in the system yet.') }}
    </p>

    @if (empty($graduatesByLgu))
        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
            {{ __('No completed trainings with an LGU recorded yet.') }}
        </div>
    @elseif (count($graduatesByLgu) === 1)
        @php $regionMax = max(array_column(reset($graduatesByLgu)['lgus'], 'total')); @endphp
        <div class="space-y-3">
            @foreach (reset($graduatesByLgu)['lgus'] as $row)
                <div class="flex items-center gap-3">
                    <div class="w-48 shrink-0 text-xs font-medium text-gray-600 dark:text-gray-300 truncate">{{ $row['lgu'] }}</div>
                    <div class="flex-1 h-5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                        <div class="h-full rounded-full bg-[#152A4E] dark:bg-[#E2762D] transition-all" style="width: {{ round(($row['total'] / $regionMax) * 100) }}%;"></div>
                    </div>
                    <div class="w-8 text-right text-xs font-semibold text-gray-700 dark:text-gray-200 tabular-nums">{{ $row['total'] }}</div>
                </div>
            @endforeach
        </div>
    @else
        <div x-data="{ activeLguRegion: @js(array_key_first($graduatesByLgu)) }">
            <div class="flex items-center gap-1 overflow-x-auto bg-gray-100 dark:bg-gray-900/40 rounded-xl p-1.5">
                @foreach ($graduatesByLgu as $regionName => $regionGroup)
                    <button type="button" @click="activeLguRegion = @js($regionName)"
                        :class="activeLguRegion === @js($regionName)
                            ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition whitespace-nowrap">
                        {{ $regionName }}
                        <span :class="activeLguRegion === @js($regionName)
                                ? 'bg-[#152A4E]/10 text-[#152A4E] dark:bg-white/15 dark:text-white px-1.5 py-0.5 rounded-full text-xs font-semibold'
                                : 'text-gray-400 dark:text-gray-500 text-xs font-normal'">
                            {{ $regionGroup['total'] }}
                        </span>
                    </button>
                @endforeach
            </div>

            @foreach ($graduatesByLgu as $regionName => $regionGroup)
                <div x-show="activeLguRegion === @js($regionName)" x-cloak class="mt-5">
                    @php $regionMax = max(array_column($regionGroup['lgus'], 'total')); @endphp
                    <div class="space-y-3">
                        @foreach ($regionGroup['lgus'] as $row)
                            <div class="flex items-center gap-3">
                                <div class="w-48 shrink-0 text-xs font-medium text-gray-600 dark:text-gray-300 truncate">{{ $row['lgu'] }}</div>
                                <div class="flex-1 h-5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full bg-[#152A4E] dark:bg-[#E2762D] transition-all" style="width: {{ round(($row['total'] / $regionMax) * 100) }}%;"></div>
                                </div>
                                <div class="w-8 text-right text-xs font-semibold text-gray-700 dark:text-gray-200 tabular-nums">{{ $row['total'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
