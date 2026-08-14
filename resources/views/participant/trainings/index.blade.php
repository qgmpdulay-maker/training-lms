<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Trainings') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div id="trainings" x-data="{
                search: '',
                category: 'All',
                get filtered() {
                    return trainings.filter(t =>
                        (this.category === 'All' || t.category === this.category) &&
                        (t.title.toLowerCase().includes(this.search.toLowerCase()))
                    );
                }
            }">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-lg font-bold text-[#152A4E] dark:text-white">{{ __('Available Trainings') }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Trainings currently being offered. Details will be updated as they become available.') }}</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <!-- Search -->
                        <div class="relative">
                            <input type="text" x-model="search" placeholder="{{ __('Search trainings...') }}"
                                class="w-full sm:w-64 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm pl-9 py-2.5">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>

                        <!-- Category filter -->
                        <select x-model="category"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm py-2.5">
                            <option value="All">{{ __('All Categories') }}</option>
                            @foreach (collect($trainings)->pluck('category')->unique()->sort() as $categoryOption)
                                <option value="{{ $categoryOption }}">{{ $categoryOption }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Training cards data -->
                <template x-init="
                    trainings = {{ Js::from($trainings) }}
                "></template>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <template x-for="training in filtered" :key="training.title">
                        <div x-data="{ open: false }" class="flex flex-col bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition overflow-hidden">
                            <div class="h-1.5 bg-gradient-to-r from-[#152A4E] to-[#E2762D]"></div>
                            <button type="button" @click="open = ! open" class="p-6 flex flex-col flex-1 text-left w-full">
                                <span class="inline-block w-fit text-[11px] font-semibold tracking-wide uppercase text-[#152A4E] dark:text-white bg-[#152A4E]/8 dark:bg-[#152A4E]/30 rounded-full px-2.5 py-1 mb-3"
                                    x-text="training.category"></span>

                                <h3 class="text-base font-bold text-[#152A4E] dark:text-white mb-2 leading-snug" x-text="training.title"></h3>

                                <p x-show="open" x-transition class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed flex-1 mb-4" x-text="training.description"></p>

                                <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">
                                        <svg class="w-4 h-4 text-[#E2762D]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span x-text="training.hours"></span> {{ __('training hours') }}
                                    </span>

                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-[#152A4E] dark:text-white">
                                        <span x-text="open ? '{{ __('Show less') }}' : '{{ __('Show details') }}'"></span>
                                        <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </span>
                                </div>
                            </button>
                        </div>
                    </template>
                </div>

                <p x-show="filtered.length === 0" class="text-sm text-gray-500 dark:text-gray-400 text-center py-12">
                    {{ __('No trainings match your search.') }}
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
