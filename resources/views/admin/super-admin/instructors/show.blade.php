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
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Email') }}</div>
                            <div class="text-sm text-gray-700 dark:text-gray-200">{{ $instructor->email ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Phone') }}</div>
                            <div class="text-sm text-gray-700 dark:text-gray-200">{{ $instructor->phone ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Specialization') }}</div>
                            <div class="text-sm text-gray-700 dark:text-gray-200">{{ $instructor->specialization ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Certification') }}</div>
                            <div class="text-sm text-gray-700 dark:text-gray-200">{{ $instructor->certification ?? '—' }}</div>
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

            <!-- Training Sessions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Training Sessions — Category, Module, Rate & Comments') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ __('Completed sessions this instructor is formally attached to (via instructor selection on Summary), with participant feedback per session.') }}
                </p>

                @if ($sessions->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No completed sessions linked to this instructor yet — attach them via Summary → instructor selection.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Training') }}</th>
                                    <th class="py-2 pr-4">{{ __('Category') }}</th>
                                    <th class="py-2 pr-4">{{ __('Date') }}</th>
                                    <th class="py-2 pr-4">{{ __('Module(s)') }}</th>
                                    <th class="py-2 pr-4">{{ __('Rate') }}</th>
                                    <th class="py-2 pr-4">{{ __('Comments') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($sessions as $session)
                                    <tr>
                                        <td class="py-3 pr-4">
                                            <div class="font-medium text-[#152A4E] dark:text-white">{{ $session['training_title'] }}</div>
                                            <div class="text-xs text-gray-400">{{ $session['venue'] }}</div>
                                        </td>
                                        <td class="py-3 pr-4">
                                            @if ($session['category'])
                                                <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2 py-0.5 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600">
                                                    {{ $session['category'] }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $session['preferred_date']->format('M j, Y') }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ ! empty($session['modules']) ? implode(', ', $session['modules']) : '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                            {{ $session['rate'] ?? '—' }}
                                            @if ($session['responses'] > 0)
                                                <span class="text-gray-400">({{ trans_choice(':count response|:count responses', $session['responses'], ['count' => $session['responses']]) }})</span>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4">
                                            @if (! empty($session['comments']))
                                                <div x-data="{ open: false }">
                                                    <button type="button" @click="open = !open" class="text-xs font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D] inline-flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                        {{ trans_choice(':count comment|:count comments', count($session['comments']), ['count' => count($session['comments'])]) }}
                                                    </button>
                                                    <ul x-show="open" x-cloak class="mt-2 space-y-1">
                                                        @foreach ($session['comments'] as $comment)
                                                            <li class="text-xs text-gray-600 dark:text-gray-300">"{{ $comment }}"</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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
                                    <th class="py-2 pr-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($deployments as $record)
                                    <tr x-data="{ editing: false }">
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $record->deployment }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->deployment_date?->format('M j, Y') ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->agency_organization ?? $record->lgu ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->deployment_role ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-right">
                                            <button type="button" @click="editing = !editing" class="text-xs font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D]">
                                                {{ __('Edit') }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr x-show="editing" x-cloak>
                                        <td colspan="5" class="py-3 pl-0 pr-4 bg-gray-50/60 dark:bg-gray-900/20">
                                            <form method="POST" action="{{ route('admin.instructors.update', $record) }}" class="flex flex-wrap items-end gap-3">
                                                @csrf
                                                @method('PATCH')
                                                <div>
                                                    <x-input-label :value="__('Deployment')" class="text-xs" />
                                                    <input type="text" name="deployment" value="{{ $record->deployment }}"
                                                        class="mt-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Date')" class="text-xs" />
                                                    <input type="date" name="deployment_date" value="{{ $record->deployment_date?->format('Y-m-d') }}"
                                                        class="mt-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Area (Agency / Organization)')" class="text-xs" />
                                                    <input type="text" name="agency_organization" value="{{ $record->agency_organization }}"
                                                        class="mt-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Role')" class="text-xs" />
                                                    <input type="text" name="deployment_role" value="{{ $record->deployment_role }}"
                                                        class="mt-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                </div>
                                                <button type="submit"
                                                    class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition">
                                                    {{ __('Save') }}
                                                </button>
                                            </form>
                                        </td>
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

                @if (filled($instructor->complaints))
                    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-line">
                        {{ $instructor->complaints }}
                    </div>
                @else
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No complaints on record.') }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
