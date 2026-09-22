{{--
    Type-ahead city field, matching the one on participant registration.

    Filters config('cities.list') as you type so the same city is spelled the
    same way every time. Still accepts free text — the list covers cities but
    not every municipality, so anything unlisted can just be typed.

    $name / $id / $value / $placeholder / $inputClass are all optional.
--}}
@php
    $name ??= 'city';
    $id ??= 'city';
    $value ??= '';
    $placeholder ??= __('Start typing a city...');
    $inputClass ??= 'w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]';
@endphp

<div class="relative"
    x-data="{
        city: @js($value),
        show: false,
        cities: {{ Js::from(config('cities.list')) }},
        get matches() {
            const needle = this.city.toLowerCase();
            return this.cities.filter(c => c.toLowerCase().includes(needle)).slice(0, 8);
        },
        pick(option) {
            this.city = option;
            this.show = false;
        },
    }">
    <input id="{{ $id }}" type="text" name="{{ $name }}" autocomplete="off"
        x-model="city"
        @focus="show = true"
        @input="show = true"
        @click.outside="show = false"
        @keydown.escape="show = false"
        placeholder="{{ $placeholder }}"
        class="{{ $inputClass }}">

    <ul x-show="show && city.length > 0" x-cloak
        class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-lg py-1">
        <template x-for="option in matches" :key="option">
            <li @click="pick(option)"
                class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-[#152A4E]/8 dark:hover:bg-gray-600 cursor-pointer"
                x-text="option"></li>
        </template>
        <li x-show="matches.length === 0" class="px-4 py-2 text-sm text-gray-400">
            {{ __('No match — you can still use what you typed.') }}
        </li>
    </ul>
</div>
