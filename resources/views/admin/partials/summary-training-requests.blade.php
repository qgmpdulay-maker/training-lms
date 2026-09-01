@if ($records->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ __('No training requests match this filter or search.') }}
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <th class="py-2 pr-4">{{ __('Training') }}</th>
                    <th class="py-2 pr-4">{{ __('Requesting Agency') }}</th>
                    <th class="py-2 pr-4">{{ __('Participants') }}</th>
                    <th class="py-2 pr-4">{{ __('Date & Venue') }}</th>
                    <th class="py-2 pr-4">{{ __('Status') }}</th>
                    <th class="py-2 pr-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($records as $record)
                    @php $recordParticipants = $record->effectiveParticipants(); @endphp
                    <tr>
                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $record->training_title }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                            {{ $record->requesting_agency }}
                            <div class="text-xs text-gray-400">{{ $record->contact_person }} &middot; {{ $record->contact_number }}</div>
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                            @if ($recordParticipants->isEmpty())
                                <span class="text-gray-400">{{ __('None on file') }}</span>
                            @else
                                {{ trans_choice(':count participant|:count participants', $recordParticipants->count(), ['count' => $recordParticipants->count()]) }}
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                            {{ $record->preferred_date->format('M j, Y') }}
                            <div class="text-xs text-gray-400">{{ $record->venue }}</div>
                        </td>
                        <td class="py-3 pr-4">
                            <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1 {{ $statusColors[$record->status] ?? '' }}">
                                {{ $record->statusLabel() }}
                            </span>
                        </td>
                        <td class="py-3 pr-4 text-right">
                            <a href="{{ route('admin.summary.edit', $record) }}"
                                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                {{ __('Manage') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5 training-requests-pagination">
        {{ $records->links() }}
    </div>
@endif
