{{--
    The public nav's links, rendered twice: inline on sm+ and stacked inside
    the mobile menu panel. Shared so the two can't drift apart.

    $itemClass — layout/spacing for the current context.
--}}
@php
    $itemClass ??= 'inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold text-white hover:bg-white/15 transition';
@endphp

<a href="{{ route('home') }}#trainings"
    @click="
        const target = document.getElementById('trainings');
        if (target) {
            $event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        open = false;
    "
    class="{{ $itemClass }}">
    {{ __('Browse Trainings') }}
</a>

<a href="{{ route('about') }}" class="{{ $itemClass }}">
    {{ __('About Us') }}
</a>

@auth
    <a href="{{ route(Auth::user()->isParticipant() ? 'dashboard' : 'admin.dashboard') }}" class="{{ $itemClass }}">
        {{ __('Go to Dashboard') }}
    </a>
@else
    <a href="{{ route('login') }}" class="{{ $itemClass }}">
        {{ __('Log In') }}
    </a>
    <a href="{{ route('register') }}" class="{{ $itemClass }}">
        {{ __('Register') }}
    </a>
@endauth
