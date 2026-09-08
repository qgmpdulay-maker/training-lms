<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('My Profile') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Training ID -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Your Training ID') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Generate a printable ID card with your participant details.') }}</p>
                </div>
                <a href="{{ route('profile.id-card') }}" target="_blank"
                    class="shrink-0 inline-flex items-center justify-center bg-[#152A4E]/70 hover:bg-[#152A4E]/85 backdrop-blur-xl backdrop-saturate-150 border border-white/10 text-white text-sm font-semibold rounded-lg px-6 py-3 shadow-lg transition">
                    {{ __('Generate ID') }}
                </a>
            </div>

            <!-- Profile Information -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                @include('participant.profile.partials.update-profile-information-form')
            </div>

            <div class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3">
                <svg class="w-5 h-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                <span>{{ __('Forgot your password or need it changed? Contact your Super Admin to have it reset.') }}</span>
            </div>

        </div>
    </div>
</x-app-layout>
