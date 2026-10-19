<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tools') }}
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

            @if ($regionLocked)
                <div class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ __('Showing :region only. Requests need to be tagged with a region on the Summary tab to show up here.', ['region' => $region]) }}</span>
                </div>
            @else
                <div class="flex items-center flex-wrap gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm px-5 py-3">
                    <span class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ __('Region') }}</span>
                    <form method="GET" action="{{ route('admin.tools') }}" class="flex items-center gap-2">
                        <select name="region" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="">{{ __('All Regions (Philippines)') }}</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption }}" @selected($region === $regionOption)>{{ $regionOption }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if ($region)
                        <a href="{{ route('admin.tools') }}"
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Reset to all regions') }}
                        </a>
                    @endif
                </div>
            @endif

            {{-- Importing a region's training data: they send the Training Database
                 CSV to the Super Admin, who loads it here to populate the system
                 with what was run in that region. It used to sit as a tab under
                 ATAR, which read as if the CSV were an ATAR import — it isn't;
                 ATAR is the narrative report, this is regional training data. --}}
            @if (Auth::user()->isSuperAdmin())
                <x-chart-card body-class="p-6 sm:p-8"
                    :title="__('Import Training Data (CSV)')"
                    :subtitle="__('For training data a region sends in — import it to populate the system.')">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400 flex-1">
                            {{ __('Columns have to be in the order the parser expects, so start from the template. You review a preview of every row before anything is saved.') }}
                            @if ($importedRecordCount > 0)
                                <span class="block text-xs text-gray-400 mt-1">
                                    {{ trans_choice(':count record imported so far|:count records imported so far', $importedRecordCount, ['count' => $importedRecordCount]) }}{{ $region ? ' '.__('for :region', ['region' => $region]) : '' }}.
                                    <a href="{{ route('admin.atar-records.index', $region ? ['region' => $region] : []) }}"
                                        class="font-semibold underline hover:text-[#152A4E] dark:hover:text-white">{{ __('View imported records') }}</a>
                                </span>
                            @endif
                        </p>

                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('admin.atar-records.import.template') }}"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-lg border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 px-3 py-2.5 hover:border-[#152A4E] dark:hover:border-white transition whitespace-nowrap">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3" />
                                </svg>
                                {{ __('CSV Template') }}
                            </a>
                            <a href="{{ route('admin.atar-records.import') }}"
                                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                {{ __('Import CSV') }}
                            </a>
                        </div>
                    </div>
                </x-chart-card>
            @endif

            <!-- Evaluation Computation (L1 / L2) -->
            <x-chart-card id="evaluation-computation" class="scroll-mt-24" body-class="p-0"
                :title="__('Evaluation Computation (L1 / L2)')">
                <x-slot:description>
                {{ __('Combines the admin-entered evaluation with what participants submitted themselves. Pick a training, then expand a session for its L1 and L2 results.') }}
                </x-slot:description>

                @if (empty($evaluationsByTraining) && $trainingSearch === '')
                    <div class="p-6 sm:p-8">
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No evaluations entered yet.') }}
                        </div>
                    </div>
                @else
                    {{-- Two panes: every training in a scrolling list on the left, the
                         selected training's sessions — ten at a time — on the right. It
                         used to be one horizontal strip of title pills, which overflowed
                         off-screen once there were more than a handful of titles. --}}
                    @php
                        $trainingTitles = array_keys($evaluationsByTraining);
                        $sessionTotal = collect($evaluationsByTraining)->sum(fn ($sessions) => $sessions->count());
                    @endphp
                    <div x-data="evaluationBrowser(@js($trainingTitles))" class="flex flex-col lg:flex-row lg:items-stretch">
                        <div class="lg:w-80 lg:shrink-0 border-b lg:border-b-0 lg:border-e border-gray-100 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/20">
                            <div class="p-4">
                                <form method="GET" action="{{ route('admin.tools') }}#evaluation-computation">
                                    @if ($region)
                                        <input type="hidden" name="region" value="{{ $region }}">
                                    @endif
                                    <div class="relative">
                                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                        </svg>
                                        <input type="search" name="trainings_q" value="{{ $trainingSearch }}"
                                            placeholder="{{ __('Search trainings…') }}"
                                            class="w-full rounded-lg border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm ps-9 py-2 transition">
                                    </div>
                                </form>

                                <p class="text-xs text-gray-400 mt-2">
                                    @if ($trainingTitles === [])
                                        {{ __('No trainings match that search.') }}
                                    @else
                                        {{ trans_choice(':count training|:count trainings', count($trainingTitles), ['count' => count($trainingTitles)]) }}
                                        ·
                                        {{ trans_choice(':count session|:count sessions', $sessionTotal, ['count' => $sessionTotal]) }}
                                    @endif
                                    @if ($trainingSearch !== '')
                                        <a href="{{ route('admin.tools', $region ? ['region' => $region] : []) }}#evaluation-computation"
                                            class="font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition">
                                            {{ __('Clear') }}
                                        </a>
                                    @endif
                                </p>
                            </div>

                            <div class="max-h-[22rem] lg:max-h-[34rem] overflow-y-auto px-2 pb-2 space-y-0.5">
                                @foreach ($evaluationsByTraining as $trainingTitle => $sessions)
                                    <button type="button" @click="activeTraining = @js($trainingTitle)"
                                        :class="activeTraining === @js($trainingTitle)
                                            ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm'
                                            : 'text-gray-500 dark:text-gray-400 hover:bg-white/70 dark:hover:bg-gray-700/50'"
                                        class="w-full flex items-start justify-between gap-2 rounded-lg px-3 py-2.5 text-start text-sm font-semibold transition">
                                        <span class="min-w-0">{{ $trainingTitle }}</span>
                                        <span :class="activeTraining === @js($trainingTitle)
                                                ? 'bg-[#152A4E]/10 text-[#152A4E] dark:bg-white/15 dark:text-white'
                                                : 'bg-gray-200/70 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                                            class="shrink-0 rounded-full px-1.5 py-0.5 text-xs font-semibold tabular-nums">
                                            {{ $sessions->count() }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>

                        </div>

                        <div class="min-w-0 flex-1">
                            @foreach ($evaluationsByTraining as $trainingTitle => $sessions)
                                {{-- Sessions are paged ten at a time, in the browser: every
                                     training's collapsed rows are already in the page (only
                                     the expanded breakdowns are fetched), so paging here
                                     needs no reload and switching training stays instant.
                                     Each training keeps its own page number. --}}
                                @php $sessionPages = (int) ceil($sessions->count() / 10); @endphp
                                <div x-show="activeTraining === @js($trainingTitle)" x-cloak
                                    x-data="{ page: 1, pages: {{ max($sessionPages, 1) }}, perPage: 10 }">
                                    <div class="px-5 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                                        <h4 class="font-bold text-[#152A4E] dark:text-white">{{ $trainingTitle }}</h4>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ trans_choice(':count session on record|:count sessions on record', $sessions->count(), ['count' => $sessions->count()]) }}
                                            @if ($sessionPages > 1)
                                                <span x-text="'· showing ' + ((page - 1) * perPage + 1) + '–' + Math.min(page * perPage, {{ $sessions->count() }})"></span>
                                            @endif
                                        </p>
                                    </div>

                                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @foreach ($sessions as $session)
                                            {{-- One collapsible row per training session. evaluationSession() (script at the
                                                 bottom of this page) remembers whether the row is open and, the first time it
                                                 is opened, downloads that session's full results from the admin.tools.evaluation
                                                 route. Only this summary line is rendered up front, which keeps the page small. --}}
                                            @php
                                                $responseRatio = $session['participant_total'] > 0
                                                    ? min(100, round($session['participant_response_count'] / $session['participant_total'] * 100))
                                                    : null;
                                            @endphp
                                            <div x-show="Math.floor({{ $loop->index }} / perPage) + 1 === page" x-cloak
                                                x-data="evaluationSession(@js(route('admin.tools.evaluation', $session['training_request_id'])))">
                                                <div class="flex items-center gap-3 px-5 sm:px-6 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition"
                                                    :class="open ? 'bg-gray-50 dark:bg-gray-700/40' : ''">
                                                    <button type="button" @click="toggle()" class="flex items-center gap-3 min-w-0 flex-1 text-left">
                                                        <svg class="w-4 h-4 shrink-0 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                        </svg>
                                                        <div class="min-w-0">
                                                            <p class="text-sm font-semibold text-[#152A4E] dark:text-white truncate">{{ $session['venue'] }}</p>
                                                            {{-- Date only; "updated" has its own column on the right so neither
                                                                 gets truncated mid-word when the window narrows. --}}
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                                {{ $session['preferred_date']->format('M j, Y') }}
                                                            </p>
                                                        </div>
                                                    </button>

                                                    <div class="shrink-0 flex items-center gap-3 sm:gap-4">
                                                        {{-- Every column below keeps its width whether or not the session has
                                                             the figure, so the bars, chips and links line up down the list
                                                             instead of sliding left and right row by row. --}}
                                                        {{-- Only on a really wide window: between lg and 2xl the two panes are
                                                             side by side and this column would squeeze the venue to nothing. --}}
                                                        <p class="hidden 2xl:block w-32 shrink-0 text-xs text-gray-400 text-end truncate"
                                                            title="{{ $session['updated_at']?->format('M j, Y g:i A') }}">
                                                            {{ __('updated') }} {{ $session['updated_at']?->diffForHumans() ?? '—' }}
                                                        </p>

                                                        {{-- How much of the roster has answered, as a bar rather than a bare
                                                             fraction: a half-answered session is the thing worth spotting. --}}
                                                        <div class="hidden sm:block w-24 shrink-0" title="{{ __('Participants who submitted their own evaluation') }}">
                                                            @if ($responseRatio !== null)
                                                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1 tabular-nums">
                                                                    <span>{{ $session['participant_response_count'] }}/{{ $session['participant_total'] }}</span>
                                                                    <span class="text-gray-400">{{ $responseRatio }}%</span>
                                                                </div>
                                                                <div class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                                                    <div class="h-full rounded-full bg-[#152A4E] dark:bg-white/70" style="width: {{ $responseRatio }}%"></div>
                                                                </div>
                                                            @else
                                                                <p class="text-xs text-gray-400">{{ __('No roster') }}</p>
                                                            @endif
                                                        </div>

                                                        {{-- Fixed width and two decimals so a 5 and a 4.33 wear the same
                                                             oval — the chips used to be as wide as their own text. --}}
                                                        <div class="shrink-0 w-[4.75rem] flex justify-center">
                                                            @if ($session['overall_trainer_rating'])
                                                                <span class="inline-flex items-center justify-center gap-1 w-full text-xs font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-full px-2 py-1 tabular-nums"
                                                                    title="{{ __('Overall trainer rating') }}">
                                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.958c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.958a1 1 0 00-.364-1.118L2.062 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.286-3.958z" /></svg>
                                                                    {{ number_format($session['overall_trainer_rating'], 2) }}
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center justify-center w-full text-xs font-semibold text-gray-400 border border-dashed border-gray-200 dark:border-gray-700 rounded-full px-2 py-1"
                                                                    title="{{ __('No trainer rating yet') }}">—</span>
                                                            @endif
                                                        </div>

                                                        {{-- Same figures on their own page, for printing or sharing a link. --}}
                                                        <a href="{{ route('admin.summary.evaluations', $session['training_request_id']) }}"
                                                            class="hidden sm:inline-flex items-center gap-1 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                                            {{ __('Full summary') }}
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </div>

                                                {{-- Placeholder the downloaded results are inserted into (shows a skeleton
                                                     until they arrive). data-error is the message shown if the download fails. --}}
                                                <div x-show="open" x-cloak x-ref="details" class="bg-gray-50/60 dark:bg-gray-900/20"
                                                    data-error="{{ __("Couldn't load this session's results. Collapse and expand it to try again.") }}">
                                                    <div class="px-6 pb-6 pt-1 space-y-2 animate-pulse">
                                                        <div class="h-8 w-2/3 rounded bg-gray-200 dark:bg-gray-700"></div>
                                                        <div class="h-24 rounded bg-gray-200/70 dark:bg-gray-700/70"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if ($sessionPages > 1)
                                        <div class="flex items-center justify-between gap-2 border-t border-gray-100 dark:border-gray-700 px-5 sm:px-6 py-3">
                                            <button type="button" @click="page = Math.max(1, page - 1)" :disabled="page === 1"
                                                :class="page === 1 ? 'text-gray-300 dark:text-gray-600 cursor-default' : 'text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white'"
                                                class="text-xs font-semibold transition">
                                                {{ __('Previous') }}
                                            </button>

                                            <span class="text-xs text-gray-400 tabular-nums"
                                                x-text="@js(__('Page')) + ' ' + page + ' ' + @js(__('of')) + ' ' + pages"></span>

                                            <button type="button" @click="page = Math.min(pages, page + 1)" :disabled="page === pages"
                                                :class="page === pages ? 'text-gray-300 dark:text-gray-600 cursor-default' : 'text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white'"
                                                class="text-xs font-semibold transition">
                                                {{ __('Next') }}
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            {{-- Only reachable when a search matched no training at all. --}}
                            <p x-show="activeTraining === null" x-cloak class="px-5 sm:px-6 py-12 text-center text-sm text-gray-400">
                                {{ __('No trainings match that search.') }}
                            </p>
                        </div>
                    </div>
                @endif
            </x-chart-card>

        </div>
    </div>

    <script>
        // Left pane of the Evaluation Computation card: which training in the
        // (scrolling) list is selected. Searching is the server's job via
        // trainings_q; each training's own session paging lives in its x-data.
        window.evaluationBrowser = function (titles) {
            return {
                activeTraining: titles[0] ?? null,
            };
        };

        // Each session's full L1/L2 breakdown is fetched the first time it's
        // expanded rather than rendered up front — rendering all of them made
        // this page tens of megabytes of HTML.
        //
        // Used as x-data="evaluationSession(url)" on every session row;
        // url is that session's admin.tools.evaluation route.
        window.evaluationSession = function (url) {
            return {
                open: false,   // whether the row is currently expanded
                loaded: false, // whether its results have already been downloaded
                toggle() {
                    this.open = !this.open;

                    // Nothing to download when collapsing, or when this
                    // session's results were already loaded earlier.
                    if (!this.open || this.loaded) {
                        return;
                    }

                    this.loaded = true;
                    const details = this.$refs.details;

                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error(response.status);
                            }
                            return response.text();
                        })
                        .then((html) => {
                            // Put the results into the row, then let Alpine
                            // activate the tabs inside the newly added HTML.
                            details.innerHTML = html;
                            window.Alpine.initTree(details);
                        })
                        .catch(() => {
                            // Show the error message and allow a retry the
                            // next time the row is expanded.
                            this.loaded = false;
                            const message = document.createElement('p');
                            message.className = 'px-6 pb-6 pt-1 text-sm text-red-500';
                            message.textContent = details.dataset.error;
                            details.replaceChildren(message);
                        });
                },
            };
        };
    </script>
</x-app-layout>
