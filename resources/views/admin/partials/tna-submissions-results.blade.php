@if ($submissions->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ $submissionSearch !== '' ? __('No submissions match your search.') : __('No Training Needs Assessment submissions yet.') }}
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <th class="py-2 pr-4">{{ __('Participant') }}</th>
                    <th class="py-2 pr-4">{{ __('Organization') }}</th>
                    <th class="py-2 pr-4">{{ __('Top Category') }}</th>
                    <th class="py-2 pr-4">{{ __('Recommended Training') }}</th>
                    <th class="py-2 pr-4">{{ __('Max Hours') }}</th>
                    <th class="py-2 pr-4">{{ __('Submitted') }}</th>
                    @if (Auth::user()->isAdmin())
                        <th class="py-2 pr-4"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($submissions as $submission)
                    <tr>
                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">
                            {{ $submission->user->name }}
                            <div class="text-xs text-gray-400 font-normal">{{ $submission->user->email }}</div>
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->user->organization }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->top_category ?? '—' }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->recommended_training_title }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->max_hours ?? '—' }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->created_at->format('M j, Y') }}</td>
                        @if (Auth::user()->isAdmin())
                            <td class="py-3 pr-4 text-right">
                                @if ($submission->recommended_training_slug)
                                    <a href="{{ route('admin.training-requests.create', ['training' => $submission->recommended_training_slug]) }}"
                                        class="inline-flex items-center text-xs font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D] whitespace-nowrap">
                                        {{ __('Request Training') }} &rarr;
                                    </a>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5 tna-submissions-pagination">
        {{ $submissions->links() }}
    </div>
@endif
