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
            <header class="fixed top-0 inset-x-0 z-30 bg-[#E2762D] shadow-md">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 min-w-0">
                        <svg class="w-5 h-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.5 1.5 0 012.122 0L22.28 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        <span class="text-sm sm:text-base font-bold text-white truncate">{{ __('Home') }}</span>
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
                            class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold text-white hover:bg-white/15 transition">
                            {{ __('Browse Trainings') }}
                        </a>
                        <a href="{{ route('about') }}"
                            class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold text-white hover:bg-white/15 transition">
                            {{ __('About Us') }}
                        </a>
                        @auth
                            <a href="{{ route(Auth::user()->isParticipant() ? 'dashboard' : 'admin.dashboard') }}"
                                class="inline-flex items-center rounded-md bg-[#152A4E] px-4 py-2 text-sm font-semibold text-white hover:bg-[#152A4E]/90 transition">
                                {{ __('Go to Dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                                class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold text-white hover:bg-white/15 transition">
                                {{ __('Log In') }}
                            </a>
                            <a href="{{ route('register') }}"
                                class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold text-white hover:bg-white/15 transition">
                                {{ __('Register') }}
                            </a>
                        @endauth
                    </nav>
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
                    <span class="text-xs">&copy; {{ date('Y') }} {{ __('ICTS-SDIMD Training IMS. All rights reserved.') }}</span>
                </div>
            </footer>
        </div>
    </body>
</html>
