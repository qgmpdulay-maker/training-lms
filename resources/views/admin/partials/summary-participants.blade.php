@if ($participants->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ $participantSearch !== '' ? __('No participants match your search.') : __('No participants registered yet.') }}
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <th class="py-2 pr-4"></th>
                    <th class="py-2 pr-4">{{ __('Name') }}</th>
                    <th class="py-2 pr-4">{{ __('Age / Sex') }}</th>
                    <th class="py-2 pr-4">{{ __('Participant Type') }}</th>
                    <th class="py-2 pr-4">{{ __('Agency / Organization') }}</th>
                    <th class="py-2 pr-4">{{ __('Email') }}</th>
                    <th class="py-2 pr-4">{{ __('Contact Number') }}</th>
                    <th class="py-2 pr-4">{{ __('Training') }}</th>
                    <th class="py-2 pr-4">{{ __('Certificate') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($participants as $participant)
                    <tr>
                        <td class="py-3 pr-4">
                            @if ($participant->picture)
                                <img src="{{ asset('storage/'.$participant->picture) }}" alt="{{ $participant->name }}" class="w-9 h-9 object-cover rounded-full border border-gray-200 dark:border-gray-600">
                            @else
                                <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600"></div>
                            @endif
                        </td>
                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $participant->name }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->age }} / {{ $participant->sex }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->participant_type }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->organization ?: $participant->agency }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->email }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->mobile_number }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                            @if ($participant->latestTrainingRequest)
                                <div class="text-[#152A4E] dark:text-white">{{ $participant->latestTrainingRequest->training_title }}</div>
                                @if ($participant->latestTrainingRequest->lgu)
                                    <div class="text-xs text-gray-400">{{ $participant->latestTrainingRequest->lgu }}</div>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                            @if ($participant->latestTrainingRequest?->certificate_remarks)
                                <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600">
                                    {{ $certificateRemarksLabels[$participant->latestTrainingRequest->certificate_remarks] ?? $participant->latestTrainingRequest->certificate_remarks }}
                                </span>
                                @if ($participant->latestTrainingRequest->certificate_code)
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $participant->latestTrainingRequest->certificate_code }}</div>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5 participants-pagination">
        {{ $participants->links() }}
    </div>
@endif
