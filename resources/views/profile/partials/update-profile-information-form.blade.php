<section>
    <header class="mb-6">
        <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">
            {{ __('Your Information') }}
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Keep your details up to date so OCD can reach you about your trainings.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <!-- Picture -->
        <div class="flex items-center gap-5">
            @if ($user->picture)
                <img src="{{ asset('storage/' . $user->picture) }}" alt="{{ __('Current picture') }}" class="w-20 h-20 object-cover rounded-full border border-gray-200 dark:border-gray-600">
            @else
                <div class="w-20 h-20 rounded-full bg-[#152A4E]/8 dark:bg-[#152A4E]/30 flex items-center justify-center text-[#152A4E] dark:text-white font-bold text-xl">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <div class="flex-1">
                <label for="picture" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Picture of Participant') }}</label>
                <input id="picture" type="file" name="picture" accept="image/*"
                    class="block w-full text-sm text-gray-600 dark:text-gray-400 file:me-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#152A4E]/8 dark:file:bg-[#152A4E]/30 file:text-[#152A4E] dark:file:text-white hover:file:bg-[#152A4E]/15">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Leave blank to keep your current picture.') }}</p>
                <x-input-error class="mt-1" :messages="$errors->get('picture')" />
            </div>
        </div>

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Full Name') }}</label>
            <input id="name" name="name" type="text" required autofocus autocomplete="name"
                value="{{ old('name', $user->name) }}"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
            <x-input-error class="mt-1" :messages="$errors->get('name')" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Age -->
            <div>
                <label for="age" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Age') }}</label>
                <input id="age" name="age" type="text" required
                    value="{{ old('age', $user->age) }}"
                    inputmode="numeric" pattern="\d*" maxlength="3"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                <x-input-error class="mt-1" :messages="$errors->get('age')" />
            </div>

            <!-- Sex -->
            <div>
                <label for="sex" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Sex') }}</label>
                <select id="sex" name="sex" required
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                    <option value="Male" {{ old('sex', $user->sex) == 'Male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                    <option value="Female" {{ old('sex', $user->sex) == 'Female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                    <option value="Other" {{ old('sex', $user->sex) == 'Other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                </select>
                <x-input-error class="mt-1" :messages="$errors->get('sex')" />
            </div>
        </div>

        <!-- Participant Type -->
        <div>
            <label for="participant_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Participant Type') }}</label>
            <select id="participant_type" name="participant_type" required
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                @foreach ([
                    'Academe', 'Artisanal Fisherfolk', 'Barangay', 'Children',
                    'City Government', 'Cooperatives', 'CSOs/NGOs',
                    'Farmers and Landless Rural Workers', 'GOCC', 'Humanitarian',
                    'Indigenous Peoples', 'Informal Sector', 'Local Chief Executive',
                    'Municipal Government', 'National Government', 'OCD Personnel',
                    'Others', 'Persons with Disabilities', 'Private Sector',
                ] as $type)
                    <option value="{{ $type }}" {{ old('participant_type', $user->participant_type) == $type ? 'selected' : '' }}>
                        {{ $type }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-1" :messages="$errors->get('participant_type')" />
        </div>

        <!-- Agency/Organization (participant's own org) -->
        <div>
            <label for="organization" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Agency/Organization') }}</label>
            <input id="organization" name="organization" type="text" required
                value="{{ old('organization', $user->organization) }}"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
            <x-input-error class="mt-1" :messages="$errors->get('organization')" />
        </div>

        <!-- Agency (OCD Region) -->
        <div>
            <label for="agency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('OCD Regional Office') }}</label>
            <select id="agency" name="agency" required
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
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
                ] as $agencyOption)
                    <option value="{{ $agencyOption }}" {{ old('agency', $user->agency) == $agencyOption ? 'selected' : '' }}>
                        {{ $agencyOption }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-1" :messages="$errors->get('agency')" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Mobile Number -->
            <div>
                <label for="mobile_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Mobile Number') }}</label>
                <input id="mobile_number" name="mobile_number" type="text" required
                    value="{{ old('mobile_number', $user->mobile_number) }}"
                    inputmode="numeric" pattern="\d*" maxlength="11" autocomplete="tel"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                <x-input-error class="mt-1" :messages="$errors->get('mobile_number')" />
            </div>

            <!-- Landline -->
            <div>
                <label for="landline_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Landline Number') }}</label>
                <input id="landline_number" name="landline_number" type="text"
                    value="{{ old('landline_number', $user->landline_number) }}"
                    inputmode="numeric" pattern="\d*" maxlength="10" autocomplete="off"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                <x-input-error class="mt-1" :messages="$errors->get('landline_number')" />
            </div>
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" required autocomplete="username"
                value="{{ old('email', $user->email) }}"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
            <x-input-error class="mt-1" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Your email address is unverified.') }}
                        <button form="send-verification" class="underline font-medium text-[#152A4E] dark:text-white hover:text-[#E2762D]">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit"
                class="bg-[#152A4E] hover:bg-[#1E3A66] text-white text-sm font-semibold rounded-lg px-6 py-3 transition">
                {{ __('Save Changes') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-green-600 font-medium">
                    {{ __('Saved.') }}
                </p>
            @endif
        </div>
    </form>
</section>
