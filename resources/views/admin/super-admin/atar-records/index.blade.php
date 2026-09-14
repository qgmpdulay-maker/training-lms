<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ATAR') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @include('admin.super-admin.partials.atar-tabs')

            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white">{{ __('ATAR Records') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Historical training accomplishment data, imported from regional Training Database exports.') }}</p>
                    {{-- Full column-order instructions + a downloadable template live on the
                         Import page itself (see import.blade.php) — this is just a visible
                         pointer to them from here, so the guidance isn't hidden behind a
                         click on "Import CSV" first. --}}
                    <p class="text-xs text-gray-400 mt-1">
                        {{ __('CSV columns must follow a specific order to import correctly —') }}
                        <a href="{{ route('admin.atar-records.import') }}" class="underline hover:text-[#152A4E] dark:hover:text-white">{{ __('see the import instructions') }}</a>
                        {{ __('or') }}
                        <a href="{{ route('admin.atar-records.import.template') }}" class="underline hover:text-[#152A4E] dark:hover:text-white">{{ __('download the CSV template') }}</a>.
                    </p>
                </div>
                <a href="{{ route('admin.atar-records.import') }}"
                    class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition shrink-0">
                    {{ __('Import CSV') }}
                </a>
            </div>

            <form method="GET" class="flex flex-wrap gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                <select name="region" onchange="this.form.submit()"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    <option value="">{{ __('All Regions') }}</option>
                    @foreach ($regions as $regionOption)
                        <option value="{{ $regionOption }}" @selected($selectedRegion === $regionOption)>{{ $regionOption }}</option>
                    @endforeach
                </select>
            </form>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ __('Region') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Training') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Mode') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                                <th class="px-4 py-3 font-medium text-right">{{ __('Graduates') }}</th>
                                <th class="px-4 py-3 font-medium text-right">{{ __('Rating') }}</th>
                                <th class="px-4 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($records as $record)
                                <tr>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $record->region }}</td>
                                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100 max-w-sm truncate" title="{{ $record->training_title }}">{{ $record->training_title }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $record->mode_of_implementation ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $record->date_conducted ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100 text-right">{{ $record->graduates ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100 text-right">{{ $record->overall_rating ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('admin.atar-records.destroy', $record) }}" onsubmit="return confirm('{{ __('Delete this record?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-semibold">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                        {{ __('No ATAR records imported yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $records->links() }}
        </div>
    </div>
</x-app-layout>
