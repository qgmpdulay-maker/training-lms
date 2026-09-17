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

            <!-- ATAR -->
            <x-chart-card body-class="p-6 sm:p-8"
                :title="__('ATAR')"
                :subtitle="__('Blank template, then upload each completed ATAR below.')">
                <x-slot:action>
                    <a href="{{ route('admin.tools.atar-template') }}" target="_blank"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-full border border-white/30 text-white px-3 py-2 hover:bg-white/10 transition whitespace-nowrap">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3" />
                        </svg>
                        {{ __('ATAR Template') }}
                    </a>
                </x-slot:action>

                <div class="mb-5">
                    <form data-live-form data-live-section="files" data-live-target="files-section"
                        method="GET" action="{{ route('admin.tools') }}#files-section" class="w-full">
                        <input type="hidden" name="_section" value="files">
                        @if ($region)
                            <input type="hidden" name="region" value="{{ $region }}">
                        @endif
                        <div class="flex items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                            <div class="relative flex-1">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                                <input type="text" name="files_q" value="{{ $filesSearch }}" placeholder="{{ __('Search training, venue, LGU, or participant…') }}"
                                    class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                            </div>
                            <button type="submit"
                                class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                {{ __('Search') }}
                            </button>
                        </div>
                    </form>
                    @if ($filesSearch !== '')
                        <a href="{{ route('admin.tools', $region ? ['region' => $region] : []) }}#files-section"
                            class="inline-block mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Reset search') }}
                        </a>
                    @endif
                </div>

                <div id="files-section">
                    @include('admin.partials.files-table')
                </div>
            </x-chart-card>

            <!-- Evaluation Computation (L1 / L2) -->
            <x-chart-card body-class="p-6 sm:p-8"
                :title="__('Evaluation Computation (L1 / L2)')">
                <x-slot:description>
                {{ __('Combines the admin-entered evaluation with what participants submitted themselves. Pick a training below, then expand a session to see its L1 and L2 results.') }}
                </x-slot:description>

                @if (empty($evaluationsByTraining))
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No evaluations entered yet.') }}
                    </div>
                @else
                    <div x-data="{ activeTraining: @js(array_key_first($evaluationsByTraining)) }">
                        <div class="flex items-center gap-1 overflow-x-auto bg-gray-100 dark:bg-gray-900/40 rounded-xl p-1.5">
                            @foreach ($evaluationsByTraining as $trainingTitle => $sessions)
                                <button type="button" @click="activeTraining = @js($trainingTitle)"
                                    :class="activeTraining === @js($trainingTitle)
                                        ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition whitespace-nowrap">
                                    {{ $trainingTitle }}
                                    <span :class="activeTraining === @js($trainingTitle)
                                            ? 'bg-[#152A4E]/10 text-[#152A4E] dark:bg-white/15 dark:text-white px-1.5 py-0.5 rounded-full text-xs font-semibold'
                                            : 'text-gray-400 dark:text-gray-500 text-xs font-normal'">
                                        {{ $sessions->count() }}
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        @foreach ($evaluationsByTraining as $trainingTitle => $sessions)
                            <div x-show="activeTraining === @js($trainingTitle)" x-cloak class="mt-5">
                                <div class="border border-gray-100 dark:border-gray-700 rounded-lg overflow-hidden divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($sessions as $session)
                                        <div x-data="evaluationSession(@js(route('admin.tools.evaluation', $session['training_request_id'])))">
                                            <button type="button" @click="toggle()"
                                                class="w-full flex items-center justify-between gap-4 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <svg class="w-4 h-4 shrink-0 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                    </svg>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-semibold text-[#152A4E] dark:text-white truncate">{{ $session['venue'] }}</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $session['preferred_date']->format('M j, Y') }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 shrink-0">
                                                    @if ($session['participant_total'] > 0)
                                                        <span class="hidden sm:inline-flex items-center gap-1 text-xs font-semibold text-gray-500 dark:text-gray-400" title="{{ __('Participants who submitted their own evaluation') }}">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                                                            {{ $session['participant_response_count'] }}/{{ $session['participant_total'] }} {{ __('evaluated') }}
                                                        </span>
                                                    @endif
                                                    @if ($session['overall_trainer_rating'])
                                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.958c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.958a1 1 0 00-.364-1.118L2.062 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.286-3.958z" /></svg>
                                                            {{ $session['overall_trainer_rating'] }}
                                                        </span>
                                                    @endif
                                                    <span class="hidden sm:inline text-xs text-gray-400" title="{{ $session['updated_at']?->format('M j, Y g:i A') }}">
                                                        {{ __('Updated') }} {{ $session['updated_at']?->diffForHumans() ?? '—' }}
                                                    </span>
                                                </div>
                                            </button>

                                            <div x-show="open" x-cloak x-ref="details" class="bg-gray-50/60 dark:bg-gray-900/20"
                                                data-error="{{ __("Couldn't load this session's results. Collapse and expand it to try again.") }}">
                                                <p class="px-6 pb-6 pt-1 text-sm text-gray-400">{{ __('Loading…') }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-chart-card>

        </div>
    </div>

    @include('admin.partials.live-search-script')

    <script>
        // Each session's full L1/L2 breakdown is fetched the first time it's
        // expanded rather than rendered up front — rendering all of them made
        // this page tens of megabytes of HTML.
        window.evaluationSession = function (url) {
            return {
                open: false,
                loaded: false,
                toggle() {
                    this.open = !this.open;

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
                            details.innerHTML = html;
                            window.Alpine.initTree(details);
                        })
                        .catch(() => {
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
