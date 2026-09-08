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

            @if (Auth::user()->isSuperAdmin())
                <div class="flex items-center flex-wrap gap-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-2 pl-4">
                    <span class="text-sm font-semibold text-[#152A4E] dark:text-white shrink-0">{{ __('Region') }}</span>
                    <form method="GET" action="{{ route('admin.summary') }}" class="flex-1 min-w-[12rem]">
                        @if ($search !== '')
                            <input type="hidden" name="q" value="{{ $search }}">
                        @endif
                        @if (! $statusDefaulted)
                            <input type="hidden" name="status" value="{{ $selectedStatus }}">
                        @endif
                        @if ($participantSearch !== '')
                            <input type="hidden" name="participants_q" value="{{ $participantSearch }}">
                        @endif
                        @if ($instructorSearch !== '')
                            <input type="hidden" name="instructors_q" value="{{ $instructorSearch }}">
                        @endif
                        @if ($evaluationSearch !== '')
                            <input type="hidden" name="evaluations_q" value="{{ $evaluationSearch }}">
                        @endif
                        <div class="relative">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                            </svg>
                            <select name="region" onchange="this.form.submit()"
                                class="w-full rounded-xl border-0 bg-gray-50 dark:bg-gray-900 dark:text-gray-100 focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 pr-9 py-2.5 transition">
                                <option value="">{{ __('All Regions (Philippines)') }}</option>
                                @foreach ($regions as $regionOption)
                                    <option value="{{ $regionOption }}" @selected($selectedRegion === $regionOption)>{{ $regionOption }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                    @if ($selectedRegion)
                        <a href="{{ route('admin.summary', array_filter(['q' => $search ?: null, 'status' => ! $statusDefaulted ? $selectedStatus : null, 'participants_q' => $participantSearch ?: null, 'instructors_q' => $instructorSearch ?: null, 'evaluations_q' => $evaluationSearch ?: null])) }}"
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap shrink-0">
                            {{ __('Reset to all regions') }}
                        </a>
                    @endif
                </div>
            @endif

            <div id="training-requests" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="mb-5">
                    <div class="mb-4">
                        <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Training Requests') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @php
                                $regionPhrase = Auth::user()->isAdmin()
                                    ? __('for :region', ['region' => Auth::user()->region])
                                    : ($selectedRegion ? __('for :region', ['region' => $selectedRegion]) : __('across all regions'));
                            @endphp
                            @if ($statusDefaulted)
                                {{ __('Newly received training requests :region, most recent first, so nothing gets missed. Use the status filter to review requests that are being reviewed, approved, completed, or not approved. Click Manage to update status, certificate details, or move date and venue.', ['region' => $regionPhrase]) }}
                            @elseif ($selectedStatus === 'all')
                                {{ __('Every training request on record :region, most recent first. Click Manage to update its status, certificate details, or move its date and venue.', ['region' => $regionPhrase]) }}
                            @else
                                {{ __('Training requests marked ":status" :region, most recent first. Click Manage to update status, certificate details, or move date and venue.', ['status' => $statusLabels[$selectedStatus] ?? $selectedStatus, 'region' => $regionPhrase]) }}
                            @endif
                        </p>
                    </div>
                    <form id="training-requests-form" data-live-form data-live-section="training-requests" data-live-target="training-requests-results"
                        method="GET" action="{{ route('admin.summary') }}#training-requests" class="w-full">
                        <input type="hidden" name="_section" value="training-requests">
                        @if ($participantSearch !== '')
                            <input type="hidden" name="participants_q" value="{{ $participantSearch }}">
                        @endif
                        @if ($instructorSearch !== '')
                            <input type="hidden" name="instructors_q" value="{{ $instructorSearch }}">
                        @endif
                        @if ($evaluationSearch !== '')
                            <input type="hidden" name="evaluations_q" value="{{ $evaluationSearch }}">
                        @endif
                        @if ($selectedRegion)
                            <input type="hidden" name="region" value="{{ $selectedRegion }}">
                        @endif
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                            <div class="relative flex-1 min-w-[14rem]">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                                <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('Search training, agency, participant, venue, or date…') }}"
                                    class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                            </div>
                            <div class="relative sm:w-48 shrink-0">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                                </svg>
                                <select name="status"
                                    class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 pr-9 py-2.5 transition">
                                    <option value="all" @selected($selectedStatus === 'all')>{{ __('All statuses') }}</option>
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit"
                                class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                {{ __('Search') }}
                            </button>
                        </div>
                        @if ($search !== '' || ! $statusDefaulted)
                            <a href="{{ route('admin.summary', array_filter(['participants_q' => $participantSearch ?: null, 'instructors_q' => $instructorSearch ?: null, 'evaluations_q' => $evaluationSearch ?: null, 'region' => $selectedRegion ?: null])) }}#training-requests"
                                class="inline-block mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Clear filters') }}
                            </a>
                        @endif
                    </form>
                </div>

                <div id="training-requests-results">
                    @include('admin.partials.summary-training-requests')
                </div>
            </div>

            @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                <div id="registered-participants" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <div class="mb-5">
                        <div class="mb-4">
                            <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Registered Participants') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @if (Auth::user()->isAdmin())
                                    {{ __('Participants registered under :region.', ['region' => Auth::user()->region]) }}
                                @elseif ($selectedRegion)
                                    {{ __('Participants registered under :region.', ['region' => $selectedRegion]) }}
                                @else
                                    {{ __('Participants registered across all regions.') }}
                                @endif
                            </p>
                        </div>
                        <form id="participants-form" data-live-form data-live-section="participants" data-live-target="participants-results"
                            method="GET" action="{{ route('admin.summary') }}#registered-participants" class="w-full">
                            <input type="hidden" name="_section" value="participants">
                            @if ($search !== '')
                                <input type="hidden" name="q" value="{{ $search }}">
                            @endif
                            @if (! $statusDefaulted)
                                <input type="hidden" name="status" value="{{ $selectedStatus }}">
                            @endif
                            @if ($selectedRegion)
                                <input type="hidden" name="region" value="{{ $selectedRegion }}">
                            @endif
                            @if ($instructorSearch !== '')
                                <input type="hidden" name="instructors_q" value="{{ $instructorSearch }}">
                            @endif
                            @if ($evaluationSearch !== '')
                                <input type="hidden" name="evaluations_q" value="{{ $evaluationSearch }}">
                            @endif
                            <div class="flex items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                                <div class="relative flex-1">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                    </svg>
                                    <input type="text" name="participants_q" value="{{ $participantSearch }}" placeholder="{{ __('Search name, type, agency, email, or contact no.…') }}"
                                        class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                                </div>
                                <button type="submit"
                                    class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                    {{ __('Search') }}
                                </button>
                            </div>
                            @if ($participantSearch !== '')
                                <a href="{{ route('admin.summary', array_filter(['q' => $search ?: null, 'status' => ! $statusDefaulted ? $selectedStatus : null, 'region' => $selectedRegion ?: null, 'instructors_q' => $instructorSearch ?: null, 'evaluations_q' => $evaluationSearch ?: null])) }}#registered-participants"
                                    class="inline-block mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </form>
                    </div>

                    <div id="participants-results">
                        @include('admin.partials.summary-participants')
                    </div>
                </div>
            @endif

            @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                <div id="evaluations" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <div class="mb-5">
                        <div class="mb-4">
                            <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Evaluations') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @if (Auth::user()->isAdmin())
                                    {{ __('Evaluations participants have submitted for trainings in :region.', ['region' => Auth::user()->region]) }}
                                @elseif ($selectedRegion)
                                    {{ __('Evaluations participants have submitted for trainings in :region.', ['region' => $selectedRegion]) }}
                                @else
                                    {{ __('Evaluations participants have submitted, across all regions. Open a training\'s Manage page for the full breakdown.') }}
                                @endif
                            </p>
                        </div>
                        <form id="evaluations-form" data-live-form data-live-section="evaluations" data-live-target="evaluations-results"
                            method="GET" action="{{ route('admin.summary') }}#evaluations" class="w-full">
                            <input type="hidden" name="_section" value="evaluations">
                            @if ($search !== '')
                                <input type="hidden" name="q" value="{{ $search }}">
                            @endif
                            @if (! $statusDefaulted)
                                <input type="hidden" name="status" value="{{ $selectedStatus }}">
                            @endif
                            @if ($selectedRegion)
                                <input type="hidden" name="region" value="{{ $selectedRegion }}">
                            @endif
                            @if ($participantSearch !== '')
                                <input type="hidden" name="participants_q" value="{{ $participantSearch }}">
                            @endif
                            @if ($instructorSearch !== '')
                                <input type="hidden" name="instructors_q" value="{{ $instructorSearch }}">
                            @endif
                            <div class="flex items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                                <div class="relative flex-1">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                    </svg>
                                    <input type="text" name="evaluations_q" value="{{ $evaluationSearch }}" placeholder="{{ __('Search participant or training…') }}"
                                        class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                                </div>
                                <button type="submit"
                                    class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                    {{ __('Search') }}
                                </button>
                            </div>
                            @if ($evaluationSearch !== '')
                                <a href="{{ route('admin.summary', array_filter(['q' => $search ?: null, 'status' => ! $statusDefaulted ? $selectedStatus : null, 'region' => $selectedRegion ?: null, 'participants_q' => $participantSearch ?: null, 'instructors_q' => $instructorSearch ?: null])) }}#evaluations"
                                    class="inline-block mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </form>
                    </div>

                    <div id="evaluations-results">
                        @include('admin.partials.summary-evaluations')
                    </div>
                </div>
            @endif

            @if (Auth::user()->isSuperAdmin())
                <div id="instructors" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <div class="mb-5">
                        <div class="mb-4">
                            <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Instructors') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @if ($selectedRegion)
                                    {{ __('Instructors on file for :region. Click a name for their full profile, deployment history, and complaints on record.', ['region' => $selectedRegion]) }}
                                @else
                                    {{ __('Instructors on file across all regions. Click a name for their full profile, deployment history, and complaints on record.') }}
                                @endif
                            </p>
                        </div>
                        <form id="instructors-form" data-live-form data-live-section="instructors" data-live-target="instructors-results"
                            method="GET" action="{{ route('admin.summary') }}#instructors" class="w-full">
                            <input type="hidden" name="_section" value="instructors">
                            @if ($search !== '')
                                <input type="hidden" name="q" value="{{ $search }}">
                            @endif
                            @if (! $statusDefaulted)
                                <input type="hidden" name="status" value="{{ $selectedStatus }}">
                            @endif
                            @if ($selectedRegion)
                                <input type="hidden" name="region" value="{{ $selectedRegion }}">
                            @endif
                            @if ($participantSearch !== '')
                                <input type="hidden" name="participants_q" value="{{ $participantSearch }}">
                            @endif
                            @if ($evaluationSearch !== '')
                                <input type="hidden" name="evaluations_q" value="{{ $evaluationSearch }}">
                            @endif
                            <div class="flex items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                                <div class="relative flex-1">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                    </svg>
                                    <input type="text" name="instructors_q" value="{{ $instructorSearch }}" placeholder="{{ __('Search name, training type, agency, or certificate code…') }}"
                                        class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                                </div>
                                <button type="submit"
                                    class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                    {{ __('Search') }}
                                </button>
                            </div>
                            @if ($instructorSearch !== '')
                                <a href="{{ route('admin.summary', array_filter(['q' => $search ?: null, 'status' => ! $statusDefaulted ? $selectedStatus : null, 'region' => $selectedRegion ?: null, 'participants_q' => $participantSearch ?: null, 'evaluations_q' => $evaluationSearch ?: null])) }}#instructors"
                                    class="inline-block mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </form>
                    </div>

                    <div id="instructors-results">
                        @include('admin.partials.summary-instructors')
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Shared filter values across all four search forms below, kept in
            // sync so switching sections never loses another section's filter.
            const liveState = {
                q: @js($search),
                status: @js($statusDefaulted ? null : $selectedStatus),
                participants_q: @js($participantSearch),
                instructors_q: @js($instructorSearch),
                evaluations_q: @js($evaluationSearch),
                region: @js($selectedRegion),
            };

            function syncHiddenFields() {
                document.querySelectorAll('form[data-live-form]').forEach(function (form) {
                    Object.keys(liveState).forEach(function (key) {
                        const field = form.elements.namedItem(key);
                        if (!field || field.tagName === 'SELECT') {
                            return;
                        }
                        field.value = liveState[key] ?? '';
                    });
                });
            }

            function wireLiveSearch(form) {
                const target = document.getElementById(form.dataset.liveTarget);
                const section = form.dataset.liveSection;
                if (!target || !section) {
                    return;
                }

                let debounceTimer;

                function applyResponse(html, url) {
                    target.innerHTML = html;
                    window.history.replaceState({}, '', url);

                    // Content swapped in via innerHTML bypasses Alpine's mutation
                    // observer in some cases (e.g. the certificate dropdown on
                    // the participants table), so any x-data in the new markup
                    // needs to be initialized explicitly.
                    if (window.Alpine) {
                        window.Alpine.initTree(target);
                    }
                }

                function request(url) {
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (response) { return response.text(); })
                        .then(function (html) { applyResponse(html, url); });
                }

                function submitLive() {
                    const params = new URLSearchParams(new FormData(form));
                    Object.keys(liveState).forEach(function (key) {
                        if (params.has(key)) {
                            liveState[key] = params.get(key);
                        }
                    });
                    syncHiddenFields();
                    request(form.action.split('#')[0] + '?' + params.toString() + '#' + section);
                }

                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    clearTimeout(debounceTimer);
                    submitLive();
                });

                form.querySelectorAll('input[type="text"]').forEach(function (input) {
                    input.addEventListener('input', function () {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(submitLive, 350);
                    });
                });

                form.querySelectorAll('select').forEach(function (select) {
                    select.addEventListener('change', function () {
                        clearTimeout(debounceTimer);
                        submitLive();
                    });
                });

                target.addEventListener('click', function (event) {
                    const link = event.target.closest('.' + section + '-pagination a[href]');
                    if (!link) {
                        return;
                    }
                    event.preventDefault();
                    // Pagination links are rendered from whatever query string loaded
                    // the page, which may predate this section ever going through
                    // submitLive() — so it won't carry _section yet. Force it on
                    // here rather than trusting the link to already have it.
                    const url = new URL(link.href, window.location.origin);
                    url.searchParams.set('_section', section);
                    request(url.toString());
                });
            }

            document.querySelectorAll('form[data-live-form]').forEach(wireLiveSearch);
        });
    </script>
</x-app-layout>
