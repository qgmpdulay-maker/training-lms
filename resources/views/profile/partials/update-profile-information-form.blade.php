<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <!-- Agency -->
        <div>
            <x-input-label for="agency" :value="__('Agency')" />
            <select id="agency" name="agency" required
                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300">
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
                    <option value="{{ $agency }}" {{ old('agency', $user->agency) == $agency ? 'selected' : '' }}>
                        {{ $agency }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('agency')" />
        </div>

        <!-- Mobile Number -->
        <div>
            <x-input-label for="mobile_number" :value="__('Mobile Number')" />
            <x-text-input id="mobile_number" name="mobile_number" type="text" class="mt-1 block w-full"
                :value="old('mobile_number', $user->mobile_number)"
                required inputmode="numeric" pattern="\d*" maxlength="11" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('mobile_number')" />
        </div>

        <!-- Landline -->
        <div>
            <x-input-label for="landline_number" :value="__('Landline Number')" />
            <x-text-input id="landline_number" name="landline_number" type="text" class="mt-1 block w-full"
                :value="old('landline_number', $user->landline_number)"
                inputmode="numeric" pattern="\d*" maxlength="8" autocomplete="off" />
            <x-input-error class="mt-2" :messages="$errors->get('landline_number')" />
        </div>

        <!-- Email -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Saved saved.') }}
                </p>
            @endif
        </div>
    </form>
</section>