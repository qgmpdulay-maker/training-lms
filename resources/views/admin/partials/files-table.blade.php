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
                    <th class="py-2 pr-4">{{ __('Certificate') }}</th>
                    <th class="py-2 pr-4">{{ __('ATAR') }}</th>
                    <th class="py-2 pr-4">{{ __('Evaluation') }}</th>
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
                                'field' => 'certificate_file',
                                'path' => $record->certificate_file_path,
                                'accept' => '.pdf,.jpg,.jpeg,.png',
                            ])
                        </td>
                        <td class="py-3 pr-4">
                            @include('admin.partials.file-upload-cell', [
                                'record' => $record,
                                'field' => 'atar_file',
                                'path' => $record->atar_file_path,
                                'accept' => '.pdf',
                            ])
                        </td>
                        <td class="py-3 pr-4">
                            @if ($record->trainingEvaluation)
                                <a href="{{ route('admin.evaluations.edit', $record) }}"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-full pl-2 pr-2.5 py-1 hover:bg-green-100 dark:hover:bg-green-900/50 transition whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ __('Edit Evaluation') }}
                                </a>
                            @else
                                <a href="{{ route('admin.evaluations.edit', $record) }}"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400 border border-dashed border-gray-300 dark:border-gray-600 rounded-full pl-2 pr-2.5 py-1 hover:text-[#152A4E] dark:hover:text-white hover:border-gray-400 dark:hover:border-gray-500 transition whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    {{ __('Add Evaluation') }}
                                </a>
                            @endif
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
