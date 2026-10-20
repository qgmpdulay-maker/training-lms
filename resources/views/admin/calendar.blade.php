<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Calendar') }}
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

            {{-- Central's whole-country view. Same month, same controls, same
                 palette as a region's month grid (admin/calendar-month) — the
                 only difference is that eighteen regions are listed by day
                 rather than drawn as one unreadable wall calendar. Picking a
                 region in the header opens that region's grid. --}}
            @include('admin.partials.calendar-header', [
                'region' => null,
                'regionPicker' => true,
                'canSchedule' => false,
                'countLabel' => trans_choice(
                    ':count training across all regions|:count trainings across all regions',
                    $monthCount,
                    ['count' => $monthCount],
                ),
            ])

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                @forelse ($days as $day)
                    {{-- The day strip is the agenda's answer to the grid's
                         weekday header: same grey, same small uppercase type. --}}
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-900/40 border-b border-gray-200 dark:border-gray-700 {{ $loop->first ? '' : 'border-t' }}">
                        <span class="text-[11px] font-semibold uppercase tracking-wider {{ $day['isToday'] ? 'text-[#152A4E] dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $day['date']->format('D j F') }}
                        </span>
                        @if ($day['isToday'])
                            <span class="inline-flex items-center rounded-full bg-[#152A4E] text-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">
                                {{ __('Today') }}
                            </span>
                        @endif
                        <span class="ms-auto text-[11px] tabular-nums text-gray-400 dark:text-gray-500">
                            {{ trans_choice(':count training|:count trainings', $day['trainings']->count(), ['count' => $day['trainings']->count()]) }}
                        </span>
                    </div>

                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($day['trainings'] as $training)
                            <li>
                                <a href="{{ route('admin.summary.edit', $training) }}"
                                    class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                    {{-- The same solid category colour the grid
                                         draws its bars in, stood on its end. --}}
                                    <span class="w-1.5 h-9 shrink-0 rounded-sm {{ $categorySwatches[$training->category] ?? 'bg-gray-400' }}"></span>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-[#152A4E] dark:text-white truncate">{{ $training->training_title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ $training->dateRangeLabel() }} &middot; {{ $training->venue ?: __('Venue TBD') }}
                                        </p>
                                    </div>

                                    {{-- The one thing a region's own calendar
                                         never has to say, and the thing Central
                                         is reading for. --}}
                                    <span class="shrink-0 inline-flex items-center text-[10px] font-semibold uppercase tracking-wide rounded-full border px-2 py-1 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600">
                                        {{ $training->region ?: __('No region') }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @empty
                    <p class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ $search !== ''
                            ? __('Nothing in :month matches ":search".', ['month' => $month->format('F Y'), 'search' => $search])
                            : __('Nothing scheduled in :month yet.', ['month' => $month->format('F Y')]) }}
                    </p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
