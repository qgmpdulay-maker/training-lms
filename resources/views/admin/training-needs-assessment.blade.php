<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Training Needs Assessment') }}
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
                <div class="flex items-center flex-wrap gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm px-5 py-3">
                    <span class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ __('Region') }}</span>
                    <form method="GET" action="{{ route('admin.training-needs-assessment') }}" class="flex items-center gap-2">
                        @if ($submissionSearch !== '')
                            <input type="hidden" name="tna_q" value="{{ $submissionSearch }}">
                        @endif
                        <select name="region" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="">{{ __('All Regions (Philippines)') }}</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption }}" @selected($selectedRegion === $regionOption)>{{ $regionOption }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if ($selectedRegion)
                        <a href="{{ route('admin.training-needs-assessment', array_filter(['tna_q' => $submissionSearch ?: null])) }}"
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Reset to all regions') }}
                        </a>
                    @endif
                </div>
            @endif

            <div id="tna-submissions" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between flex-wrap gap-3 mb-1">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">{{ __('TNA Submissions') }}</h2>
                    <form data-live-form data-live-section="tna-submissions" data-live-target="tna-submissions-results"
                        method="GET" action="{{ route('admin.training-needs-assessment') }}#tna-submissions" class="flex items-center flex-wrap gap-2">
                        <input type="hidden" name="_section" value="tna-submissions">
                        @if ($selectedRegion)
                            <input type="hidden" name="region" value="{{ $selectedRegion }}">
                        @endif
                        <input type="text" name="tna_q" value="{{ $submissionSearch }}" placeholder="{{ __('Search participant, organization, category, or date…') }}"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-72">
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                        @if ($submissionSearch !== '')
                            <a href="{{ route('admin.training-needs-assessment', array_filter(['region' => $selectedRegion ?: null])) }}#tna-submissions"
                                class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </form>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    @if (Auth::user()->isAdmin())
                        {{ __('Training Needs Assessments submitted by participants in :region, most recent first.', ['region' => Auth::user()->region]) }}
                    @elseif ($selectedRegion)
                        {{ __('Training Needs Assessments submitted by participants in :region, most recent first.', ['region' => $selectedRegion]) }}
                    @else
                        {{ __('Every Training Needs Assessment participants have submitted, across all regions, most recent first.') }}
                    @endif
                </p>

                <div id="tna-submissions-results">
                    @include('admin.partials.tna-submissions-results')
                </div>
            </div>

        </div>
    </div>

    @include('admin.partials.live-search-script')
</x-app-layout>
