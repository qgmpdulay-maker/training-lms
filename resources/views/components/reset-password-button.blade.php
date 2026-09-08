@props(['account'])

<div x-data="{ open: false, mode: 'random', password: '' }" @click.outside="open = false" class="relative inline-block text-left">
    <button type="button" @click="open = !open"
        class="inline-flex items-center justify-center border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-xs font-semibold rounded-md px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">
        {{ __('Reset Password') }}
    </button>

    <div x-show="open" x-cloak
        class="absolute z-20 right-0 mt-2 w-72 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg p-4 text-left">
        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-3">{{ __('Reset password for :name', ['name' => $account->name]) }}</p>

        <div class="flex gap-1.5 text-xs mb-3 bg-gray-100 dark:bg-gray-900/40 rounded-md p-1">
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

            <div x-show="mode === 'custom'" class="mb-3">
                <input type="text" name="password" x-model="password" :disabled="mode !== 'custom'" minlength="8"
                    placeholder="{{ __('New password (min 8 characters)') }}"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>

            <button type="submit" :disabled="mode === 'custom' && password.length < 8"
                class="w-full inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-3 py-2 hover:bg-[#1E3A66] transition disabled:opacity-40 disabled:cursor-not-allowed">
                <span x-text="mode === 'random' ? '{{ __('Generate & Reset') }}' : '{{ __('Set Password') }}'"></span>
            </button>
        </form>
    </div>
</div>
