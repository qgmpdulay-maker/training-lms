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

            <x-chart-card id="tna-submissions" class="scroll-mt-24" body-class="p-6 sm:p-8"
                :title="__('TNA Submissions')">
                <x-slot:action>
                    {{-- For agencies that would rather fill it in on paper and
                         hand it back to their Regional Office. --}}
                    <a href="{{ route('admin.tools.tna-template') }}" target="_blank"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-full border border-white/30 text-white px-3 py-2 hover:bg-white/10 transition whitespace-nowrap">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3" />
                        </svg>
                        {{ __('Blank TNA Form') }}
                    </a>
                </x-slot:action>
                <x-slot:description>
                @if (Auth::user()->isAdmin())
                    {{ __('Training Needs Assessments submitted by participants in :region, most recent first.', ['region' => Auth::user()->region]) }}
                @elseif ($selectedRegion)
                    {{ __('Training Needs Assessments submitted by participants in :region, most recent first.', ['region' => $selectedRegion]) }}
                @else
                    {{ __('Every Training Needs Assessment participants have submitted, across all regions, most recent first.') }}
                @endif
                </x-slot:description>

                <form data-live-form data-live-section="tna-submissions" data-live-target="tna-submissions-results"
                    method="GET" action="{{ route('admin.training-needs-assessment') }}#tna-submissions" class="w-full mb-5">
                    <input type="hidden" name="_section" value="tna-submissions">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                        <div class="relative flex-1 min-w-[14rem]">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <input type="text" name="tna_q" value="{{ $submissionSearch }}" placeholder="{{ __('Search participant, organization, category, or date…') }}"
                                class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                        </div>
                        @if (Auth::user()->isSuperAdmin())
                            <div class="relative sm:w-56 shrink-0">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                                </svg>
                                <select name="region"
                                    class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 pr-9 py-2.5 transition">
                                    <option value="">{{ __('All Regions (Philippines)') }}</option>
                                    @foreach ($regions as $regionOption)
                                        <option value="{{ $regionOption }}" @selected($selectedRegion === $regionOption)>{{ $regionOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <button type="submit"
                            class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                    </div>
                    @if ($submissionSearch !== '' || ($selectedRegion && Auth::user()->isSuperAdmin()))
                        <a href="{{ route('admin.training-needs-assessment') }}#tna-submissions"
                            class="inline-block mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </form>

                <div id="tna-submissions-results">
                    @include('admin.partials.tna-submissions-results')
                </div>
            </x-chart-card>

        </div>
    </div>

    @include('admin.partials.live-search-script')
</x-app-layout>
