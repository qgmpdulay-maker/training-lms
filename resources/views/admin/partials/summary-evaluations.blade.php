@if ($evaluatedTrainings->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ $evaluationSearch !== '' ? __('No evaluated trainings match your search.') : __('No evaluations submitted yet.') }}
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <th class="py-2 pr-4">{{ __('Training') }}</th>
                    <th class="py-2 pr-4">{{ __('Requesting Agency') }}</th>
                    <th class="py-2 pr-4">{{ __('Evaluations') }}</th>
                    <th class="py-2 pr-4">{{ __('Last Submitted') }}</th>
                    <th class="py-2 pr-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($evaluatedTrainings as $training)
                    {{-- "x of y" is against the training's own roster, so a half-answered
                         training is obvious at a glance without opening it. --}}
                    @php $rosterCount = $training->effectiveParticipants()->count(); @endphp
                    <tr>
                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">
                            {{ $training->training_title }}
                            @if ($training->region)
                                <div class="text-xs font-normal text-gray-400 mt-1">{{ $training->region }}</div>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $training->requesting_agency ?: '—' }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                            {{ __(':count of :total submitted', ['count' => $training->participant_evaluations_count, 'total' => $rosterCount]) }}
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                            {{ $training->participant_evaluations_max_updated_at
                                ? \Illuminate\Support\Carbon::parse($training->participant_evaluations_max_updated_at)->format('M j, Y')
                                : '—' }}
                        </td>
                        <td class="py-3 pr-4 text-right">
                            <a href="{{ route('admin.summary.evaluations', $training) }}"
                                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                {{ __('View') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5 evaluations-pagination">
        {{ $evaluatedTrainings->links() }}
    </div>
@endif
