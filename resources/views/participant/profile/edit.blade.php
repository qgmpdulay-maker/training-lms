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
                    class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] hover:bg-[#1E3A66] text-white text-sm font-semibold rounded-lg px-6 py-3 transition">
                    {{ __('Generate ID') }}
                </a>
            </div>

            <!-- Profile Information -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                @include('participant.profile.partials.update-profile-information-form')
            </div>

            <!-- Change Password -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                @include('participant.profile.partials.update-password-form')
            </div>

        </div>
    </div>
</x-app-layout>
