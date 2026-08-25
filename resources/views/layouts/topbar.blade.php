<div class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 sm:px-6">
    <button @click="sidebarOpen = true" class="text-gray-500 hover:text-[#152A4E] dark:text-gray-300 dark:hover:text-white lg:hidden">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>

    <div class="flex-1"></div>

    <x-dropdown align="right" width="48">
        <x-slot name="trigger">
            <button class="inline-flex items-center gap-2 rounded-full pe-3 ps-1.5 py-1.5 text-sm font-medium text-[#152A4E] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#152A4E]/10 dark:bg-white/10 text-xs font-semibold text-[#152A4E] dark:text-white">
                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                </span>
                <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </x-slot>

        <x-slot name="content">
            @unless (Auth::user()->isParticipant())
                <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">
                    {{ Auth::user()->isSuperAdmin() ? __('Super Admin') : __('Regional Admin').' — '.Auth::user()->region }}
                </div>
            @endunless

            <x-dropdown-link :href="route('profile.edit')">
                {{ __('Profile') }}
            </x-dropdown-link>

            <x-dropdown-link :href="route('settings.edit')">
                {{ __('Settings') }}
            </x-dropdown-link>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <x-dropdown-link :href="route('logout')"
                        onclick="event.preventDefault();
                                    this.closest('form').submit();">
                    {{ __('Log Out') }}
                </x-dropdown-link>
            </form>
        </x-slot>
    </x-dropdown>
</div>
