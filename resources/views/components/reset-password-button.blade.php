@props(['account'])

{{--
    A centered modal (teleported to <body>) rather than an inline dropdown —
    the table rows this button lives in sit inside an `overflow-x-auto`
    wrapper, which clipped an absolutely-positioned dropdown's bottom edge.

    Re-opens itself if this exact account's reset just failed validation —
    e.g. NotSimilarToAccount — so the error shows up right next to the field
    instead of only in the page-level banner. Identified via a hidden
    `_reset_password_for` field rather than the account id alone, since
    $errors/old() are shared across the whole page and every row's modal
    would otherwise think the error was its own.

    The password itself is never re-filled on reopen — Laravel deliberately
    excludes `password` fields from old() input flashing, so it comes back
    empty regardless; the admin re-types it, which also means it never sits
    in the session's flashed-input data.
--}}
@php
    $isReopening = old('_reset_password_for') == $account->id;
@endphp
<div class="inline-block" x-data="{
        open: {{ $isReopening ? 'true' : 'false' }},
        mode: '{{ $isReopening ? 'custom' : 'random' }}',
        password: '',
    }">
    <button type="button" @click="open = true"
        class="inline-flex items-center justify-center border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-xs font-semibold rounded-md px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">
        {{ __('Reset Password') }}
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="open = false">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" x-show="open" x-transition.opacity @click="open = false"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm"
                x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">
                <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="min-w-0">
                        <h3 class="font-bold text-[#152A4E] dark:text-white">{{ __('Reset Password') }}</h3>
                        <p class="text-xs text-gray-400 truncate">{{ $account->name }}</p>
                    </div>
                    <button type="button" @click="open = false"
                        class="shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div class="flex gap-1.5 text-xs mb-4 bg-gray-100 dark:bg-gray-900/40 rounded-md p-1">
                        <button type="button" @click="mode = 'random'"
                            :class="mode === 'random' ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                            class="flex-1 rounded px-2 py-1.5 font-semibold transition">
                            {{ __('Generate') }}
                        </button>
                        <button type="button" @click="mode = 'custom'"
                            :class="mode === 'custom' ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                            class="flex-1 rounded px-2 py-1.5 font-semibold transition">
                            {{ __('Type my own') }}
                        </button>
                    </div>

                    <form method="POST" action="{{ route('admin.users.reset-password', $account) }}"
                        @submit="if (! confirm('Reset the password for ' + @js($account->name) + '? The old password stops working immediately.')) $event.preventDefault()">
                        @csrf
                        <input type="hidden" name="_reset_password_for" value="{{ $account->id }}">

                        <div x-show="mode === 'custom'" x-cloak class="mb-4">
                            <input type="text" name="password" x-model="password" :disabled="mode !== 'custom'" minlength="8"
                                placeholder="{{ __('New password') }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">
                                {{ __("At least 8 characters, with upper & lower case, a number, and a symbol. Can't match the account's current password, name, email, or phone number.") }}
                            </p>
                            @if ($isReopening)
                                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                            @endif
                        </div>

                        <p x-show="mode === 'random'" class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            {{ __('A random password will be generated and shown once you confirm.') }}
                        </p>

                        <button type="submit" :disabled="mode === 'custom' && password.length < 8"
                            class="w-full inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-md px-3 py-2.5 hover:bg-[#1E3A66] transition disabled:opacity-40 disabled:cursor-not-allowed">
                            <span x-text="mode === 'random' ? '{{ __('Generate & Reset') }}' : '{{ __('Set Password') }}'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
