{{-- Mobile backdrop --}}
<div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" x-transition.opacity
    class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"></div>

<aside
    x-cloak
    :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        sidebarCollapsed ? 'lg:w-20' : 'lg:w-72',
    ]"
    class="fixed inset-y-0 start-0 z-50 flex w-72 shrink-0 flex-col bg-[#03055A] transition-all duration-200 ease-in-out lg:static">

    <div class="flex h-20 shrink-0 items-center gap-3 px-5 border-b border-white/10">
        <a href="{{ route(Auth::user()->isParticipant() ? 'dashboard' : 'admin.dashboard') }}" class="flex items-center gap-2.5 min-w-0">
            <img src="{{ asset('images/Training-LMS-Logo.png') }}" alt="{{ __('Training IMS Logo') }}" class="h-12 w-12 object-contain shrink-0">
            <span class="text-sm font-semibold text-white truncate" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ __('OCD Training IMS') }}</span>
        </a>

        <button @click="sidebarCollapsed = ! sidebarCollapsed" class="ms-auto hidden lg:flex text-white/60 hover:text-white shrink-0">
            <svg class="w-5 h-5 transition-transform duration-200" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
        </button>

        <button @click="sidebarOpen = false" class="ms-auto text-white/60 hover:text-white lg:hidden">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-5 space-y-1" style="scrollbar-gutter: stable">
        @if (Auth::user()->isParticipant())
            <x-sidebar-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                </x-slot:icon>
                {{ __('Dashboard') }}
            </x-sidebar-nav-link>
            <x-sidebar-nav-link :href="route('trainings.index')" :active="request()->routeIs('trainings.*')">
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                </x-slot:icon>
                {{ __('Trainings') }}
            </x-sidebar-nav-link>
            <x-sidebar-nav-link :href="route('training-needs-assessment.index')" :active="request()->routeIs('training-needs-assessment.*')">
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                </x-slot:icon>
                {{ __('Needs Assessment') }}
            </x-sidebar-nav-link>
            <x-sidebar-nav-link :href="route('training-requests.index')" :active="request()->routeIs('training-requests.*')">
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                </x-slot:icon>
                {{ __('Upcoming Trainings') }}
            </x-sidebar-nav-link>
            <x-sidebar-nav-link :href="route('certificates.index')" :active="request()->routeIs('certificates.*')">
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </x-slot:icon>
                {{ __('Certificates') }}
            </x-sidebar-nav-link>
        @else
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-white/40" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ __('Overview') }}</p>

            <x-sidebar-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                </x-slot:icon>
                {{ __('Dashboard') }}
            </x-sidebar-nav-link>
            <x-sidebar-nav-link :href="route('admin.summary')" :active="request()->routeIs('admin.summary')">
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.125C3.75 12.504 4.254 12 4.875 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C8.25 20.496 7.746 21 7.125 21h-2.25a1.125 1.125 0 01-1.125-1.125v-6.75zM10.5 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM17.25 4.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                </x-slot:icon>
                {{ __('Summary') }}
            </x-sidebar-nav-link>
            <x-sidebar-nav-link :href="route('admin.calendar')" :active="request()->routeIs('admin.calendar')">
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                </x-slot:icon>
                {{ __('Calendar') }}
            </x-sidebar-nav-link>

            <p class="px-3 pb-2 pt-4 text-[11px] font-semibold uppercase tracking-wider text-white/40" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ __('Training') }}</p>

            @if (Auth::user()->isAdmin())
                <x-sidebar-nav-link :href="route('admin.training-requests.create')" :active="request()->routeIs('admin.training-requests.*')">
                    <x-slot:icon>
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </x-slot:icon>
                    {{ __('Request Training') }}
                </x-sidebar-nav-link>
            @endif
            <x-sidebar-nav-link :href="route('admin.tools')" :active="request()->routeIs('admin.tools')">
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75" /></svg>
                </x-slot:icon>
                {{ __('Tools') }}
            </x-sidebar-nav-link>
            <x-sidebar-nav-link :href="route('admin.instructors.index')" :active="request()->routeIs('admin.instructors.*')">
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" /></svg>
                </x-slot:icon>
                {{ __('Instructors') }}
            </x-sidebar-nav-link>
            <x-sidebar-nav-link :href="route('admin.training-needs-assessment')" :active="request()->routeIs('admin.training-needs-assessment')">
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                </x-slot:icon>
                {{ __('Needs Assessment') }}
            </x-sidebar-nav-link>
            <x-sidebar-nav-link :href="route('admin.tna-submissions.index')" :active="request()->routeIs('admin.tna-submissions.*')">
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                </x-slot:icon>
                {{ __('TNA Submissions (Org.)') }}
            </x-sidebar-nav-link>

            @if (Auth::user()->isSuperAdmin())
                <p class="px-3 pb-2 pt-4 text-[11px] font-semibold uppercase tracking-wider text-white/40" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ __('Administration') }}</p>

                <x-sidebar-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    <x-slot:icon>
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                    </x-slot:icon>
                    {{ __('Manage Admins') }}
                </x-sidebar-nav-link>
                <x-sidebar-nav-link :href="route('admin.monitoring.regional')" :active="request()->routeIs('admin.monitoring.regional')">
                    <x-slot:icon>
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    </x-slot:icon>
                    {{ __('Regional Monitoring') }}
                </x-sidebar-nav-link>
                <x-sidebar-nav-link :href="route('admin.monitoring.map')" :active="request()->routeIs('admin.monitoring.map')">
                    <x-slot:icon>
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" /></svg>
                    </x-slot:icon>
                    {{ __('Graduates Map') }}
                </x-sidebar-nav-link>
            @endif
        @endif
    </nav>

    <div class="shrink-0 border-t border-white/10 px-5 py-4" :class="sidebarCollapsed ? 'lg:hidden' : ''">
        <p class="text-xs font-medium text-white truncate">{{ Auth::user()->name }}</p>
        @unless (Auth::user()->isParticipant())
            <p class="text-xs text-white/50 truncate">{{ Auth::user()->isSuperAdmin() ? __('Super Admin') : __('Regional Admin').' — '.Auth::user()->region }}</p>
        @endunless
    </div>
</aside>
