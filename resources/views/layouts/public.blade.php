<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'OCD Training IMS') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <div class="min-h-screen flex flex-col">
            <header class="fixed top-0 inset-x-0 z-30">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 sm:pt-6">
                    <div class="flex items-center justify-between gap-4 rounded-2xl bg-[#E2762D]/55 backdrop-blur-xl backdrop-saturate-0 backdrop-brightness-125 border border-[#E2762D]/45 shadow-lg px-4 sm:px-6 py-2.5">
                        <a href="{{ route('home') }}" class="flex items-center gap-2.5 min-w-0">
                            <img src="{{ asset('images/Training-LMS-Logo.png') }}" alt="{{ __('Training IMS Logo') }}" class="h-9 w-9 object-contain shrink-0">
                            <span class="text-sm sm:text-base font-bold text-[#152A4E] truncate">{{ __('OCD Training IMS') }}</span>
                        </a>

                        <nav class="flex items-center gap-1 sm:gap-2 shrink-0">
                            <a href="{{ route('home') }}#trainings"
                                @click="
                                    const target = document.getElementById('trainings');
                                    if (target) {
                                        $event.preventDefault();
                                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                    }
                                "
                                class="inline-flex items-center rounded-2xl px-4 py-2 text-sm font-semibold text-[#152A4E] hover:bg-white/40 transition">
                                {{ __('Browse Trainings') }}
                            </a>
                            @auth
                                <a href="{{ route(Auth::user()->isParticipant() ? 'dashboard' : 'admin.dashboard') }}"
                                    class="inline-flex items-center rounded-2xl bg-[#152A4E] px-4 py-2 text-sm font-semibold text-white hover:bg-[#152A4E]/90 transition">
                                    {{ __('Go to Dashboard') }}
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                    class="inline-flex items-center rounded-2xl px-4 py-2 text-sm font-semibold text-[#152A4E] hover:bg-white/40 transition">
                                    {{ __('Log In') }}
                                </a>
                                <a href="{{ route('register') }}"
                                    class="inline-flex items-center rounded-2xl px-4 py-2 text-sm font-semibold text-[#152A4E] hover:bg-white/40 transition">
                                    {{ __('Register') }}
                                </a>
                            @endauth
                        </nav>
                    </div>
                </div>
            </header>

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="bg-[#03055A] text-white/70">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <img src="{{ asset('images/ocd-seal.png') }}" alt="{{ __('OCD Seal') }}" class="h-8 w-8 object-contain">
                        <span class="text-xs">{{ __('Office of Civil Defense — Training Information Management System') }}</span>
                    </div>
                    <span class="text-xs">&copy; {{ date('Y') }} {{ __('OCD Training IMS. All rights reserved.') }}</span>
                </div>
            </footer>
        </div>
    </body>
</html>
