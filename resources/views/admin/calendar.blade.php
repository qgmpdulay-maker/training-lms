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
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Filter') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Narrow the agenda below by region or a search for a specific training/agency.') }}</p>

                    <form method="GET" action="{{ route('admin.calendar') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="region" :value="__('Region')" />
                            <select id="region" name="region" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                                <option value="">{{ __('All Regions') }}</option>
                                @foreach ($regions as $regionOption)
                                    <option value="{{ $regionOption }}" @selected($filters['region'] === $regionOption)>{{ $regionOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="search" :value="__('Search')" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('e.g. PCO, TA for LGU/NGA') }}" value="{{ $search }}" />
                        </div>
                        <div class="flex items-end gap-3">
                            <button type="submit" class="w-full inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-4 py-2 hover:bg-[#1E3A66] transition">
                                {{ __('Apply Filters') }}
                            </button>
                            @if ($filters['region'] || $search !== '')
                                <a href="{{ route('admin.calendar') }}" class="shrink-0 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                    {{ __('Reset') }}
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 sm:p-5">
                    <form method="GET" action="{{ route('admin.calendar') }}" class="flex items-center gap-3">
                        <x-input-label for="search" :value="__('Search')" class="sr-only" />
                        <x-text-input id="search" name="search" type="text" class="block w-full text-sm" placeholder="{{ __('Search this region\'s trainings or agencies (e.g. PCO, TA for LGU/NGA)') }}" value="{{ $search }}" />
                        <button type="submit" class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-4 py-2 hover:bg-[#1E3A66] transition">
                            {{ __('Search') }}
                        </button>
                        @if ($search !== '')
                            <a href="{{ route('admin.calendar') }}" class="shrink-0 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Reset') }}
                            </a>
                        @endif
                    </form>
                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-3">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <span>{{ __(':region agenda — trainings, holidays, and suspensions.', ['region' => Auth::user()->region]) }}</span>
                    </div>
                </div>
            @endif

            <div class="flex items-center gap-4 flex-wrap text-xs font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-orange-400"></span>{{ __('TA') }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>{{ __('Holiday') }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>{{ __('Suspension') }}</span>
            </div>

            @if ($groupedByMonth->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No scheduled trainings, holidays, or suspensions match yet.') }}
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
                        <div x-show="activeMonth === @js($month)" x-cloak class="mt-5 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                            <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-3">{{ $month }}</h2>
                            <ul class="space-y-2">
                                @foreach ($entries as $entry)
                                    @if ($entry->kind === 'training')
                                        @php
                                            $request = $entry->model;
                                            $categoryShort = ['apb' => 'APB', 'ta' => 'TA'];
                                        @endphp
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
                                    @else
                                        @php $event = $entry->model; @endphp
                                        <li class="flex items-center justify-between gap-3 rounded-lg border border-dashed border-gray-200 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-700/20 px-3 py-2">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-[#152A4E] dark:text-white text-sm truncate">{{ $event->title }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                    {{ $event->date->format('M j, Y') }}
                                                    @if ($event->spansMultipleDays())
                                                        &ndash; {{ $event->end_date->format('M j, Y') }}
                                                    @endif
                                                    @if (Auth::user()->isSuperAdmin())
                                                        &middot; {{ $event->regionLabel() }}
                                                    @endif
                                                    @if ($event->description)
                                                        &middot; {{ $event->description }}
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="shrink-0 flex items-center gap-2">
                                                <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1 {{ $eventTypeColors[$event->type] ?? '' }}">
                                                    {{ $event->typeLabel() }}
                                                </span>
                                                @if (Auth::user()->isSuperAdmin())
                                                    <form method="POST" action="{{ route('admin.calendar-events.destroy', $event) }}" onsubmit="return confirm('{{ __('Remove :title from the calendar?', ['title' => $event->title]) }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition" title="{{ __('Remove') }}">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
