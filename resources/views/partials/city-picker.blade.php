{{--
    Pick-from-the-list city field: choose from every Philippine city and
    municipality (App\Support\CityDirectory), never type one in. Typing only
    narrows the list, so the same city is always spelled the same way and the
    server can trust what it gets.

    Reads the list from window.phCities, which the host page defines once —
    the list holds 1,600-plus entries and these pickers get swapped in and out
    of live search results, so repeating it inside each one would balloon the
    page.

    $name      form field name; use "cities[]" with $multiple, "city" without
    $selected  labels already chosen
    $multiple  allow several cities (chips) instead of one
    $label     accessible name for the search box
--}}
@php
    $name ??= 'city';
    $selected = array_values($selected ?? []);
    $multiple ??= false;
    $label ??= __('City');
@endphp

<div class="relative"
    x-data="{
        q: '',
        open: false,
        multiple: @js($multiple),
        selected: @js($selected),
        get matches() {
            const needle = this.q.trim().toLowerCase();
            return (window.phCities || [])
                .filter(c => ! this.selected.includes(c) && c.toLowerCase().includes(needle))
                .slice(0, 60);
        },
        add(city) {
            this.selected = this.multiple ? [...this.selected, city] : [city];
            this.q = '';
            this.open = false;
        },
        remove(city) {
            this.selected = this.selected.filter(c => c !== city);
        },
    }"
    @click.outside="open = false"
    @keydown.escape="open = false">

    <template x-for="city in selected" :key="city">
        <input type="hidden" name="{{ $name }}" :value="city">
    </template>

    <div class="flex flex-wrap items-center gap-1.5 w-full min-h-[2.5rem] rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-2 py-1.5 focus-within:border-[#152A4E] focus-within:ring-1 focus-within:ring-[#152A4E]">
        <template x-for="city in selected" :key="city">
            <span class="inline-flex items-center gap-1 rounded-md bg-[#152A4E]/10 dark:bg-white/10 text-[#152A4E] dark:text-white text-xs font-medium ps-2 pe-1 py-1">
                <span x-text="city"></span>
                <button type="button" @click="remove(city)" class="rounded p-0.5 hover:bg-[#152A4E]/15 dark:hover:bg-white/15" aria-label="{{ __('Remove') }}">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </span>
        </template>
        <input type="text" x-model="q" autocomplete="off" aria-label="{{ $label }}"
            x-show="multiple || selected.length === 0"
            @focus="open = true" @input="open = true"
            @keydown.enter.prevent="if (matches.length) add(matches[0])"
            placeholder="{{ $multiple ? __('Select one or more cities...') : __('Select a city...') }}"
            class="flex-1 min-w-[10rem] border-0 bg-transparent p-1 text-sm focus:ring-0 dark:text-white">
    </div>

    <ul x-show="open" x-cloak
        class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-lg py-1">
        <template x-for="option in matches" :key="option">
            <li @click="add(option)"
                class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-[#152A4E]/8 dark:hover:bg-gray-600 cursor-pointer"
                x-text="option"></li>
        </template>
        <li x-show="matches.length === 0" class="px-4 py-2 text-sm text-gray-400">{{ __('No matching city or municipality.') }}</li>
    </ul>
</div>
