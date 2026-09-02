<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Welcome Banner -->
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-[#152A4E] via-[#1E3A66] to-[#0D1B33] px-6 py-8 sm:px-10 sm:py-10">
                <img src="{{ asset('images/ocd-seal.png') }}" alt=""
                    class="absolute -right-10 -bottom-16 w-64 h-64 object-contain opacity-[0.08] pointer-events-none">
                <div class="relative z-10">
                    <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase mb-2">
                        {{ __('OCD Training IMS') }}
                    </p>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">
                        {{ __('Welcome back, :name', ['name' => explode(' ', Auth::user()->name)[0]]) }}
                    </h1>
                    <p class="text-sm text-white/70 max-w-xl mb-6">
                        {{ __('Here is where things stand with your trainings.') }}
                    </p>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('trainings.index') }}"
                            class="inline-flex items-center justify-center bg-white text-[#152A4E] text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-white/90 transition">
                            {{ __('Browse Trainings') }}
                        </a>
                        <a href="{{ route('training-needs-assessment.index') }}"
                            class="inline-flex items-center justify-center bg-white/10 text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-white/20 transition border border-white/20">
                            {{ __('Take Needs Assessment') }}
                        </a>
                        <a href="{{ route('training-requests.index') }}"
                            class="inline-flex items-center justify-center bg-white/10 text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-white/20 transition border border-white/20">
                            {{ __('Upcoming Trainings') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Taken trainings + Recommendation -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Trainings You've Taken -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __("Trainings You've Taken") }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Trainings CDTI has marked as completed for you.') }}</p>

                    @if ($takenTrainings->isEmpty())
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                            {{ __("You haven't completed any trainings yet. Once CDTI marks a training as completed, it will show up here.") }}
                        </div>
                    @else
                        <ul class="space-y-3">
                            @foreach ($takenTrainings as $taken)
                                <li>
                                    <a href="{{ route('training-requests.show', $taken) }}"
                                        class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition p-4">
                                        <div>
                                            <p class="font-semibold text-[#152A4E] dark:text-white text-sm">{{ $taken->training_title }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $taken->preferred_date->format('F j, Y') }}</p>
                                        </div>
                                        <span class="shrink-0 inline-flex items-center text-xs font-semibold rounded-full border px-3 py-1.5 bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700">
                                            {{ __('Completed') }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- Recommended For You -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Recommended For You') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Based on your Training Needs Assessment.') }}</p>

                    @if ($recommendedTraining)
                        <div class="rounded-lg bg-[#152A4E]/5 dark:bg-[#152A4E]/20 border border-[#152A4E]/10 dark:border-[#152A4E]/40 p-5">
                            <span class="inline-block text-[11px] font-semibold tracking-wide uppercase text-[#152A4E] dark:text-white bg-white dark:bg-gray-900 rounded-full px-2.5 py-1 mb-3">
                                {{ $recommendedTraining['category'] }}
                            </span>
                            <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ $recommendedTraining['title'] }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $recommendedTraining['hours'] }} {{ __('training hours') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                                {{ __('Your OCD Regional Office will use this to help schedule your next training.') }}
                            </p>
                        </div>
                    @else
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                            {{ __("You haven't taken the Training Needs Assessment yet.") }}
                            <a href="{{ route('training-needs-assessment.index') }}" class="text-[#152A4E] dark:text-white font-semibold hover:text-[#E2762D]">
                                {{ __('Take it now') }} &rarr;
                            </a>
                        </div>
                    @endif
                </div>

            </div>

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
</x-app-layout>
