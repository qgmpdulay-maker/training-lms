<section>
    <header class="mb-6">
        <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">
            {{ __('Change Password') }}
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Use a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.password') }}" class="space-y-5">
        @csrf
        @method('patch')

        <!-- Current Password -->
        <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Current Password') }}</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
            <x-input-error class="mt-1" :messages="$errors->get('current_password')" />
        </div>

        <!-- New Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('New Password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
            <x-input-error class="mt-1" :messages="$errors->get('password')" />
        </div>

        <!-- Confirm New Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Confirm New Password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
            <x-input-error class="mt-1" :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit"
                class="bg-[#152A4E] hover:bg-[#1E3A66] text-white text-sm font-semibold rounded-lg px-6 py-3 transition">
                {{ __('Update Password') }}
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-green-600 font-medium">
                    {{ __('Saved.') }}
                </p>
            @endif
        </div>
    </form>
</section>
