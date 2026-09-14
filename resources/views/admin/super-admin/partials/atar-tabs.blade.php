{{--
    Shared tab bar for the two ATAR features — the narrative report builder
    (AtarReport) and the CSV "Training Database" tracker import (AtarRecord).
    They used to be two separate top-level sidebar links, which read as
    duplicate/confusing entries for what looks like the same feature. Now
    there's one sidebar entry ("ATAR") and this tab bar, included at the top
    of both index pages, is what actually switches between them.
--}}
<div class="flex gap-1 border-b border-gray-200 dark:border-gray-700">
    <a href="{{ route('admin.atar-reports.index') }}"
        @class([
            'px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition',
            'border-[#152A4E] text-[#152A4E] dark:border-white dark:text-white' => request()->routeIs('admin.atar-reports.*'),
            'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => ! request()->routeIs('admin.atar-reports.*'),
        ])>
        {{ __('Reports') }}
    </a>
    <a href="{{ route('admin.atar-records.index') }}"
        @class([
            'px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition',
            'border-[#152A4E] text-[#152A4E] dark:border-white dark:text-white' => request()->routeIs('admin.atar-records.*'),
            'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => ! request()->routeIs('admin.atar-records.*'),
        ])>
        {{ __('Records (CSV Import)') }}
    </a>
</div>
