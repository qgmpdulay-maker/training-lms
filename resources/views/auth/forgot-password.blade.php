<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'OCD Training LMS') }} — {{ __('Forgot Password') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col lg:flex-row">

            <!-- Left: Form -->
            <div class="flex-1 flex flex-col px-6 sm:px-12 lg:px-20 py-10 bg-white">

                <!-- Logo -->
                <div class="flex items-center gap-3 mb-16">
                    <img src="{{ asset('images/ocd-seal.png') }}" alt="{{ __('OCD Seal') }}" class="h-20 w-20 object-contain">
                    <span class="text-base font-semibold text-[#152A4E] tracking-tight">
                        {{ __('OCD Training LMS') }}
                    </span>
                </div>

                <div class="flex-1 flex items-center">
                    <div class="w-full max-w-sm mx-auto lg:mx-0">

                        <p class="text-sm text-gray-500 mb-1">{{ __('Forgot your password?') }}</p>
                        <h1 class="text-2xl font-bold text-[#152A4E] mb-4">
                            {{ __('Reset Your Password') }}
                        </h1>
                        <p class="text-sm text-gray-500 mb-8">
                            {{ __('No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                        </p>

                        <x-auth-session-status class="mb-4" :status="session('status')" />

                        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                            @csrf

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-xs font-medium text-gray-500 mb-1">
                                    {{ __('Email') }}
                                </label>
                                <div class="relative">
                                    <input id="email" type="email" name="email"
                                        value="{{ old('email') }}"
                                        required autofocus autocomplete="username"
                                        placeholder="{{ __('you@example.com') }}"
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-lg h-12 pr-10">
                                    <svg class="w-4 h-4 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-1" />
                            </div>

                            <!-- Submit -->
                            <button type="submit"
                                class="w-full bg-[#152A4E] hover:bg-[#1E3A66] text-white text-sm font-semibold rounded-lg py-3 transition">
                                {{ __('Email Password Reset Link') }}
                            </button>
                        </form>

                    </div>
                </div>

                <p class="text-sm text-gray-500 text-center lg:text-left">
                    {{ __('Remembered your password?') }}
                    <a href="{{ route('login') }}" class="text-[#152A4E] font-semibold hover:text-[#E2762D]">
                        {{ __('Sign In') }}
                    </a>
                </p>
            </div>

            <!-- Right: Visual -->
            <div class="hidden lg:flex lg:flex-1 relative overflow-hidden bg-gradient-to-br from-[#152A4E] via-[#1E3A66] to-[#0D1B33]">
                <img src="{{ asset('images/ocd-seal.png') }}" alt=""
                    class="absolute -right-24 -bottom-24 w-[560px] h-[560px] object-contain opacity-[0.07] pointer-events-none">

                <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-[#152A4E] via-[#152A4E] to-[#E2762D]"></div>

                <div class="relative z-10 flex flex-col justify-end p-16 text-white">
                    <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase mb-4">
                        {{ __('National Disaster Risk Reduction and Management Council') }}
                    </p>
                    <h2 class="text-3xl font-bold leading-snug mb-4 max-w-md">
                        {{ __('Building prepared, resilient communities across the Philippines.') }}
                    </h2>
                    <p class="text-sm text-white/70 max-w-sm">
                        {{ __('Recover access to your account to continue managing your training courses and records.') }}
                    </p>
                </div>
            </div>

        </div>
    </body>
</html>
