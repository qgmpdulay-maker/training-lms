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

            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Total Instructors') }}</div>
                    <div class="text-2xl font-bold text-[#152A4E] dark:text-white mt-1">{{ $stats['total'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Regions Represented') }}</div>
                    <div class="text-2xl font-bold text-[#152A4E] dark:text-white mt-1">{{ $stats['regions'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 col-span-2 lg:col-span-1">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Complaints on Record') }}</div>
                    <div class="text-2xl font-bold mt-1 {{ $stats['complaints'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-[#152A4E] dark:text-white' }}">
                        {{ $stats['complaints'] }}
                    </div>
                </div>
            </div>

            <!-- Add Instructor -->
            @include('admin.partials.instructor-form')

            <!-- Complaints on Record -->
            @if ($stats['complaints'] > 0 && ! $complaintsOnly)
                <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 shadow-sm p-6 sm:p-8">
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-1">
                        <h2 class="text-lg font-bold text-red-800 dark:text-red-300 flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            {{ __('Complaints on Record') }}
                        </h2>
                        <a href="{{ route('admin.instructors.index', array_filter(['region' => $selectedRegion ?: null, 'instructors_q' => $instructorSearch ?: null, 'complaints_only' => 1])) }}#instructor-roster"
                            class="text-xs font-semibold text-red-700 dark:text-red-300 hover:underline whitespace-nowrap">
                            {{ __('View complaints only') }}
                        </a>
                    </div>
                    <p class="text-sm text-red-700/80 dark:text-red-300/80 mb-4">{{ __('Instructors with a complaint filed against them. Click a name to review the full record.') }}</p>

                    <div class="divide-y divide-red-200 dark:divide-red-800">
                        @foreach ($instructorsByRegion->flatten()->filter(fn ($i) => filled($i->complaints))->unique('name') as $instructor)
                            <div class="py-3 flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4">
                                <a href="{{ route('admin.instructors.show', $instructor) }}"
                                    class="shrink-0 w-full sm:w-48 font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D] dark:hover:text-[#E2762D] hover:underline">
                                    {{ $instructor->name }}
                                    <span class="block sm:inline text-xs font-normal text-gray-500 dark:text-gray-400">{{ $instructor->region ?: __('Central / Unassigned') }}</span>
                                </a>
                                <p class="flex-1 min-w-0 text-sm text-gray-700 dark:text-gray-300 line-clamp-2">{{ $instructor->complaints }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Instructor Roster, grouped by region -->
            <div id="instructor-roster" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between flex-wrap gap-3 mb-1">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">{{ __('Instructor Roster') }}</h2>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Click a name to view their full profile, deployment history, and complaints on record.') }}</p>

                <!-- Filters -->
                <div class="flex items-center flex-wrap gap-3 bg-gray-50 dark:bg-gray-700/40 rounded-lg border border-gray-200 dark:border-gray-600 px-4 py-3 mb-5">
                    <span class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ __('Region') }}</span>
                    <form method="GET" action="{{ route('admin.instructors.index') }}#instructor-roster" class="flex items-center gap-2">
                        @if ($complaintsOnly)
                            <input type="hidden" name="complaints_only" value="1">
                        @endif
                        @if ($instructorSearch !== '')
                            <input type="hidden" name="instructors_q" value="{{ $instructorSearch }}">
                        @endif
                        <select name="region" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="">{{ __('All Regions (Philippines)') }}</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption }}" @selected($selectedRegion === $regionOption)>{{ $regionOption }}</option>
                            @endforeach
                        </select>
                    </form>

                    <form method="GET" action="{{ route('admin.instructors.index') }}#instructor-roster" class="flex items-center gap-2">
                        @if ($selectedRegion)
                            <input type="hidden" name="region" value="{{ $selectedRegion }}">
                        @endif
                        @if ($instructorSearch !== '')
                            <input type="hidden" name="instructors_q" value="{{ $instructorSearch }}">
                        @endif
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" name="complaints_only" value="1" onchange="this.form.submit()" @checked($complaintsOnly)
                                class="rounded border-gray-300 dark:border-gray-600 text-[#152A4E] focus:ring-[#152A4E]">
                            {{ __('Only show instructors with complaints') }}
                        </label>
                    </form>

                    <form method="GET" action="{{ route('admin.instructors.index') }}#instructor-roster" class="flex items-center gap-2">
                        @if ($selectedRegion)
                            <input type="hidden" name="region" value="{{ $selectedRegion }}">
                        @endif
                        @if ($complaintsOnly)
                            <input type="hidden" name="complaints_only" value="1">
                        @endif
                        <input type="text" name="instructors_q" value="{{ $instructorSearch }}" placeholder="{{ __('Search name, training, certificate, agency, or LGU…') }}"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-72">
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                    </form>

                    @if ($selectedRegion || $complaintsOnly || $instructorSearch !== '')
                        <a href="{{ route('admin.instructors.index') }}#instructor-roster"
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Reset filters') }}
                        </a>
                    @endif
                </div>

                @if ($instructorsByRegion->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ $complaintsOnly || $selectedRegion || $instructorSearch !== '' ? __('No instructors match the selected filters.') : __('No instructors on file yet.') }}
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
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
