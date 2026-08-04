<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Agency -->
        <div class="mt-4">
            <x-input-label for="agency" :value="__('Agency')" />
            <select id="agency" name="agency" required
                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300">
                <option value="" disabled {{ old('agency') ? '' : 'selected' }}>{{ __('Select an Agency') }}</option>
                @foreach ([
                    'OCD-NCR: National Capital Region',
                    'OCD-CAR: Cordillera Administrative Region',
                    'OCD-Region I: Ilocos Region',
                    'OCD-Region II: Cagayan Valley',
                    'OCD-Region III: Central Luzon',
                    'OCD-Region IV-A: CALABARZON',
                    'OCD-Region IV-B: MIMAROPA',
                    'OCD-Region V: Bicol Region',
                    'OCD-Region VI: Western Visayas',
                    'OCD-Region VII: Central Visayas',
                    'OCD-Region VIII: Eastern Visayas',
                    'OCD-Region IX: Zamboanga Peninsula',
                    'OCD-Region X: Northern Mindanao',
                    'OCD-Region XI: Davao Region',
                    'OCD-Region XII: SOCCSKSARGEN',
                    'OCD-Region XIII: Caraga',
                    'OCD-NIR: Negros Island Region', 
                ] as $agency)
                    <option value="{{ $agency }}" {{ old('agency') == $agency ? 'selected' : '' }}>
                        {{ $agency }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('agency')" class="mt-2" />
        </div>

        <!-- Mobile Number -->
        <div class="mt-4">
            <x-input-label for="mobile_number" :value="__('Mobile Number')" />
            <x-text-input id="mobile_number" class="block mt-1 w-full"
                            type="text"
                            name="mobile_number"
                            :value="old('mobile_number')"
                            required
                            inputmode="numeric"
                            pattern="\d*"
                            maxlength="11"
                            autocomplete="tel" />
            <x-input-error :messages="$errors->get('mobile_number')" class="mt-2" />
        </div>

        <!-- Landline -->
        <div class="mt-4">
            <x-input-label for="landline_number" :value="__('Landline Number')" />
            <x-text-input id="landline_number" class="block mt-1 w-full"
                            type="text"
                            name="landline_number"
                            :value="old('landline_number')"
                            inputmode="numeric"
                            pattern="\d*"
                            maxlength="8"
                            autocomplete="off" />
            <x-input-error :messages="$errors->get('landline_number')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>