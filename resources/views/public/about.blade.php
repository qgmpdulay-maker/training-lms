<x-public-layout>

    <!-- Hero -->
    <section class="bg-[#152A4E]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-28 sm:pt-32 pb-16 sm:pb-20 text-center">
            <span class="inline-block text-xs sm:text-sm font-semibold tracking-wide uppercase text-white bg-white/10 rounded-full px-3 py-1.5 mb-5">
                {{ __('Office of Civil Defense') }}
            </span>
            <h1 class="text-4xl sm:text-6xl font-bold text-white leading-tight">
                {{ __('About Us') }}
            </h1>
            <p class="text-sm text-white/70 max-w-2xl mx-auto mt-5">
                {{ __('Learn more about the Training Information Management System, our latest memos, and the mission that drives our work.') }}
            </p>
        </div>
    </section>

    <div class="py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10 sm:space-y-14">

            <!-- Description -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 sm:p-10">
                <div class="flex items-center gap-3 mb-4">
                    <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-[#152A4E]/8 text-[#152A4E] shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    </span>
                    <h2 class="text-xl sm:text-2xl font-bold text-[#152A4E]">{{ __('What is the Training IMS?') }}</h2>
                </div>
                <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
                    {{ __('The OCD Training Information Management System (Training IMS) is the Office of Civil Defense\'s digital platform for managing disaster risk reduction and management (DRRM) trainings nationwide. It streamlines the entire training lifecycle — from scheduling and participant registration to attendance, evaluation, and certification — giving LGUs, national government agencies, academic institutions, and volunteer organizations a single, reliable system to coordinate capacity-building programs and track training records across the country.') }}
                </p>
            </div>

            <!-- Memos -->
            <div>
                <div class="flex items-center gap-4 mb-5">
                    <h2 class="text-2xl sm:text-3xl font-bold text-[#152A4E] whitespace-nowrap">{{ __('Memos') }}</h2>
                    <span class="h-px flex-1 bg-gradient-to-r from-[#152A4E]/25 via-[#E2762D]/25 to-transparent"></span>
                </div>

                <div class="bg-white rounded-xl border border-dashed border-gray-200 p-10 sm:p-14 text-center">
                    <span class="mx-auto flex items-center justify-center w-12 h-12 rounded-full bg-gray-50 text-gray-300 mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </span>
                    <p class="text-sm font-medium text-gray-500">{{ __('No memos have been posted yet.') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('Official memos will appear here once released.') }}</p>
                </div>
            </div>

            <!-- Mission / Vision -->
            <div>
                <div class="flex items-center gap-4 mb-5">
                    <h2 class="text-2xl sm:text-3xl font-bold text-[#152A4E] whitespace-nowrap">{{ __('Mission & Vision') }}</h2>
                    <span class="h-px flex-1 bg-gradient-to-r from-[#152A4E]/25 via-[#E2762D]/25 to-transparent"></span>
                </div>

                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-[#152A4E] via-[#1E3A66] to-[#0D1B33] p-6 sm:p-10 shadow-lg">
                    <img src="{{ asset('images/ocd-seal.png') }}" alt=""
                        class="absolute -right-12 -bottom-12 w-56 h-56 object-contain opacity-[0.08] pointer-events-none">

                    <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 gap-x-10 gap-y-8">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-[#E2762D]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase">{{ __('Vision') }}</p>
                            </div>
                            <p class="text-sm text-white/80 leading-relaxed">
                                {{ __('OCD is the premier organization in Civil Defense and Disaster Risk Reduction and Management towards building a safe, secured and resilient Filipino nation by 2030.') }}
                            </p>
                        </div>

                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-[#E2762D]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" />
                                </svg>
                                <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase">{{ __('Mission') }}</p>
                            </div>
                            <p class="text-sm text-white/80 leading-relaxed">
                                {{ __('To lead in the administration of comprehensive national Civil Defense and Disaster Risk Reduction and Management programs for adaptive, safer, and disaster resilient communities towards sustainable development.') }}
                            </p>
                        </div>

                        <div class="sm:col-span-2 border-t border-white/10 pt-8">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-[#E2762D]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                                <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase">{{ __('Quality Policy') }}</p>
                            </div>
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

                        <div class="sm:col-span-2 border-t border-white/10 pt-8">
                            <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase mb-3">{{ __('Core Values') }}</p>
                            <div class="flex flex-wrap gap-3">
                                <span class="inline-flex items-center rounded-lg bg-white/10 px-3 py-1.5 text-sm font-semibold text-white">
                                    <span class="text-[#E2762D]">E</span>xcellence
                                </span>
                                <span class="inline-flex items-center rounded-lg bg-white/10 px-3 py-1.5 text-sm font-semibold text-white">
                                    <span class="text-[#E2762D]">P</span>rofessionalism
                                </span>
                                <span class="inline-flex items-center rounded-lg bg-white/10 px-3 py-1.5 text-sm font-semibold text-white">
                                    <span class="text-[#E2762D]">I</span>ntegrity
                                </span>
                                <span class="inline-flex items-center rounded-lg bg-white/10 px-3 py-1.5 text-sm font-semibold text-white">
                                    <span class="text-[#E2762D]">C</span>ommitment
                                </span>
                            </div>
                        </div>
                    </div>

                    <p class="relative z-10 text-center text-xs tracking-[0.15em] uppercase text-white/50 mt-8">
                        {{ __('Serving the Nation, Protecting the People') }}
                    </p>
                </div>
            </div>

        </div>
    </div>

</x-public-layout>
