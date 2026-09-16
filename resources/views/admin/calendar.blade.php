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

            @if (Auth::user()->isSuperAdmin())
                <div class="flex justify-end">
                    <a href="{{ route('admin.trainings.create') }}"
                        class="inline-flex items-center gap-2 bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ __('Schedule a Training') }}
                    </a>
                </div>
            @endif

            @if ($filters !== null)
                <x-chart-card body-class="p-6 sm:p-8"
                    :title="__('Filter')"
                    :subtitle="__('Narrow the agenda below by region or a search for a specific training/agency.')">

                    <form method="GET" action="{{ route('admin.calendar') }}">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                            <div class="relative flex-1 min-w-[14rem]">
                                <x-input-label for="search" :value="__('Search')" class="sr-only" />
                                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                                <input id="search" name="search" type="text" value="{{ $search }}" placeholder="{{ __('e.g. PCO, TA for LGU/NGA') }}" oninput="debounceCalendarFilter(this)"
                                    class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                            </div>
                            <div class="relative sm:w-56 shrink-0">
                                <x-input-label for="region" :value="__('Region')" class="sr-only" />
                                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                                </svg>
                                <select id="region" name="region" onchange="this.form.submit()"
                                    class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 pr-9 py-2.5 transition">
                                    <option value="">{{ __('All Regions') }}</option>
                                    @foreach ($regions as $regionOption)
                                        <option value="{{ $regionOption }}" @selected($filters['region'] === $regionOption)>{{ $regionOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if ($filters['region'] || $search !== '')
                            <a href="{{ route('admin.calendar') }}" class="inline-block mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Reset') }}
                            </a>
                        @endif
                    </form>
                </x-chart-card>
            @else
                <x-chart-card body-class="p-4 sm:p-5"
                    :title="__('Filter')"
                    :subtitle="__(':region training schedule.', ['region' => Auth::user()->region])">
                    <form method="GET" action="{{ route('admin.calendar') }}" class="flex items-center gap-3">
                        <x-input-label for="search" :value="__('Search')" class="sr-only" />
                        <div class="relative flex-1 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                            <svg class="w-4 h-4 text-gray-400 absolute left-5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <input id="search" name="search" type="text" value="{{ $search }}" placeholder="{{ __('Search this region\'s trainings or agencies (e.g. PCO, TA for LGU/NGA)') }}" oninput="debounceCalendarFilter(this)"
                                class="block w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                        </div>
                        @if ($search !== '')
                            <a href="{{ route('admin.calendar') }}" class="shrink-0 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Reset') }}
                            </a>
                        @endif
                    </form>
                </x-chart-card>
            @endif

            <div class="flex items-center gap-4 flex-wrap text-xs font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-orange-400"></span>{{ __('TA') }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-blue-400"></span>{{ __('APB') }}</span>
            </div>

            @if ($groupedByMonth->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No scheduled trainings match yet.') }}
                    </div>
                </div>
            @else
                <div x-data="{ activeMonth: @js($defaultMonth) }">
                    <div class="flex items-center gap-1 overflow-x-auto bg-gray-100 dark:bg-gray-900/40 rounded-xl p-1.5">
                        @foreach ($groupedByMonth as $month => $entries)
                            <button type="button" @click="activeMonth = @js($month)"
                                x-init="@js($month) === @js($defaultMonth) && $nextTick(() => $el.scrollIntoView({ block: 'nearest', inline: 'center' }))"
                                :class="activeMonth === @js($month)
                                    ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition whitespace-nowrap">
                                {{ $month }}
                                <span :class="activeMonth === @js($month)
                                        ? 'bg-[#152A4E]/10 text-[#152A4E] dark:bg-white/15 dark:text-white px-1.5 py-0.5 rounded-full text-xs font-semibold'
                                        : 'text-gray-400 dark:text-gray-500 text-xs font-normal'">
                                    {{ $entries->count() }}
                                </span>
                            </button>
                        @endforeach
                    </div>

                    @foreach ($groupedByMonth as $month => $entries)
                        {{-- Blade does not compile @js() inside a component attribute, so the
                             Alpine expression is built in PHP and bound with ":x-show". --}}
                        <x-chart-card :x-show="'activeMonth === ' . json_encode($month)" x-cloak class="mt-5"
                            body-class="p-6 sm:p-8" :title="$month">
                            <ul class="space-y-2">
                                @php $categoryShort = ['apb' => 'APB', 'ta' => 'TA']; @endphp
                                @foreach ($entries as $request)
                                    <li class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 dark:border-gray-700 px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-[#152A4E] dark:text-white text-sm truncate">{{ $request->training_title }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ $request->preferred_date->format('M j, Y') }} &middot; {{ $request->venue ?? __('Venue TBD') }}
                                            </p>
                                        </div>
                                        <span class="shrink-0 inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1 {{ $categoryColors[$request->category] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600' }}">
                                            {{ $categoryShort[$request->category] ?? '—' }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </x-chart-card>
                    @endforeach
                </div>
            @endif

        </div>
    </div>

    <script>
        let calendarFilterTimeout;
        function debounceCalendarFilter(el) {
            clearTimeout(calendarFilterTimeout);
            calendarFilterTimeout = setTimeout(() => el.form.submit(), 500);
        }
    </script>
</x-app-layout>
