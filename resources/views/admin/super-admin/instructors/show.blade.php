<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __("Instructor's Profile") }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <a href="{{ route('admin.instructors.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-[#152A4E] dark:text-white hover:text-[#E2762D] dark:hover:text-[#E2762D]">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to Instructor Roster') }}
            </a>

            <!-- Profile Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row gap-6">
                    <div class="shrink-0 w-24 h-24 rounded-full bg-[#152A4E]/10 dark:bg-white/10 flex items-center justify-center">
                        <svg class="w-12 h-12 text-[#152A4E] dark:text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>

                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Full Name') }}</div>
                            <div class="text-lg font-bold text-[#152A4E] dark:text-white">{{ $instructor->name }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __("Instructor's Rating") }}</div>
                            <div class="text-lg font-bold text-[#152A4E] dark:text-white">{{ $overallRating ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Sex') }}</div>
                            <div class="text-sm text-gray-700 dark:text-gray-200">{{ $instructor->sex ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Position') }}</div>
                            <div class="text-sm text-gray-700 dark:text-gray-200">{{ $instructor->position ?? '—' }}</div>
                        </div>
                        <div class="sm:col-span-2">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Organization / LGU / Corporation') }}</div>
                            <div class="text-sm text-gray-700 dark:text-gray-200">{{ $instructor->agency_organization ?? $instructor->lgu ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Region') }}</div>
                            <div class="text-sm text-gray-700 dark:text-gray-200">{{ $instructor->region ?? __('Central / Unassigned') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trainings Completed -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Trainings Completed') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Every training type this instructor is on file for.') }}</p>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                <th class="py-2 pr-4">{{ __('Training Title') }}</th>
                                <th class="py-2 pr-4">{{ __('Certificate Code') }}</th>
                                <th class="py-2 pr-4">{{ __('Rating') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($records as $record)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $record->training_type }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->certificate_code ?? '—' }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->rating ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Deployment Details -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Deployment Details') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Deployments recorded against this instructor (e.g. IMT, RDANA, PDNA, EOC).') }}</p>

                @php $deployments = $records->filter(fn ($r) => filled($r->deployment)); @endphp

                @if ($deployments->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No deployments on file.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Deployment') }}</th>
                                    <th class="py-2 pr-4">{{ __('Date') }}</th>
                                    <th class="py-2 pr-4">{{ __('Area') }}</th>
                                    <th class="py-2 pr-4">{{ __('Role') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($deployments as $record)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $record->deployment }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->created_at->format('M j, Y') }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->agency_organization ?? $record->lgu ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->training_type }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Complaints -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Complaints Received') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Free-text record of any complaints filed against this instructor, if applicable.') }}</p>

                <form method="POST" action="{{ route('admin.instructors.complaints', $instructor) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')

                    <textarea name="complaints" rows="4"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"
                        placeholder="{{ __('No complaints on record. Add details here if applicable.') }}">{{ old('complaints', $instructor->complaints) }}</textarea>
                    <x-input-error :messages="$errors->get('complaints')" class="mt-1" />

                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
