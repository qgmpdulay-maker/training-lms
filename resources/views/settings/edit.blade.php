<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status') === 'settings-updated')
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ __('Your settings have been saved.') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('settings.update') }}"
                x-data="{ theme: '{{ old('theme', $user->theme) }}', locale: '{{ old('locale', $user->locale) }}' }"
                x-effect="document.documentElement.classList.toggle('dark', theme === 'dark')">
                @csrf
                @method('PATCH')

                <!-- Appearance -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 mb-6">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Appearance') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ __('Choose how the training portal looks on this device.') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="relative flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition"
                            :class="theme === 'light' ? 'border-[#152A4E] bg-[#152A4E]/5 dark:bg-[#152A4E]/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">
                            <input type="radio" name="theme" value="light" x-model="theme" class="sr-only">
                            <span class="flex items-center justify-center w-11 h-11 rounded-full bg-amber-50 dark:bg-amber-400/10 text-amber-500 shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                </svg>
                            </span>
                            <span>
                                <span class="block font-semibold text-gray-800 dark:text-gray-100">{{ __('Light') }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Bright background, dark text') }}</span>
                            </span>
                        </label>

                        <label class="relative flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition"
                            :class="theme === 'dark' ? 'border-[#152A4E] bg-[#152A4E]/5 dark:bg-[#152A4E]/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">
                            <input type="radio" name="theme" value="dark" x-model="theme" class="sr-only">
                            <span class="flex items-center justify-center w-11 h-11 rounded-full bg-slate-100 dark:bg-slate-500/20 text-slate-600 dark:text-slate-300 shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                                </svg>
                            </span>
                            <span>
                                <span class="block font-semibold text-gray-800 dark:text-gray-100">{{ __('Dark') }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Dark background, light text') }}</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Language -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 mb-6">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Language') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ __('Choose the language used across the training portal.') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="relative flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition"
                            :class="locale === 'en' ? 'border-[#152A4E] bg-[#152A4E]/5 dark:bg-[#152A4E]/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">
                            <input type="radio" name="locale" value="en" x-model="locale" class="sr-only">
                            <span>
                                <span class="block font-semibold text-gray-800 dark:text-gray-100">{{ __('English') }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">English</span>
                            </span>
                        </label>

                        <label class="relative flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition"
                            :class="locale === 'tl' ? 'border-[#152A4E] bg-[#152A4E]/5 dark:bg-[#152A4E]/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">
                            <input type="radio" name="locale" value="tl" x-model="locale" class="sr-only">
                            <span>
                                <span class="block font-semibold text-gray-800 dark:text-gray-100">{{ __('Filipino') }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Tagalog</span>
                            </span>
                        </label>
                    </div>
                </div>

                <button type="submit"
                    class="bg-[#152A4E] hover:bg-[#1E3A66] text-white text-sm font-semibold rounded-lg px-6 py-3 transition">
                    {{ __('Save Changes') }}
                </button>
            </form>

        </div>
    </div>
</x-app-layout>
