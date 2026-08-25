@props(['href', 'active' => false])

<a href="{{ $href }}"
    {{ $attributes->class([
        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
        'bg-white/10 text-white' => $active,
        'text-white/70 hover:text-white hover:bg-white/5' => ! $active,
    ]) }}>
    @isset($icon)
        <span class="shrink-0 w-5 h-5 {{ $active ? 'text-[#E2762D]' : 'text-white/50' }}">{{ $icon }}</span>
    @endisset
    <span class="truncate" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $slot }}</span>
</a>
