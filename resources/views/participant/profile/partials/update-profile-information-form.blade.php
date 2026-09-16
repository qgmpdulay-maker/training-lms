<section x-data="{ editing: {{ $errors->any() ? 'true' : 'false' }} }">
    <header class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">
                {{ __('Your Information') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Keep your details up to date so OCD can reach you about your trainings.') }}
            </p>
        </div>

        <button type="button" x-show="!editing" x-cloak @click="editing = true"
            class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-[#152A4E] px-3.5 py-2 text-sm font-semibold text-white hover:bg-[#1E3A66] transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
            </svg>
            {{ __('Edit') }}
        </button>
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
            <div class="flex-1" x-show="editing" x-cloak>
                <label for="picture" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Picture of Participant') }}</label>
                <input id="picture" type="file" name="picture" accept="image/*" :disabled="!editing"
                    class="block w-full text-sm text-gray-600 dark:text-gray-400 file:me-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#152A4E]/8 dark:file:bg-[#152A4E]/30 file:text-[#152A4E] dark:file:text-white hover:file:bg-[#152A4E]/15">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Leave blank to keep your current picture.') }}</p>
                <x-input-error class="mt-1" :messages="$errors->get('picture')" />
            </div>
        </div>

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Full Name') }}</label>
            <input id="name" name="name" type="text" required autocomplete="name" :disabled="!editing"
                value="{{ old('name', $user->name) }}"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
            <x-input-error class="mt-1" :messages="$errors->get('name')" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Age -->
            <div>
                <label for="age" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Age') }}</label>
                <input id="age" name="age" type="text" required :disabled="!editing"
                    value="{{ old('age', $user->age) }}"
                    inputmode="numeric" pattern="\d*" maxlength="3"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
                <x-input-error class="mt-1" :messages="$errors->get('age')" />
            </div>

            <!-- Sex -->
            <div>
                <label for="sex" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Sex') }}</label>
                <select id="sex" name="sex" required :disabled="!editing"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
                    <option value="Male" {{ old('sex', $user->sex) == 'Male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                    <option value="Female" {{ old('sex', $user->sex) == 'Female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                    <option value="Other" {{ old('sex', $user->sex) == 'Other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                </select>
                <x-input-error class="mt-1" :messages="$errors->get('sex')" />
            </div>
        </div>

        <!-- Participant Type — locked; only OCD staff change this, not the participant -->
        <div>
            <label for="participant_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Participant Type') }}</label>
            <select id="participant_type" disabled
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
                @foreach ([
                    'Academe', 'Artisanal Fisherfolk', 'Barangay', 'Children',
                    'City Government', 'Cooperatives', 'CSOs/NGOs',
                    'Farmers and Landless Rural Workers', 'GOCC', 'Humanitarian',
                    'Indigenous Peoples', 'Informal Sector', 'LGU', 'Local Chief Executive',
                    'Municipal Government', 'N&RDRRMC', 'National Government', 'OCD Personnel',
                    'Others', 'Persons with Disabilities', 'Private Sector', 'Volunteers',
                ] as $type)
                    <option value="{{ $type }}" {{ strcasecmp((string) $user->participant_type, $type) === 0 ? 'selected' : '' }}>
                        {{ $type }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Agency/Organization — locked; only OCD staff change this, not the participant -->
        <div>
            <label for="organization" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Agency/Organization') }}</label>
            <input id="organization" type="text" disabled
                value="{{ $user->organization }}"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
        </div>

        <!-- Region / OCD Regional Office — locked; regional & super admins are tied to an OCD
             regional office, while a participant's own location is just their region -->
        <div>
            @if (Auth::user()->isParticipant())
                <label for="region" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Region') }}</label>
                <input id="region" type="text" disabled
                    value="{{ $user->region }}"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
            @else
                @php
                    // Derived from the admin's `region` column (the authoritative
                    // value used everywhere else — dashboards, filtering, the topbar
                    // chip) rather than the stored `agency` string, which for older
                    // accounts doesn't reliably match one of the option labels below
                    // (e.g. "OCD Regional Office III" vs "OCD-Region III: Central
                    // Luzon") and would silently display the wrong region.
                    $ocdOfficeLabel = collect(config('regions.agency_map'))->search($user->region) ?: $user->region;
                @endphp
                <label for="agency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('OCD Regional Office') }}</label>
                <input id="agency" type="text" disabled
                    value="{{ $ocdOfficeLabel }}"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
            @endif
        </div>

        <!-- Email — locked; contact Super Admin to change -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Email') }}</label>
            <input id="email" type="email" disabled
                value="{{ $user->email }}"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-800 dark:disabled:text-gray-400">

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

        <div class="flex items-center gap-4 pt-2" x-show="editing" x-cloak>
            <button type="submit"
                class="bg-[#152A4E] hover:bg-[#1E3A66] text-white text-sm font-semibold rounded-lg px-6 py-3 shadow-sm transition">
                {{ __('Save Changes') }}
            </button>

            <button type="button" @click="editing = false; $el.closest('form').reset()"
                class="text-sm font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                {{ __('Cancel') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-green-600 font-medium">
                    {{ __('Saved.') }}
                </p>
            @endif
        </div>
    </form>
</section>
