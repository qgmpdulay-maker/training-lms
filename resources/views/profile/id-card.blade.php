<x-app-layout>
    <div class="py-10">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">

            <div class="no-print flex items-center justify-between mb-6">
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white">
                    <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    {{ __('Back to Profile') }}
                </a>
                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                    </svg>
                    {{ __('Print / Save as PDF') }}
                </button>
            </div>

            <!-- ID Card -->
            <div class="rounded-2xl overflow-hidden border border-gray-200 shadow-sm bg-white">
                <div class="bg-gradient-to-br from-[#152A4E] via-[#1E3A66] to-[#0D1B33] px-6 pt-6 pb-12 text-center">
                    <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase">{{ __('OCD Training LMS') }}</p>
                    <p class="text-white font-bold">{{ __('Participant ID') }}</p>
                </div>

                <div class="px-6 pb-8 -mt-10">
                    <div class="flex justify-center mb-4">
                        @if ($user->picture)
                            <img src="{{ asset('storage/' . $user->picture) }}" alt="{{ $user->name }}"
                                class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md">
                        @else
                            <div class="w-24 h-24 rounded-full bg-[#152A4E]/10 border-4 border-white shadow-md flex items-center justify-center text-[#152A4E] font-bold text-3xl">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <h1 class="text-lg font-bold text-[#152A4E] text-center mb-1">{{ $user->name }}</h1>
                    <p class="text-sm text-gray-500 text-center mb-6">{{ $user->participant_type }}</p>

                    <dl class="divide-y divide-gray-100 text-sm border-t border-b border-gray-100">
                        <div class="py-3 flex justify-between gap-4">
                            <dt class="text-gray-500">{{ __('ID Number') }}</dt>
                            <dd class="font-semibold text-gray-800">{{ sprintf('OCD-PID-%06d', $user->id) }}</dd>
                        </div>
                        <div class="py-3 flex justify-between gap-4">
                            <dt class="text-gray-500">{{ __('Agency/Organization') }}</dt>
                            <dd class="font-semibold text-gray-800 text-right">{{ $user->organization }}</dd>
                        </div>
                        <div class="py-3 flex justify-between gap-4">
                            <dt class="text-gray-500">{{ __('OCD Regional Office') }}</dt>
                            <dd class="font-semibold text-gray-800 text-right">{{ $user->agency }}</dd>
                        </div>
                        <div class="py-3 flex justify-between gap-4">
                            <dt class="text-gray-500">{{ __('Issued') }}</dt>
                            <dd class="font-semibold text-gray-800">{{ now()->format('F j, Y') }}</dd>
                        </div>
                    </dl>

                    <p class="text-xs text-gray-400 text-center mt-6">
                        {{ __('This ID identifies a registered participant of the OCD Training LMS and is valid while the account remains active.') }}
                    </p>
                </div>

                <div class="h-1.5 bg-gradient-to-r from-[#152A4E] to-[#E2762D]"></div>
            </div>

        </div>
    </div>
</x-app-layout>
