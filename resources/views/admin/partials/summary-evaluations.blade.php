@if ($evaluations->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ $evaluationSearch !== '' ? __('No evaluations match your search.') : __('No evaluations submitted yet.') }}
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <th class="py-2 pr-4">{{ __('Participant') }}</th>
                    <th class="py-2 pr-4">{{ __('Training') }}</th>
                    <th class="py-2 pr-4">{{ __('Submitted') }}</th>
                    <th class="py-2 pr-4">{{ __('Overall Comments') }}</th>
                    <th class="py-2 pr-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($evaluations as $evaluation)
                    <tr>
                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $evaluation->user->name ?? __('Unknown participant') }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $evaluation->trainingRequest->training_title ?? '—' }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $evaluation->updated_at->format('M j, Y') }}</td>
                        <td class="py-3 pr-4 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $evaluation->overall_comments ?: '—' }}</td>
                        <td class="py-3 pr-4 text-right">
                            @if ($evaluation->trainingRequest)
                                <a href="{{ route('admin.summary.edit', $evaluation->trainingRequest) }}"
                                    class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                    {{ __('View') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5 evaluations-pagination">
        {{ $evaluations->links() }}
    </div>
@endif
