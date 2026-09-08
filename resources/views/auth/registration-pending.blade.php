<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'OCD Training IMS') }} — {{ __('Pending Approval') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col lg:flex-row">

            <!-- Left: Message -->
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
                        <div class="h-14 w-14 rounded-full bg-[#152A4E]/8 flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-[#152A4E]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <p class="text-sm text-gray-500 mb-1">{{ __('Email verified') }}</p>
                        <h1 class="text-2xl font-bold text-[#152A4E] mb-4">
                            {{ __('Your account is awaiting approval') }}
                        </h1>
                        <p class="text-sm text-gray-500 mb-8">
                            {{ __("A Super Admin needs to review and approve your account before you can log in. We'll email you as soon as that happens.") }}
                        </p>

                        <a href="{{ route('login') }}" class="inline-flex items-center text-sm text-[#152A4E] font-semibold hover:text-[#E2762D]">
                            {{ __('Back to Sign In') }}
                        </a>
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
                        {{ __('Account approval keeps the training portal limited to verified government and partner personnel.') }}
                    </p>
                </div>
            </div>

        </div>
    </body>
</html>
