<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Summary') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (Auth::user()->isAdmin())
                <div class="flex items-start gap-3 text-sm text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ __('This still shows records for all regions rather than just :region — only the Calendar tab filters to your own region so far.', ['region' => Auth::user()->region]) }}</span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                    <div>
                        <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Training Requests') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Every training request on record, most recent first. Click Manage to update its status, certificate details, or move its date and venue.') }}</p>
                    </div>
                    <form method="GET" class="flex items-center gap-2">
                        <select name="status" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @if ($records->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No training requests match this filter.') }}
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
                                    @php $participants = $record->effectiveParticipants(); @endphp
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $record->training_title }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                            {{ $record->requesting_agency }}
                                            <div class="text-xs text-gray-400">{{ $record->contact_person }} &middot; {{ $record->contact_number }}</div>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                            @if ($participants->isEmpty())
                                                <span class="text-gray-400">{{ __('None on file') }}</span>
                                            @else
                                                {{ trans_choice(':count participant|:count participants', $participants->count(), ['count' => $participants->count()]) }}
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

                    <div class="mt-5">
                        {{ $records->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
