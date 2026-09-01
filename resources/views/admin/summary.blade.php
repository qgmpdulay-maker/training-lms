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
                <div class="flex items-center flex-wrap gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm px-5 py-3">
                    <span class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ __('Region') }}</span>
                    <form method="GET" action="{{ route('admin.summary') }}" class="flex items-center gap-2">
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
                        <select name="region" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="">{{ __('All Regions (Philippines)') }}</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption }}" @selected($selectedRegion === $regionOption)>{{ $regionOption }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if ($selectedRegion)
                        <a href="{{ route('admin.summary', array_filter(['q' => $search ?: null, 'status' => ! $statusDefaulted ? $selectedStatus : null, 'participants_q' => $participantSearch ?: null, 'instructors_q' => $instructorSearch ?: null, 'evaluations_q' => $evaluationSearch ?: null])) }}"
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Reset to all regions') }}
                        </a>
                    @endif
                </div>
            @endif

            <div id="training-requests" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                    <div>
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
                        method="GET" action="{{ route('admin.summary') }}#training-requests" class="flex items-center flex-wrap gap-2">
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
                        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('Search training, agency, participant, venue, or date…') }}"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-64">
                        <select name="status"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="all" @selected($selectedStatus === 'all')>{{ __('All statuses') }}</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                        @if ($search !== '' || ! $statusDefaulted)
                            <a href="{{ route('admin.summary', array_filter(['participants_q' => $participantSearch ?: null, 'instructors_q' => $instructorSearch ?: null, 'evaluations_q' => $evaluationSearch ?: null, 'region' => $selectedRegion ?: null])) }}#training-requests"
                                class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Clear') }}
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
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                        <div>
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
                            method="GET" action="{{ route('admin.summary') }}#registered-participants" class="flex items-center flex-wrap gap-2">
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
                            <input type="text" name="participants_q" value="{{ $participantSearch }}" placeholder="{{ __('Search name, type, agency, email, or contact no.…') }}"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-60">
                            <button type="submit"
                                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                {{ __('Search') }}
                            </button>
                            @if ($participantSearch !== '')
                                <a href="{{ route('admin.summary', array_filter(['q' => $search ?: null, 'status' => ! $statusDefaulted ? $selectedStatus : null, 'region' => $selectedRegion ?: null, 'instructors_q' => $instructorSearch ?: null, 'evaluations_q' => $evaluationSearch ?: null])) }}#registered-participants"
                                    class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
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
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                        <div>
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
                            method="GET" action="{{ route('admin.summary') }}#evaluations" class="flex items-center flex-wrap gap-2">
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
                            <input type="text" name="evaluations_q" value="{{ $evaluationSearch }}" placeholder="{{ __('Search participant or training…') }}"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-64">
                            <button type="submit"
                                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                {{ __('Search') }}
                            </button>
                            @if ($evaluationSearch !== '')
                                <a href="{{ route('admin.summary', array_filter(['q' => $search ?: null, 'status' => ! $statusDefaulted ? $selectedStatus : null, 'region' => $selectedRegion ?: null, 'participants_q' => $participantSearch ?: null, 'instructors_q' => $instructorSearch ?: null])) }}#evaluations"
                                    class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
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
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                        <div>
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
                            method="GET" action="{{ route('admin.summary') }}#instructors" class="flex items-center flex-wrap gap-2">
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
                            <input type="text" name="instructors_q" value="{{ $instructorSearch }}" placeholder="{{ __('Search name, training type, agency, or certificate code…') }}"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-64">
                            <button type="submit"
                                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                {{ __('Search') }}
                            </button>
                            @if ($instructorSearch !== '')
                                <a href="{{ route('admin.summary', array_filter(['q' => $search ?: null, 'status' => ! $statusDefaulted ? $selectedStatus : null, 'region' => $selectedRegion ?: null, 'participants_q' => $participantSearch ?: null, 'evaluations_q' => $evaluationSearch ?: null])) }}#instructors"
                                    class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
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
