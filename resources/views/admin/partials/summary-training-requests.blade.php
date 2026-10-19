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
                    <th class="py-2 pr-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($records as $record)
                    @php $recordParticipants = $record->effectiveParticipants(); @endphp
                    <tr>
                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">
                            {{ $record->training_title }}
                            <div class="flex items-center gap-1.5 mt-1 font-normal">
                                @if ($record->region)
                                    <span class="text-xs text-gray-400">{{ $record->region }}</span>
                                @endif
                                <span class="inline-flex items-center text-[10px] font-semibold uppercase tracking-wide rounded-full border px-1.5 py-0.5
                                    {{ $record->source === \App\Models\TrainingRequest::SOURCE_PUBLIC_PORTAL
                                        ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700'
                                        : 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600' }}">
                                    {{ $record->sourceLabel() }}
                                </span>
                            </div>
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->requesting_agency }}</td>
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
                        <td class="py-3 pr-4 text-right whitespace-nowrap">
                            {{-- The ATAR for this training, for whoever writes it: a
                                 Regional Admin, once the training is completed. Opens the
                                 draft if one exists, otherwise starts one with this
                                 training already picked. --}}
                            @if (Auth::user()->isAdmin() && $record->status === \App\Models\TrainingRequest::STATUS_COMPLETED)
                                @if ($record->atarReport)
                                    <a href="{{ route('admin.atar-reports.edit', $record->atarReport) }}"
                                        class="inline-flex items-center justify-center text-xs font-semibold text-[#152A4E] dark:text-white border border-[#152A4E]/30 dark:border-white/30 rounded-md px-3 py-2 me-1 hover:bg-[#152A4E]/5 dark:hover:bg-white/10 transition">
                                        {{ __('Open ATAR') }}
                                    </a>
                                @else
                                    <a href="{{ route('admin.atar-reports.create', ['training_request_id' => $record->id]) }}"
                                        class="inline-flex items-center justify-center text-xs font-semibold text-[#152A4E] dark:text-white border border-[#152A4E]/30 dark:border-white/30 rounded-md px-3 py-2 me-1 hover:bg-[#152A4E]/5 dark:hover:bg-white/10 transition">
                                        {{ __('Create ATAR') }}
                                    </a>
                                @endif
                            @endif

                            {{-- Always "View": the page opens as the record, read-only for a
                                 Regional Admin (summary-edit hides the Save button for them)
                                 and still editable for a Super Admin, who needs it just in
                                 case something has to be corrected. --}}
                            <a href="{{ route('admin.summary.edit', $record) }}"
                                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                {{ __('View') }}
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
