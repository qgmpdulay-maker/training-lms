<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Admin Accounts') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <!-- Current Admins -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between flex-wrap gap-3 mb-1">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">{{ __('Current Admins') }}</h2>
                    <form data-live-form data-live-section="admins" data-live-target="admins-results"
                        method="GET" action="{{ route('admin.users.index') }}" class="flex items-center flex-wrap gap-2">
                        <input type="hidden" name="_section" value="admins">
                        <input type="hidden" name="participants_q" value="{{ $participantSearch }}">
                        <input type="text" name="admins_q" value="{{ $adminSearch }}" placeholder="{{ __('Search name, email, or organization…') }}"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-72">
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                        @if ($adminSearch !== '')
                            <a href="{{ route('admin.users.index', array_filter(['participants_q' => $participantSearch ?: null])) }}"
                                class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </form>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Regional admins and super admins with access to the admin dashboard.') }}</p>

                <div id="admins-results">
                    @include('admin.partials.manage-admins-results')
                </div>
            </div>

            <!-- Participants -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between flex-wrap gap-3 mb-1">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">{{ __('Participants') }}</h2>
                    <form data-live-form data-live-section="participants" data-live-target="participants-results"
                        method="GET" action="{{ route('admin.users.index') }}" class="flex items-center flex-wrap gap-2">
                        <input type="hidden" name="_section" value="participants">
                        <input type="hidden" name="admins_q" value="{{ $adminSearch }}">
                        <input type="text" name="participants_q" value="{{ $participantSearch }}" placeholder="{{ __('Search name, email, or organization…') }}"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-72">
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                        @if ($participantSearch !== '')
                            <a href="{{ route('admin.users.index', array_filter(['admins_q' => $adminSearch ?: null])) }}"
                                class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </form>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Elevate a participant to Regional Admin by assigning them a region.') }}</p>

                <div id="participants-results">
                    @include('admin.partials.manage-participants-results')
                </div>
            </div>

        </div>
    </div>

    @include('admin.partials.live-search-script')
</x-app-layout>
