@if ($filesRecords->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ __('No training requests on record yet.') }}
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <th class="py-2 pr-4">{{ __('Training') }}</th>
                    <th class="py-2 pr-4">{{ __('Participants') }}</th>
                    <th class="py-2 pr-4">{{ __('ATAR') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($filesRecords as $record)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">
                            {{ $record->training_title }}
                            <div class="text-xs text-gray-400 font-normal">{{ $record->preferred_date->format('M j, Y') }}</div>
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                            @php $participants = $record->effectiveParticipants(); @endphp
                            @if ($participants->count() === 1)
                                {{ $participants->first()->name }}
                            @elseif ($participants->isEmpty())
                                <span class="text-gray-400">{{ __('None on file') }}</span>
                            @else
                                {{ trans_choice(':count participant|:count participants', $participants->count(), ['count' => $participants->count()]) }}
                            @endif
                        </td>
                        <td class="py-3 pr-4">
                            @include('admin.partials.file-upload-cell', [
                                'record' => $record,
                                'field' => 'atar_file',
                                'path' => $record->atar_file_path,
                                'accept' => '.pdf',
                            ])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5 files-pagination">
        {{ $filesRecords->links() }}
    </div>
@endif
