<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Instructors') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <!-- Add Instructor -->
            @include('admin.partials.instructor-form')

            <!-- Instructor Roster, grouped by region -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Instructor Roster') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('All instructors on file, grouped by region. Click a name to view their full profile, deployment history, and complaints on record.') }}</p>

                @if ($instructorsByRegion->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No instructors on file yet.') }}
                    </div>
                @else
                    <div class="space-y-8">
                        @foreach ($instructorsByRegion as $region => $instructors)
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wide text-[#152A4E] dark:text-white mb-3">{{ $region }}</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                <th class="py-2 pr-4">{{ __('Name') }}</th>
                                                <th class="py-2 pr-4">{{ __('Type of Training') }}</th>
                                                <th class="py-2 pr-4">{{ __('Certificate Code') }}</th>
                                                <th class="py-2 pr-4">{{ __('Deployment') }}</th>
                                                <th class="py-2 pr-4">{{ __('Agency / LGU') }}</th>
                                                <th class="py-2 pr-4">{{ __('Rating') }}</th>
                                                <th class="py-2 pr-4">{{ __('Complaints') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @foreach ($instructors as $instructor)
                                                <tr>
                                                    <td class="py-3 pr-4 font-medium">
                                                        <a href="{{ route('admin.instructors.show', $instructor) }}" class="text-[#152A4E] dark:text-white hover:text-[#E2762D] dark:hover:text-[#E2762D] hover:underline">
                                                            {{ $instructor->name }}
                                                        </a>
                                                    </td>
                                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->training_type }}</td>
                                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->certificate_code ?? '—' }}</td>
                                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->deployment ?? '—' }}</td>
                                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->agency_organization ?? $instructor->lgu ?? '—' }}</td>
                                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->rating ?? '—' }}</td>
                                                    <td class="py-3 pr-4">
                                                        @if (filled($instructor->complaints))
                                                            <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-700">
                                                                {{ __('On record') }}
                                                            </span>
                                                        @else
                                                            <span class="text-gray-400">{{ __('None') }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
