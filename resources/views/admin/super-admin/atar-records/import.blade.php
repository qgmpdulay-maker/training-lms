<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import Training Database') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admin.atar-records.index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to ATAR Records') }}
            </a>

            @if ($errors->any())
                <div class="flex items-start gap-2 text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if ($preview)
                <x-chart-card body-class="p-6 sm:p-8"
                    :title="__('Preview Import')"
                    :subtitle="__(':count training record(s) parsed for :region. Review below, then confirm to save them.', ['count' => count($preview['rows']), 'region' => $preview['region']])">

                    @if (! empty($preview['warnings']))
                        <div class="flex items-start gap-2 text-sm text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3 mb-6">
                            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach ($preview['warnings'] as $warning)
                                    <li>{{ $warning }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="overflow-x-auto border border-gray-100 dark:border-gray-700 rounded-lg mb-6">
                        <table class="min-w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-2 font-medium">{{ __('Code') }}</th>
                                    <th class="px-4 py-2 font-medium">{{ __('Training') }}</th>
                                    <th class="px-4 py-2 font-medium">{{ __('Mode') }}</th>
                                    <th class="px-4 py-2 font-medium">{{ __('Date') }}</th>
                                    <th class="px-4 py-2 font-medium text-right">{{ __('Graduates') }}</th>
                                    <th class="px-4 py-2 font-medium text-right">{{ __('Rating') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($preview['rows'] as $row)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $row['atar_tracker_code'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-gray-800 dark:text-gray-100 max-w-xs truncate" title="{{ $row['training_title'] }}">{{ $row['training_title'] }}</td>
                                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $row['mode_of_implementation'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $row['date_conducted'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-gray-800 dark:text-gray-100 text-right">{{ $row['graduates'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-gray-800 dark:text-gray-100 text-right">{{ $row['overall_rating'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-3">
                        <form method="POST" action="{{ route('admin.atar-records.import.cancel') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-lg px-6 py-3 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                                {{ __('Cancel') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.atar-records.import.confirm') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-6 py-3 hover:bg-[#1E3A66] transition">
                                {{ __('Confirm Import') }}
                            </button>
                        </form>
                    </div>
                </x-chart-card>
            @else
                {{-- Import parsing is purely positional (see AtarImportParser's class
                     doc) — it reads by column INDEX, not header text, so getting the
                     column order right matters far more than what the header row
                     actually says. This panel exists so that's obvious up front,
                     instead of the admin discovering it via a garbled preview. --}}
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6 sm:p-8 space-y-4" x-data="{ expanded: false }">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 shrink-0 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <div class="flex-1">
                            <h2 class="font-semibold text-amber-900 dark:text-amber-200">{{ __('Before you upload: column order matters, not column names') }}</h2>
                            <p class="text-sm text-amber-800 dark:text-amber-300 mt-1">
                                {{ __('The importer reads each column by its position, not by what the header says — so a CSV with the right column headers in the wrong order will still import incorrectly. The first two rows of the file are always skipped (put anything there), then every column after that must appear in exactly this order:') }}
                            </p>
                        </div>
                    </div>

                    <button type="button" @click="expanded = !expanded" class="text-sm font-semibold text-amber-900 dark:text-amber-200 hover:underline">
                        <span x-show="!expanded">{{ __('Show the 37 expected columns, in order ▾') }}</span>
                        <span x-show="expanded" x-cloak>{{ __('Hide column list ▴') }}</span>
                    </button>

                    <ol x-show="expanded" x-cloak class="grid sm:grid-cols-2 gap-x-6 gap-y-1 text-sm text-amber-900 dark:text-amber-200 list-decimal list-inside">
                        @foreach ($columnLabels as $label)
                            <li>{{ $label }}</li>
                        @endforeach
                    </ol>

                    <ul class="text-sm text-amber-800 dark:text-amber-300 list-disc list-inside space-y-1">
                        <li>{{ __('Column 35 ("unused") is a genuine gap in the real CDTI export — leave it blank, but don\'t delete the column itself, or every column after it will shift over by one.') }}</li>
                        <li>{{ __('"Signed", "L1 Completed", and "L2 Completed" only count as true when the cell says exactly TRUE — anything else (including a blank cell) is read as false.') }}</li>
                        <li>{{ __('The importer stops reading at the first row with a blank Training Title — this is what lets it skip over a CDTI "DO NOT INPUT" summary block at the bottom of a sheet automatically. Just make sure there\'s no accidental blank Training Title in the middle of your real rows.') }}</li>
                    </ul>

                    <a href="{{ route('admin.atar-records.import.template') }}"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-[#152A4E] dark:text-white bg-white dark:bg-gray-800 border border-amber-300 dark:border-amber-700 rounded-lg px-4 py-2 hover:bg-amber-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3" /></svg>
                        {{ __('Download a blank CSV template') }}
                    </a>
                </div>

                <x-chart-card body-class="p-6 sm:p-8"
                    :title="__('Import Training Database')"
                    :subtitle="__('Upload the CDTI Training Database CSV export for a region. You\'ll get a preview to review before anything is saved.')">

                    <form method="POST" action="{{ route('admin.atar-records.import.store') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf

                        <div>
                            <x-input-label for="region" :value="__('Region')" />
                            <select id="region" name="region" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                                <option value="" disabled {{ old('region') ? '' : 'selected' }}>{{ __('Select') }}</option>
                                @foreach ($regions as $regionOption)
                                    <option value="{{ $regionOption }}" @selected(old('region') === $regionOption)>{{ $regionOption }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('region')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="csv_file" :value="__('CSV File')" />
                            <input id="csv_file" type="file" name="csv_file" accept=".csv,.txt" required
                                class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-300 file:me-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#152A4E]/10 file:text-[#152A4E] hover:file:bg-[#152A4E]/20">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Export the "ATAR" sheet as CSV before uploading.') }}</p>
                            <x-input-error :messages="$errors->get('csv_file')" class="mt-1" />
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-6 py-3 hover:bg-[#1E3A66] transition">
                                {{ __('Parse & Preview') }}
                            </button>
                        </div>
                    </form>
                </x-chart-card>
            @endif
        </div>
    </div>
</x-app-layout>
