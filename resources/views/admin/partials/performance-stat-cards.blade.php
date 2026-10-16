{{--
    The six Regional Performance stat cards at the top of both admin
    dashboards. Expects $summary (MonitoringController::summary()).
--}}
<div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
    @foreach ([
        ['label' => 'Trainings Conducted', 'value' => $summary['trainings'], 'hint' => null, 'accent' => '#2a78d6'],
        ['label' => 'Total Participants', 'value' => $summary['participants'], 'hint' => $summary['non_completers'].' non-completers', 'accent' => '#152A4E'],
        ['label' => 'Graduates', 'value' => $summary['graduates'], 'hint' => $summary['completion_rate'].' completion', 'accent' => '#0ca30c'],
        ['label' => 'Deployed', 'value' => $summary['deployed'], 'hint' => 'graduates sent to operations', 'accent' => '#b45309'],
        ['label' => 'LGUs Covered', 'value' => $summary['lgus'], 'hint' => null, 'accent' => '#03055A'],
        ['label' => 'NGAs Covered', 'value' => $summary['ngas'], 'hint' => null, 'accent' => '#E2762D'],
    ] as $card)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 border-l-4" style="border-left-color: {{ $card['accent'] }};">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 min-h-[2rem]">{{ __($card['label']) }}</div>
            <div class="text-2xl font-bold text-[#152A4E] dark:text-white mt-1">{{ $card['value'] }}</div>
            @if ($card['hint'])
                <div class="text-xs text-gray-400 mt-0.5">{{ $card['hint'] }}</div>
            @endif
        </div>
    @endforeach
</div>
