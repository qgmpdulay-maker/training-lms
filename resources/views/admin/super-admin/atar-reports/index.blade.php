<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ATAR') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @include('admin.super-admin.partials.atar-tabs')

            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white">{{ __('ATAR Reports') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('After Training Activity Reports — auto-generated from completed trainings or written from scratch.') }}</p>
                </div>
                <a href="{{ route('admin.atar-reports.create') }}"
                    class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition shrink-0">
                    {{ __('New ATAR') }}
                </a>
            </div>

            <form method="GET" class="w-full">
                <div class="flex items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('Search by title...') }}"
                            class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                    </div>
                    <button type="submit"
                        class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                        {{ __('Search') }}
                    </button>
                </div>
            </form>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ __('Title') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Venue') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Date(s)') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Source') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                                <th class="px-4 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($reports as $report)
                                <tr>
                                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100 max-w-sm truncate" title="{{ $report->title }}">{{ $report->title ?: __('(untitled)') }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $report->venue ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $report->date_range ?? '—' }}</td>
                                    {{-- Whether this report has a linked TrainingRequest (auto-generated) or not (written from scratch) --}}
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $report->trainingRequest ? __('Generated') : __('Written from scratch') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span @class([
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' => $report->status === 'draft',
                                            'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300' => $report->status === 'final',
                                        ])>
                                            {{ $report->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.atar-reports.pdf', $report) }}" target="_blank" class="text-[#152A4E] dark:text-blue-300 hover:underline text-xs font-semibold mr-3">{{ __('PDF') }}</a>
                                        <a href="{{ route('admin.atar-reports.edit', $report) }}" class="text-[#152A4E] dark:text-blue-300 hover:underline text-xs font-semibold mr-3">{{ __('Edit') }}</a>
                                        <form method="POST" action="{{ route('admin.atar-reports.destroy', $report) }}" class="inline" onsubmit="return confirm('{{ __('Delete this ATAR report?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-semibold">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                        {{ __('No ATAR reports yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $reports->links() }}
        </div>
    </div>
</x-app-layout>
