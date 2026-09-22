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
            {{-- Below `sm` the links total more than a 375px phone can hold, so
                 they collapse into a menu panel behind the toggle. --}}
            <header x-data="{ open: false }" class="fixed top-0 inset-x-0 z-30 bg-[#E2762D] shadow-md">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 min-w-0">
                        <img src="{{ asset('images/Training-LMS-Logo.png') }}" alt="{{ __('Training IMS Logo') }}" class="h-9 w-9 object-contain shrink-0">
                        <span class="text-sm font-semibold text-white truncate">{{ __('Home') }}</span>
                    </a>

                    <nav class="hidden sm:flex items-center gap-1 sm:gap-2 shrink-0">
                        @include('partials.public-nav-links')

                    </nav>

                    <div class="flex sm:hidden items-center gap-2 shrink-0">
                        <button type="button" @click="open = ! open"
                            :aria-expanded="open ? 'true' : 'false'" aria-label="{{ __('Menu') }}"
                            class="inline-flex items-center justify-center rounded-md p-2 text-white hover:bg-white/15 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path x-show="! open" stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div x-show="open" x-cloak @click.outside="open = false"
                    class="sm:hidden border-t border-white/20 px-4 py-2">
                    @include('partials.public-nav-links', ['itemClass' => 'block rounded-md px-3 py-2.5 text-sm font-semibold text-white hover:bg-white/15 transition'])
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
