<x-public-layout>

    <div x-data="{
        search: '',
        category: 'All',
        selected: null,
        get filtered() {
            return trainings.filter(t =>
                (this.category === 'All' || t.category === this.category) &&
                (t.title.toLowerCase().includes(this.search.toLowerCase()))
            );
        },
        get groupedFiltered() {
            const groups = {};
            this.filtered.forEach(t => {
                (groups[t.category] ??= []).push(t);
            });
            return Object.keys(groups).sort().map(category => ({ category, items: groups[category] }));
        }
    }">
        <!-- Hero -->
        <section class="bg-[#152A4E] min-h-[50vh]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-28 sm:pt-32 pb-24 sm:pb-32 text-center">
                <span class="inline-block text-xs sm:text-sm font-semibold tracking-wide uppercase text-white bg-white/10 rounded-full px-3 py-1.5 mb-5">
                    {{ __('Office of Civil Defense') }}
                </span>
                <h1 class="text-4xl sm:text-6xl font-bold text-white leading-tight max-w-4xl mx-auto">
                    {{ __('Disaster Risk Reduction & Management Trainings') }}
                </h1>
                <p class="text-sm text-white/70 max-w-2xl mx-auto mt-5">
                    {{ __('Browse the trainings currently being offered. Log in or register as a participant to join a training and track your progress.') }}
                </p>
            </div>
        </section>

        <!-- Results -->
        <div class="py-12 sm:py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <div id="trainings" class="mb-8 scroll-mt-24">
                    <div class="mb-5">
                        <h2 class="text-2xl sm:text-3xl font-bold text-[#152A4E]">{{ __('Available Trainings') }}</h2>
                        <p class="text-sm text-gray-500 mt-1.5 whitespace-nowrap">{{ __('Trainings currently being offered. Details will be updated as they become available.') }}</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-2">
                        <!-- Search -->
                        <div class="relative flex-1">
                            <input type="text" x-model="search" placeholder="{{ __('Search trainings...') }}"
                                class="w-full rounded-xl border-0 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>

                        <!-- Category filter -->
                        <div class="relative sm:w-56 shrink-0">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                            </svg>
                            <select x-model="category"
                                class="w-full rounded-xl border-0 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 pr-9 py-2.5 transition">
                                <option value="All">{{ __('All Categories') }}</option>
                                @foreach (collect($trainings)->pluck('category')->unique()->sort() as $categoryOption)
                                    <option value="{{ $categoryOption }}">{{ $categoryOption }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Training cards data -->
                <template x-init="
                    trainings = {{ Js::from($trainings) }}
                "></template>

                <div class="space-y-10 sm:space-y-12">
                    <template x-for="group in groupedFiltered" :key="group.category">
                        <div x-data="{
                            hovering: false,
                            canScrollLeft: false,
                            canScrollRight: false,
                            checkScroll() {
                                const el = this.$refs.scroller;
                                if (!el) return;
                                this.canScrollLeft = el.scrollLeft > 4;
                                this.canScrollRight = el.scrollLeft < el.scrollWidth - el.clientWidth - 4;
                            }
                        }" x-init="$nextTick(() => checkScroll())" @resize.window="checkScroll()">
                            <div class="flex items-center gap-4 mb-5">
                                <h3 class="text-xl sm:text-2xl font-bold text-[#152A4E] whitespace-nowrap" x-text="group.category"></h3>
                                <span class="h-px flex-1 bg-gradient-to-r from-[#152A4E]/25 via-[#E2762D]/25 to-transparent"></span>
                            </div>

                            <div class="relative" @mouseenter="hovering = true" @mouseleave="hovering = false">
                                <!-- Left nav -->
                                <button type="button"
                                    x-show="hovering && canScrollLeft" x-cloak
                                    @click="$refs.scroller.scrollBy({ left: -420, behavior: 'smooth' })"
                                    class="hidden sm:flex absolute -left-8 top-1/2 -translate-y-1/2 z-20 items-center justify-center w-8 h-8 text-gray-400 hover:text-[#152A4E] hover:scale-110 transition"
                                    aria-label="{{ __('Scroll left') }}">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                    </svg>
                                </button>

                                <!-- Right nav -->
                                <button type="button"
                                    x-show="hovering && canScrollRight" x-cloak
                                    @click="$refs.scroller.scrollBy({ left: 420, behavior: 'smooth' })"
                                    class="hidden sm:flex absolute -right-8 top-1/2 -translate-y-1/2 z-20 items-center justify-center w-8 h-8 text-gray-400 hover:text-[#152A4E] hover:scale-110 transition"
                                    aria-label="{{ __('Scroll right') }}">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </button>

                                <div x-ref="scroller" @scroll="checkScroll()"
                                    class="flex gap-6 overflow-x-auto -mx-4 px-4 py-4
                                    [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                                    <template x-for="training in group.items" :key="training.title">
                                        <div class="relative flex flex-col bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 hover:scale-[1.04] hover:z-10 transition overflow-hidden shrink-0 w-[85%] sm:w-[calc((100%-1.5rem)/2)] lg:w-[calc((100%-3rem)/3)]">
                                            <div class="h-1.5 bg-gradient-to-r from-[#152A4E] to-[#E2762D]"></div>
                                            <button type="button" @click="selected = training" class="p-6 flex flex-col flex-1 text-left w-full">
                                                <span class="inline-block w-fit text-[11px] font-semibold tracking-wide uppercase text-[#152A4E] bg-[#152A4E]/8 rounded-full px-2.5 py-1 mb-3"
                                                    x-text="training.category"></span>

                                                <h3 class="text-base font-bold text-[#152A4E] mb-2 leading-snug" x-text="training.title"></h3>

                                                <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100">
                                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600">
                                                        <svg class="w-4 h-4 text-[#E2762D]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span x-text="training.hours"></span> {{ __('training hours') }}
                                                    </span>

                                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-[#152A4E]">
                                                        {{ __('Show details') }}
                                                    </span>
                                                </div>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <p x-show="groupedFiltered.length === 0" class="text-sm text-gray-500 text-center py-12">
                    {{ __('No trainings match your search.') }}
                </p>

                <!-- Details modal -->
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    :class="selected ? '' : 'pointer-events-none'">
                    <div x-show="selected" x-cloak x-transition.opacity @click="selected = null"
                        class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm"></div>

                    <div x-show="selected" x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        @click.outside="selected = null" @keydown.escape.window="selected = null"
                        class="relative w-full max-w-2xl min-h-[36rem] max-h-[85vh] flex flex-col bg-white/80 backdrop-blur-xl backdrop-saturate-150 border border-white/60 rounded-xl shadow-xl overflow-hidden">
                        <template x-if="selected">
                            <div class="flex flex-col h-full">
                                <div class="flex items-start justify-between gap-4 p-8 pb-0">
                                    <span class="inline-block w-fit text-xs font-semibold tracking-wide uppercase text-[#152A4E] bg-[#152A4E]/8 rounded-full px-3 py-1.5"
                                        x-text="selected.category"></span>

                                    <button type="button" @click="selected = null" class="text-gray-400 hover:text-gray-600 shrink-0">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="flex-1 overflow-y-auto px-8 py-6">
                                    <h3 class="text-2xl font-bold text-[#152A4E] mb-4 leading-snug" x-text="selected.title"></h3>

                                    <p class="text-base text-gray-500 leading-relaxed" x-text="selected.description"></p>
                                </div>

                                <div class="flex items-center justify-between gap-4 px-8 py-5 border-t border-gray-100">
                                    <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-600">
                                        <svg class="w-5 h-5 text-[#E2762D]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span x-text="selected.hours"></span> {{ __('training hours') }}
                                    </span>

                                    @auth
                                        <a href="{{ route(Auth::user()->isParticipant() ? 'trainings.index' : 'admin.dashboard') }}" class="inline-flex items-center rounded-lg bg-[#152A4E] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#152A4E]/90 transition shrink-0">
                                            {{ __('Go to Dashboard') }}
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="pb-12 sm:pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Mission / Vision -->
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-[#152A4E] via-[#1E3A66] to-[#0D1B33] p-6 sm:p-10">
                <img src="{{ asset('images/ocd-seal.png') }}" alt=""
                    class="absolute -right-12 -bottom-12 w-56 h-56 object-contain opacity-[0.08] pointer-events-none">

                <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 gap-x-10 gap-y-8">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase mb-2">{{ __('Vision') }}</p>
                        <p class="text-sm text-white/80 leading-relaxed">
                            {{ __('OCD is the premier organization in Civil Defense and Disaster Risk Reduction and Management towards building a safe, secured and resilient Filipino nation by 2030.') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase mb-2">{{ __('Mission') }}</p>
                        <p class="text-sm text-white/80 leading-relaxed">
                            {{ __('To lead in the administration of comprehensive national Civil Defense and Disaster Risk Reduction and Management programs for adaptive, safer, and disaster resilient communities towards sustainable development.') }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase mb-2">{{ __('Quality Policy') }}</p>
                        <p class="text-sm text-white/80 leading-relaxed mb-3">
                            {{ __('The Office of Civil Defense commits to:') }}
                        </p>
                        <ul class="text-sm text-white/80 leading-relaxed space-y-1 mb-3">
                            <li>{{ __('I. Uphold a culture of excellence, professionalism, integrity, and commitment;') }}</li>
                            <li>{{ __('II. Comply with legal and applicable requirements; and') }}</li>
                            <li>{{ __('III. Ensure continual improvement of its quality management system') }}</li>
                        </ul>
                        <p class="text-sm text-white/80 leading-relaxed">
                            {{ __("...to meet the highest level of stakeholder satisfaction in the administration of the country's comprehensive civil defense and disaster risk reduction and management program for an adaptive, safer, and resilient Filipino community.") }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase mb-2">{{ __('Core Values') }}</p>
                        <p class="text-sm font-semibold text-white">
                            <span class="text-[#E2762D]">E</span>xcellence,
                            <span class="text-[#E2762D]">P</span>rofessionalism,
                            <span class="text-[#E2762D]">I</span>ntegrity,
                            <span class="text-[#E2762D]">C</span>ommitment
                        </p>
                    </div>
                </div>

                <p class="relative z-10 text-center text-xs tracking-[0.15em] uppercase text-white/50 mt-8">
                    {{ __('Serving the Nation, Protecting the People') }}
                </p>
            </div>

        </div>
    </div>

</x-public-layout>
