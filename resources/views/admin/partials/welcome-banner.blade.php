{{--
    The navy "Welcome back" banner at the top of both admin dashboards.
    Expects $user, $eyebrow (the small orange line), and $description. Shows
    the user's profile picture beside the greeting once they've uploaded one
    on My Profile; without one the banner is text only.
--}}
<div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-[#152A4E] via-[#1E3A66] to-[#0D1B33] px-6 py-8 sm:px-10 sm:py-10">
    <img src="{{ asset('images/ocd-seal.png') }}" alt=""
        class="absolute -right-10 -bottom-16 w-64 h-64 object-contain opacity-[0.08] pointer-events-none">
    <div class="relative z-10 flex items-center gap-5 sm:gap-7">
        @if ($user->picture)
            <img src="{{ asset('storage/'.$user->picture) }}" alt="{{ $user->name }}"
                class="shrink-0 w-16 h-16 sm:w-24 sm:h-24 object-cover rounded-full ring-4 ring-white/15 shadow-lg">
        @endif
        <div class="min-w-0">
            <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase mb-2">
                {{ $eyebrow }}
            </p>
            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">
                {{ __('Welcome back, :name', ['name' => explode(' ', $user->name)[0]]) }}
            </h1>
            <p class="text-sm text-white/70 max-w-xl">
                {{ $description }}
            </p>
        </div>
    </div>
</div>
