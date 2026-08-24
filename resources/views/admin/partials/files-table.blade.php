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
                    <tr>
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
                            <form method="POST" action="{{ route('admin.tools.files', $record) }}" enctype="multipart/form-data" class="flex items-center gap-1.5">
                                @csrf
                                @if ($record->certificate_file_path)
                                    <a href="{{ asset('storage/' . $record->certificate_file_path) }}" target="_blank"
                                        class="text-xs font-semibold text-[#152A4E] dark:text-white underline shrink-0">{{ __('View') }}</a>
                                @endif
                                <input type="file" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png"
                                    class="w-36 text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-100 dark:file:bg-gray-700 file:text-gray-700 dark:file:text-gray-200">
                                <button type="submit"
                                    class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-2.5 py-1.5 hover:bg-[#1E3A66] transition">
                                    {{ __('Upload') }}
                                </button>
                            </form>
                        </td>
                        <td class="py-3 pr-4">
                            <form method="POST" action="{{ route('admin.tools.files', $record) }}" enctype="multipart/form-data" class="flex items-center gap-1.5">
                                @csrf
                                @if ($record->atar_file_path)
                                    <a href="{{ asset('storage/' . $record->atar_file_path) }}" target="_blank"
                                        class="text-xs font-semibold text-[#152A4E] dark:text-white underline shrink-0">{{ __('View') }}</a>
                                @endif
                                <input type="file" name="atar_file" accept=".pdf"
                                    class="w-36 text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-100 dark:file:bg-gray-700 file:text-gray-700 dark:file:text-gray-200">
                                <button type="submit"
                                    class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-2.5 py-1.5 hover:bg-[#1E3A66] transition">
                                    {{ __('Upload') }}
                                </button>
                            </form>
                        </td>
                        <td class="py-3 pr-4">
                            <a href="{{ route('admin.evaluations.edit', $record) }}"
                                class="text-xs font-semibold text-[#152A4E] dark:text-white underline whitespace-nowrap">
                                {{ $record->trainingEvaluation ? __('Edit Evaluation') : __('Add Evaluation') }}
                            </a>
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
