<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Training Needs Assessment') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('TNA Submissions') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Every Training Needs Assessment participants have submitted, most recent first.') }}</p>

                @if ($submissions->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No Training Needs Assessment submissions yet.') }}
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5">
                        {{ $submissions->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
