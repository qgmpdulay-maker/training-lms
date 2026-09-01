@if ($instructorsByRegion->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ $complaintsOnly || $selectedRegion || $instructorSearch !== '' ? __('No instructors match the selected filters.') : __('No instructors on file yet.') }}
    </div>
@else
    <div x-data="{ activeInstructorRegion: @js($instructorsByRegion->keys()->first()) }">
        <div class="flex items-center gap-1 overflow-x-auto bg-gray-100 dark:bg-gray-900/40 rounded-xl p-1.5">
            @foreach ($instructorsByRegion as $region => $instructors)
                <button type="button" @click="activeInstructorRegion = @js($region)"
                    :class="activeInstructorRegion === @js($region)
                        ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition whitespace-nowrap">
                    {{ $region }}
                    <span :class="activeInstructorRegion === @js($region)
                            ? 'bg-[#152A4E]/10 text-[#152A4E] dark:bg-white/15 dark:text-white px-1.5 py-0.5 rounded-full text-xs font-semibold'
                            : 'text-gray-400 dark:text-gray-500 text-xs font-normal'">
                        {{ $instructors->count() }}
                    </span>
                </button>
            @endforeach
        </div>

        @foreach ($instructorsByRegion as $region => $instructors)
            <div x-show="activeInstructorRegion === @js($region)" x-cloak class="mt-5 overflow-x-auto">
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
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
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
                                        <span title="{{ $instructor->complaints }}" class="inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-700">
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
        @endforeach
    </div>
@endif
