<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'OCD Training IMS') }} — {{ __('Register') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --logo-size: 80px;             /* size of the seal logo, top-left */
                --page-title-size: 1.875rem;   /* "Create a Participant Account" */
                --section-label-size: 0.875rem;/* "PERSONAL INFORMATION" etc. */
                --section-gap: 2.5rem;         /* space above each section header */
                --field-label-size: 1rem;      /* "Full Name of Participant" etc. */
                --input-text-size: 1.125rem;   /* text typed into fields */
                --input-height: 3rem;        /* height of input boxes */
                --button-text-size: 1.125rem;  /* "Sign Up" button text */
                --button-height: 3rem;       /* height of the button */
                --footer-text-size: 1rem;      /* "Already registered? Sign in" */
            }

            .app-logo-img {
                width: var(--logo-size);
                height: var(--logo-size);
                object-fit: contain;
                flex-shrink: 0;
            }
            .page-eyebrow { font-size: var(--eyebrow-size); }
            .page-title { font-size: var(--page-title-size); }

            /* Section header with icon badge + gradient divider */
            .section-block { margin-top: var(--section-gap); }
            .section-block:first-child { margin-top: 0; }

            .section-header {
                display: flex;
                align-items: center;
                gap: 0.625rem;
                margin-bottom: 1.5rem;
            }
            .section-icon {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 1.75rem;
                height: 1.75rem;
                border-radius: 9999px;
                background: rgba(21, 42, 78, 0.08);
                color: #152A4E;
                flex-shrink: 0;
            }
            .section-icon svg { width: 0.95rem; height: 0.95rem; }
            .section-label {
                font-size: var(--section-label-size);
                letter-spacing: 0.08em;
                font-weight: 600;
                color: #152A4E;
                text-transform: uppercase;
            }
            .section-divider {
                height: 2px;
                margin-top: 2rem;
                background: linear-gradient(to right, #152A4E 0%, rgba(21,42,78,0.15) 60%, rgba(226,118,45,0.4) 100%);
                border: none;
            }

            .field-label { font-size: var(--field-label-size); }
            .field-input {
                font-size: var(--input-text-size);
                height: var(--input-height);
            }
            .submit-btn {
                font-size: var(--button-text-size);
                height: var(--button-height);
            }
            .footer-text { font-size: var(--footer-text-size); }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col lg:flex-row">

            <!-- Left: Form (scrollable) -->
            <div class="flex-1 lg:h-screen lg:overflow-y-auto px-6 sm:px-12 lg:px-20 py-10 bg-white">

                <!-- Back -->
                <a href="{{ url()->previous() }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-8 footer-text">
                    <svg class="w-5 h-5 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    {{ __('Back') }}
                </a>

                <!-- Logo -->
                <div class="flex items-center gap-3 mb-10">
                    <img src="{{ asset('images/Training-LMS-Logo.png') }}" alt="{{ __('Training LMS Logo') }}" class="app-logo-img">
                    <span class="font-semibold text-[#152A4E] tracking-tight text-lg">
                        {{ __('OCD Training IMS') }}
                    </span>
                </div>

                <div class="max-w-xl">
                    <h1 class="font-bold text-[#152A4E] mb-10 page-title">
                        {{ __('Create a Participant Account') }}
                    </h1>

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <!-- Personal Information -->
                        <div class="section-block">
                            <div class="section-header">
                                <span class="section-icon">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </span>
                                <h2 class="section-label">{{ __('Personal Information') }}</h2>
                            </div>
                            <div class="space-y-5">
                                <div>
                                    <label for="name" class="block font-medium text-gray-700 mb-1.5 field-label">{{ __('Full Name of Participant') }}</label>
                                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] px-4 field-input">
                                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                                </div>

                                <div>
                                    <label for="age" class="block font-medium text-gray-700 mb-1.5 field-label">{{ __('Age') }}</label>
                                    <input id="age" type="text" name="age" value="{{ old('age') }}" required
                                        inputmode="numeric" pattern="\d*" maxlength="3"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] px-4 field-input">
                                    <x-input-error :messages="$errors->get('age')" class="mt-1" />
                                </div>

                                <div>
                                    <label for="sex" class="block font-medium text-gray-700 mb-1.5 field-label">{{ __('Sex') }}</label>
                                    <select id="sex" name="sex" required
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] px-4 field-input">
                                        <option value="" disabled {{ old('sex') ? '' : 'selected' }}>{{ __('Select') }}</option>
                                        <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                                        <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                                        <option value="Other" {{ old('sex') == 'Other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('sex')" class="mt-1" />
                                </div>
                            </div>
                            <hr class="section-divider">
                        </div>

                        <!-- Participant Classification -->
                        <div class="section-block">
                            <div class="section-header">
                                <span class="section-icon">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                    </svg>
                                </span>
                                <h2 class="section-label">{{ __('Participant Classification') }}</h2>
                            </div>
                            <div class="space-y-5">
                                <div>
                                    <label for="participant_type" class="block font-medium text-gray-700 mb-1.5 field-label">{{ __('Participant Type') }}</label>
                                    <select id="participant_type" name="participant_type" required
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] px-4 field-input">
                                        <option value="" disabled {{ old('participant_type') ? '' : 'selected' }}>{{ __('Select') }}</option>
                                        @foreach ([
                                            'Academe', 'Artisanal Fisherfolk', 'Barangay', 'Children',
                                            'City Government', 'Cooperatives', 'CSOs/NGOs',
                                            'Farmers and Landless Rural Workers', 'GOCC', 'Humanitarian',
                                            'Indigenous Peoples', 'Informal Sector', 'Local Chief Executive',
                                            'Municipal Government', 'National Government', 'OCD Personnel',
                                            'Others', 'Persons with Disabilities', 'Private Sector',
                                        ] as $type)
                                            <option value="{{ $type }}" {{ old('participant_type') == $type ? 'selected' : '' }}>
                                                {{ $type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('participant_type')" class="mt-1" />
                                </div>

                                <div>
                                    <label for="organization" class="block font-medium text-gray-700 mb-1.5 field-label">{{ __('Agency/Organization') }}</label>
                                    <input id="organization" type="text" name="organization" value="{{ old('organization') }}" required
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] px-4 field-input">
                                    <x-input-error :messages="$errors->get('organization')" class="mt-1" />
                                </div>

                                <div>
                                    <label for="agency" class="block font-medium text-gray-700 mb-1.5 field-label">{{ __('OCD Regional Office') }}</label>
                                    <select id="agency" name="agency" required
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] px-4 field-input">
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
                                        ] as $agencyOption)
                                            <option value="{{ $agencyOption }}" {{ old('agency') == $agencyOption ? 'selected' : '' }}>
                                                {{ $agencyOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('agency')" class="mt-1" />
                                </div>
                            </div>
                            <hr class="section-divider">
                        </div>

                        <!-- Contact Information -->
                        <div class="section-block">
                            <div class="section-header">
                                <span class="section-icon">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                    </svg>
                                </span>
                                <h2 class="section-label">{{ __('Contact Information') }}</h2>
                            </div>
                            <div class="space-y-5">
                                <div>
                                    <label for="mobile_number" class="block font-medium text-gray-700 mb-1.5 field-label">{{ __('Mobile Number') }}</label>
                                    <input id="mobile_number" type="text" name="mobile_number" value="{{ old('mobile_number') }}" required
                                        inputmode="numeric" pattern="\d*" maxlength="11" autocomplete="tel"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] px-4 field-input">
                                    <x-input-error :messages="$errors->get('mobile_number')" class="mt-1" />
                                </div>

                                <div>
                                    <label for="landline_number" class="block font-medium text-gray-700 mb-1.5 field-label">{{ __('Landline Number') }}</label>
                                    <input id="landline_number" type="text" name="landline_number" value="{{ old('landline_number') }}"
                                        inputmode="numeric" pattern="\d*" maxlength="10" autocomplete="off"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] px-4 field-input">
                                    <x-input-error :messages="$errors->get('landline_number')" class="mt-1" />
                                </div>
                            </div>
                            <hr class="section-divider">
                        </div>

                        <!-- Account Credentials -->
                        <div class="section-block">
                            <div class="section-header">
                                <span class="section-icon">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </span>
                                <h2 class="section-label">{{ __('Account Credentials') }}</h2>
                            </div>
                            <div class="space-y-5">
                                <div>
                                    <label for="email" class="block font-medium text-gray-700 mb-1.5 field-label">{{ __('Email') }}</label>
                                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] px-4 field-input">
                                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                                </div>

                                <div>
                                    <label for="password" class="block font-medium text-gray-700 mb-1.5 field-label">{{ __('Password') }}</label>
                                    <input id="password" type="password" name="password" required autocomplete="new-password"
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] px-4 field-input">
                                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block font-medium text-gray-700 mb-1.5 field-label">{{ __('Confirm Password') }}</label>
                                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] px-4 field-input">
                                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="mt-10">
                            <button type="submit"
                                class="w-full bg-[#152A4E] hover:bg-[#1E3A66] text-white font-semibold rounded-lg transition submit-btn">
                                {{ __('Sign Up') }}
                            </button>

                            <p class="text-gray-600 text-center mt-10 footer-text">
                                {{ __('Already registered?') }}
                                <a href="{{ route('login') }}" class="text-[#152A4E] font-semibold hover:text-[#E2762D]">
                                    {{ __('Sign in') }}
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right: Visual (fixed) -->
            <div class="hidden lg:flex lg:flex-1 lg:h-screen lg:sticky lg:top-0 relative overflow-hidden bg-gradient-to-br from-[#152A4E] via-[#1E3A66] to-[#0D1B33]">
                <img src="{{ asset('images/ocd-seal.png') }}" alt=""
                    class="absolute -right-24 -bottom-24 w-[560px] h-[560px] object-contain opacity-[0.07] pointer-events-none">

                <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-[#152A4E] via-[#152A4E] to-[#E2762D]"></div>

                <div class="relative z-10 flex flex-col justify-end p-16 text-white">
                    <p class="font-semibold tracking-[0.2em] text-[#E2762D] uppercase mb-4 text-sm">
                        {{ __('National Disaster Risk Reduction and Management Council') }}
                    </p>
                    <h2 class="font-bold leading-snug mb-4 max-w-md text-4xl">
                        {{ __('Building prepared, resilient communities across the Philippines.') }}
                    </h2>
                    <p class="text-white/70 max-w-sm text-base">
                        {{ __('Register as a participant to enroll in trainings and track your certifications.') }}
                    </p>
                </div>
            </div>

        </div>
    </body>
</html>