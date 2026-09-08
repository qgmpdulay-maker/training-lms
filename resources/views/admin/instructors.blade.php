<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Instructors') }}
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

            <!-- Add Instructor -->
            @include('admin.partials.instructor-form')

            <!-- Instructor Roster -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Instructor Roster') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Instructors on file for :region.', ['region' => Auth::user()->region]) }}</p>

                <form id="instructor-roster-form" data-live-form data-live-section="instructor-roster" data-live-target="instructor-roster-results"
                    method="GET" action="{{ route('admin.instructors.index') }}" class="w-full mb-5">
                    <input type="hidden" name="_section" value="instructor-roster">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                        <div class="relative flex-1 min-w-[14rem]">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <input type="text" name="instructors_q" value="{{ $instructorSearch }}" placeholder="{{ __('Search name, training type, certificate code, agency, or LGU…') }}"
                                class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                        </div>
                        <div class="relative sm:w-56 shrink-0">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                            </svg>
                            <select name="training_type"
                                class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 pr-9 py-2.5 transition">
                                <option value="">{{ __('All Training Types') }}</option>
                                @foreach ($trainingTypes as $type)
                                    <option value="{{ $type }}" @selected($selectedTrainingType === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit"
                            class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                    </div>
                    @if ($instructorSearch !== '' || $selectedTrainingType !== '')
                        <a href="{{ route('admin.instructors.index') }}"
                            class="inline-block mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Clear filters') }}
                        </a>
                    @endif
                </form>

                <div id="instructor-roster-results">
                    @include('admin.partials.instructor-roster')
                </div>
            </div>

        </div>
    </div>

    @include('admin.partials.live-search-script')
</x-app-layout>
