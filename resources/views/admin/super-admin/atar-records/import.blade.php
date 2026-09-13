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
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Preview Import') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        {{ __(':count training record(s) parsed for :region. Review below, then confirm to save them.', ['count' => count($preview['rows']), 'region' => $preview['region']]) }}
                    </p>

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
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Import Training Database') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">
                        {{ __('Upload the CDTI Training Database CSV export for a region. You\'ll get a preview to review before anything is saved.') }}
                    </p>

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
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
