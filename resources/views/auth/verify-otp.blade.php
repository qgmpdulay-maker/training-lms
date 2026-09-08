<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'OCD Training IMS') }} — {{ __('Verify Email') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col lg:flex-row">

            <!-- Left: Form -->
            <div class="flex-1 lg:flex-none lg:w-[45%] flex flex-col px-6 sm:px-12 lg:px-20 py-10 bg-white">

                <!-- Logo -->
                <div class="flex items-center gap-3 mb-10">
                    <img src="{{ asset('images/Training-LMS-Logo.png') }}" alt="{{ __('Training IMS Logo') }}" class="h-20 w-20 object-contain">
                    <span class="text-base font-semibold text-[#152A4E] tracking-tight">
                        {{ __('OCD Training IMS') }}
                    </span>
                </div>

                <div class="flex-1 flex items-center">
                    <div class="w-full max-w-sm mx-auto">

                        <p class="text-sm text-gray-500 mb-1">{{ __('One more step') }}</p>
                        <h1 class="text-2xl font-bold text-[#152A4E] mb-2">
                            {{ __('Verify your email') }}
                        </h1>
                        <p class="text-sm text-gray-500 mb-8">
                            {{ __('We sent a 6-digit code to :email. Enter it below — it expires in 10 minutes.', ['email' => $email]) }}
                        </p>

                        <x-auth-session-status class="mb-4" :status="session('status')" />

                        <form method="POST" action="{{ route('otp.verify') }}" class="space-y-5">
                            @csrf

                            <div>
                                <label for="code" class="block text-xs font-medium text-gray-500 mb-1">
                                    {{ __('Verification Code') }}
                                </label>
                                <input id="code" type="text" name="code"
                                    inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code"
                                    required autofocus
                                    placeholder="000000"
                                    class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-2xl tracking-[0.3em] text-center h-14">
                                <x-input-error :messages="$errors->get('code')" class="mt-1" />
                            </div>

                            <button type="submit"
                                class="w-full bg-[#152A4E]/70 hover:bg-[#152A4E]/85 backdrop-blur-xl backdrop-saturate-150 border border-white/10 text-white text-sm font-semibold rounded-lg py-3 shadow-lg transition">
                                {{ __('Verify') }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('otp.resend') }}" class="mt-4 text-center">
                            @csrf
                            <button type="submit" class="text-sm text-[#152A4E] font-semibold hover:text-[#E2762D]">
                                {{ __("Didn't get a code? Resend it") }}
                            </button>
                        </form>

                    </div>
                </div>
            </div>

            <!-- Right: Visual -->
            <div class="hidden lg:flex lg:flex-1 relative overflow-hidden bg-gradient-to-br from-[#152A4E] via-[#1E3A66] to-[#0D1B33]">
                <img src="{{ asset('images/ocd-seal.png') }}" alt=""
                    class="absolute -right-24 -bottom-24 w-[560px] h-[560px] object-contain opacity-[0.07] pointer-events-none">

                <div class="relative z-10 flex flex-col justify-end p-16 text-white">
                    <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase mb-4">
                        {{ __('National Disaster Risk Reduction and Management Council') }}
                    </p>
                    <h2 class="text-3xl font-bold leading-snug mb-4 max-w-md">
                        {{ __('Building prepared, resilient communities across the Philippines.') }}
                    </h2>
                    <p class="text-sm text-white/70 max-w-sm">
                        {{ __('Verifying your email keeps training records accurate and accounts secure.') }}
                    </p>
                </div>
            </div>

        </div>
    </body>
</html>
