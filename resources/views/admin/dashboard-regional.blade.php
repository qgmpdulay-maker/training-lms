<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Regional Dashboard') }}
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
                        {{ __('OCD Regional Office') }}{{ $user->region ? ' — '.$user->region : '' }}
                    </p>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">
                        {{ __('Welcome back, :name', ['name' => explode(' ', $user->name)[0]]) }}
                    </h1>
                    <p class="text-sm text-white/70 max-w-xl">
                        {{ __('Regional admin access — manage your region\'s trainings, instructors, and reports below.') }}
                    </p>
                </div>
            </div>

            <!-- Module Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ([
                    ['title' => 'Summary', 'description' => 'Participant and training records, including status.', 'icon' => 'summary', 'route' => 'admin.summary'],
                    ['title' => 'Tools', 'description' => 'Graduates list now available; charts, ATAR, evaluations, certificates, and maps still to come.', 'icon' => 'tools', 'route' => 'admin.tools'],
                    ['title' => 'Instructors', 'description' => 'Instructor roster, deployments, and certificate codes.', 'icon' => 'instructors', 'route' => 'admin.instructors.index'],
                    ['title' => 'Training Needs Assessment', 'description' => 'TNA submissions from participants and their recommendations.', 'icon' => 'tna', 'route' => 'admin.training-needs-assessment'],
                    ['title' => 'Calendar', 'description' => 'Scheduled trainings, color-coded by request status.', 'icon' => 'calendar', 'route' => 'admin.calendar'],
                ] as $module)
                    <a href="{{ route($module['route']) }}"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-7 hover:border-[#152A4E]/40 dark:hover:border-white/30 transition">
                        <div class="flex items-start justify-between mb-4">
                            <div class="h-11 w-11 rounded-lg bg-[#152A4E]/5 dark:bg-[#152A4E]/30 flex items-center justify-center text-[#152A4E] dark:text-white">
                                @include('admin.partials.icon', ['name' => $module['icon']])
                            </div>
                        </div>
                        <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __($module['title']) }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __($module['description']) }}</p>
                    </a>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
