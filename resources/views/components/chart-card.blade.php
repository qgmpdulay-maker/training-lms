{{--
    Standard dashboard card: navy header band (white title + subtitle) over a
    white body. Use the optional `action` slot for controls that belong in the
    header, e.g. the Year select on "Graduates by Training" — style those for a
    dark background (white field, navy text).
--}}
@props([
    'title',
    'subtitle' => null,
    'bodyClass' => 'p-6',
    'borderClass' => 'border-gray-100 dark:border-gray-700',
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-xl border ' . $borderClass . ' shadow-sm overflow-hidden']) }}>
    <div class="bg-[#152A4E] px-5 sm:px-6 py-4 flex items-center justify-between flex-wrap gap-3">
        <div class="min-w-0">
            <h3 class="font-bold text-white leading-tight">{{ $title }}</h3>
            @if ($subtitle)
                <p class="text-sm text-white/70 mt-0.5">{{ $subtitle }}</p>
            @elseif (isset($description))
                {{-- Use the `description` slot instead of the `subtitle` prop when the
                     copy is conditional or contains markup (see admin/summary.blade.php). --}}
                <div class="text-sm text-white/70 mt-0.5 [&_a]:font-semibold [&_a]:text-white [&_a:hover]:underline">{{ $description }}</div>
            @endif
        </div>
        @isset($action)
            <div class="shrink-0">{{ $action }}</div>
        @endisset
    </div>
    <div class="{{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>
