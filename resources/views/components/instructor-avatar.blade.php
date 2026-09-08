@props(['instructor', 'class' => 'w-8 h-8 text-xs'])

@if ($instructor->photo_path)
    <img src="{{ asset('storage/'.$instructor->photo_path) }}" alt="{{ $instructor->name }}" class="{{ $class }} rounded-full object-cover shrink-0">
@else
    <div class="{{ $class }} rounded-full bg-[#152A4E]/10 dark:bg-white/10 flex items-center justify-center text-[#152A4E] dark:text-white font-semibold shrink-0">
        {{ collect(explode(' ', $instructor->name))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
    </div>
@endif
