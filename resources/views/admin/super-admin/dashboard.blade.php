<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Super Admin Dashboard') }}
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
                        {{ __('CDTI — Central Office') }}
                    </p>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">
                        {{ __('Welcome back, :name', ['name' => explode(' ', $user->name)[0]]) }}
                    </h1>
                    <p class="text-sm text-white/70 max-w-xl">
                        {{ __('Super Admin access — full visibility across all OCD regional offices, plus admin account management.') }}
                    </p>
                </div>
            </div>

            <!-- Stat tiles -->
            <div class="grid grid-cols-3 gap-3">
                @foreach ([
                    ['label' => 'Instructors on File', 'value' => $stats['instructors']],
                    ['label' => 'TNA Submissions', 'value' => $stats['tna_submissions']],
                    ['label' => 'Upcoming Trainings', 'value' => $stats['upcoming_trainings']],
                ] as $card)
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 shadow-sm px-3 py-2.5">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __($card['label']) }}</div>
                        <div class="text-xl font-bold text-[#152A4E] dark:text-white mt-0.5">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <!-- Module Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ([
                    ['title' => 'Summary', 'description' => 'Participant and training records across all regions and Central Office.', 'icon' => 'summary', 'route' => 'admin.summary'],
                    ['title' => 'Tools', 'description' => 'Graduates list now available; charts, ATAR, evaluations, certificates, and maps still to come.', 'icon' => 'tools', 'route' => 'admin.tools'],
                    ['title' => 'Instructors', 'description' => 'Instructor list with ratings, deployments, and certificate codes.', 'icon' => 'instructors', 'route' => 'admin.instructors.index'],
                    ['title' => 'Training Needs Assessment Results', 'description' => 'TNA results aggregated across all regions.', 'icon' => 'tna', 'route' => 'admin.training-needs-assessment'],
                    ['title' => 'Regional Training Calendar', 'description' => 'Color-coded by request status, all regions and Central.', 'icon' => 'calendar', 'route' => 'admin.calendar'],
                    ['title' => 'Regional Monitoring', 'description' => 'Overall training data, graduate demographics, and 3-year data generation across all regions.', 'icon' => 'monitoring', 'route' => 'admin.monitoring.regional'],
                    ['title' => 'TNA Submissions (Org.)', 'description' => 'Read-only copies and charts of the formal TNA reports regional admins have submitted.', 'icon' => 'tna', 'route' => 'admin.tna-submissions.index'],
                    ['title' => 'Graduates Map', 'description' => 'Map of training graduates and teams organized, plotted by LGU / NGA and region.', 'icon' => 'map', 'route' => 'admin.monitoring.map'],
                ] as $module)
                    @if ($module['route'])
                        <a href="{{ route($module['route']) }}"
                            class="group bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-7 hover:border-[#E2762D]/50 hover:shadow-md transition">
                            <div class="flex items-start justify-between mb-4">
                                <div class="h-11 w-11 rounded-lg bg-[#E2762D]/10 dark:bg-[#E2762D]/20 flex items-center justify-center text-[#E2762D]">
                                    @include('admin.partials.icon', ['name' => $module['icon']])
                                </div>
                                <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 group-hover:text-[#E2762D] transition-all" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </div>
                            <h3 class="font-bold text-[#152A4E] dark:text-white mb-1 group-hover:text-[#E2762D] transition-colors">{{ __($module['title']) }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __($module['description']) }}</p>
                        </a>
                    @else
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-7">
                            <div class="flex items-start justify-between mb-4">
                                <div class="h-11 w-11 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500">
                                    @include('admin.partials.icon', ['name' => $module['icon']])
                                </div>
                                <span class="inline-flex items-center text-[11px] font-semibold rounded-full border px-2.5 py-1 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700">
                                    {{ __('Coming Soon') }}
                                </span>
                            </div>
                            <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __($module['title']) }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __($module['description']) }}</p>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Manage Admins -->
            <a href="{{ route('admin.users.index') }}"
                class="rounded-xl border border-dashed border-[#152A4E]/30 dark:border-white/20 p-6 sm:p-7 flex items-center justify-between gap-4 flex-wrap hover:border-[#152A4E]/60 dark:hover:border-white/40 hover:bg-[#152A4E]/[0.02] dark:hover:bg-white/[0.02] transition">
                <div>
                    <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Manage Admins') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Manage admin accounts — elevate a participant to Regional Admin.') }}</p>
                </div>
                <span class="inline-flex items-center text-[11px] font-semibold rounded-full border px-2.5 py-1 bg-[#152A4E]/5 dark:bg-white/10 text-[#152A4E] dark:text-white border-[#152A4E]/20 dark:border-white/20">
                    {{ __('Open') }} &rarr;
                </span>
            </a>

        </div>
    </div>
</x-app-layout>
