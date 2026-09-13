{{--
    This page uses Alpine.js for every repeatable-row section (graduates,
    dropouts, lecturers, signatories, L1 modules) instead of Blade loops,
    so rows can be added/removed client-side without a page reload. $seed
    below is the report's current data, passed into the atarEditor()
    Alpine component (defined in the <script> at the bottom of this file)
    as its starting state. Each row renders inputs named e.g.
    `graduates_list[0][name]` — PHP parses that bracket syntax back into
    the nested array AtarReportController@update validates and saves.
--}}
@php
    $seed = [
        'graduates' => $report->graduates_list ?: [],
        'dropouts' => $report->dropouts_list ?: [],
        'lecturers' => $report->lecturers_list ?: [],
        'signatories' => $report->signatories ?: [['role' => 'Prepared by', 'name' => '', 'title' => ''], ['role' => 'Approved by', 'name' => '', 'title' => '']],
        'modules' => collect($report->l1_modules ?: [])->map(fn ($m) => [
            'module' => $m['module'] ?? '',
            'distribution' => [
                1 => $m['distribution'][1] ?? 0,
                2 => $m['distribution'][2] ?? 0,
                3 => $m['distribution'][3] ?? 0,
                4 => $m['distribution'][4] ?? 0,
                5 => $m['distribution'][5] ?? 0,
            ],
        ])->values()->all(),
        'removePhotos' => [],
    ];
    $l2 = $report->l2_stats ?: [];
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit ATAR') }}
        </h2>
    </x-slot>

    <div class="py-10" x-data="atarEditor(@js($seed))">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white">{{ $report->title ?: __('Untitled ATAR') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        @if ($report->trainingRequest)
                            {{ __('Generated from') }} <span class="font-medium">{{ $report->trainingRequest->training_title }}</span> ({{ $report->trainingRequest->region }})
                        @else
                            {{ __('Written from scratch — not linked to a tracked training.') }}
                        @endif
                    </p>
                </div>
                <div class="flex gap-3 shrink-0">
                    <a href="{{ route('admin.atar-reports.pdf', $report) }}" target="_blank"
                        class="inline-flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-lg px-4 py-2.5 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        {{ __('Preview PDF') }}
                    </a>
                    {{-- Only offered when linked to a training — hits AtarReportController@recompute,
                         which re-runs AtarReportGenerator and overwrites just the data-backed
                         fields below, leaving the narrative sections untouched. This is a
                         separate tiny <form>, not part of the big form further down. --}}
                    @if ($report->trainingRequest)
                        <form method="POST" action="{{ route('admin.atar-reports.recompute', $report) }}" onsubmit="return confirm('{{ __('This will overwrite Attendees, Graduates, Dropouts, Lecturers, and the L1/L2 annexes with current training data. Narrative sections are left untouched. Continue?') }}');">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-lg px-4 py-2.5 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                {{ __('Recompute from training') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- The one form that saves everything below — header fields, narrative
                 textareas, every Alpine repeatable table, and the L2 stats grid — in a
                 single AtarReportController@update request. enctype is multipart
                 because of the photo file input further down. --}}
            <form method="POST" action="{{ route('admin.atar-reports.update', $report) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Header details --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-4">
                    <h2 class="text-lg font-semibold text-[#152A4E] dark:text-white">{{ __('Header') }}</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Title of Activity') }}</label>
                            <input type="text" name="title" value="{{ old('title', $report->title) }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Venue / Location') }}</label>
                            <input type="text" name="venue" value="{{ old('venue', $report->venue) }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Date(s)') }}</label>
                            <input type="text" name="date_range" value="{{ old('date_range', $report->date_range) }}" placeholder="{{ __('e.g. 04-08 May 2026 (inclusive of Travel Time)') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Funding Source') }}</label>
                            <input type="text" name="funding_source" value="{{ old('funding_source', $report->funding_source) }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Status') }}</label>
                            <select name="status" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                                <option value="draft" @selected(old('status', $report->status) === 'draft')>{{ __('Draft') }}</option>
                                <option value="final" @selected(old('status', $report->status) === 'final')>{{ __('Finalized') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Narrative: plain textareas, no Alpine involved — these map straight to
                     AtarReport's longtext columns and are never auto-filled (see
                     AtarReportGenerator's class doc for why). --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-4">
                    <h2 class="text-lg font-semibold text-[#152A4E] dark:text-white">{{ __('Narrative') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('These sections are never auto-written — fill them in yourself.') }}</p>

                    @foreach ([
                        'background' => 'Background',
                        'objectives' => 'Objective(s)',
                        'highlights' => 'Highlights (day-by-day)',
                        'issues_and_concerns' => 'Issues and Concerns',
                        'ways_forward' => 'Ways Forward',
                    ] as $field => $label)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __($label) }}</label>
                            <textarea name="{{ $field }}" rows="{{ $field === 'highlights' ? 10 : 4 }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">{{ old($field, $report->$field) }}</textarea>
                        </div>
                    @endforeach
                </div>

                {{-- Attendees & graduates summary --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-4">
                    <h2 class="text-lg font-semibold text-[#152A4E] dark:text-white">{{ __('Attendees & Graduates') }}</h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Attendees') }}</label>
                        <textarea name="attendees_narrative" rows="6" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono focus:border-[#152A4E] focus:ring-[#152A4E]">{{ old('attendees_narrative', $report->attendees_narrative) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Graduates (summary line)') }}</label>
                        <textarea name="graduates_summary" rows="3" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">{{ old('graduates_summary', $report->graduates_summary) }}</textarea>
                    </div>

                    {{-- List of Graduates: Alpine-managed repeatable table. `graduates` is
                         an array of {code,name,gender,agency} objects seeded from
                         $report->graduates_list; the "+ Add row" button just pushes a
                         blank object, and x-for re-renders the numbered `name` attributes
                         so the server always receives a clean sequential array. --}}
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('List of Graduates (Declaration of Graduates annex)') }}</label>
                            <button type="button" @click="graduates.push({code:'',name:'',gender:'',agency:''})" class="text-xs font-semibold text-[#152A4E] dark:text-blue-300 hover:underline">+ {{ __('Add row') }}</button>
                        </div>
                        <div class="mt-2 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium">{{ __('Certificate Code') }}</th>
                                        <th class="px-3 py-2 text-left font-medium">{{ __('Full Name') }}</th>
                                        <th class="px-3 py-2 text-left font-medium">{{ __('Gender') }}</th>
                                        <th class="px-3 py-2 text-left font-medium">{{ __('Agency') }}</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in graduates" :key="index">
                                        <tr class="border-t border-gray-100 dark:border-gray-700">
                                            <td class="px-2 py-1"><input type="text" x-model="row.code" :name="`graduates_list[${index}][code]`" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                            <td class="px-2 py-1"><input type="text" x-model="row.name" :name="`graduates_list[${index}][name]`" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                            <td class="px-2 py-1"><input type="text" x-model="row.gender" :name="`graduates_list[${index}][gender]`" class="w-16 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                            <td class="px-2 py-1"><input type="text" x-model="row.agency" :name="`graduates_list[${index}][agency]`" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                            <td class="px-2 py-1 text-right"><button type="button" @click="graduates.splice(index,1)" class="text-red-500 hover:text-red-700 text-xs">✕</button></td>
                                        </tr>
                                    </template>
                                    <tr x-show="graduates.length === 0"><td colspan="5" class="px-3 py-3 text-center text-xs text-gray-400">{{ __('No graduates listed yet.') }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- List of Dropouts: same Alpine pattern as List of Graduates above,
                         just a smaller row shape (no certificate code). --}}
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('List of Dropouts') }}</label>
                            <button type="button" @click="dropouts.push({name:'',gender:'',agency:''})" class="text-xs font-semibold text-[#152A4E] dark:text-blue-300 hover:underline">+ {{ __('Add row') }}</button>
                        </div>
                        <div class="mt-2 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium">{{ __('Full Name') }}</th>
                                        <th class="px-3 py-2 text-left font-medium">{{ __('Gender') }}</th>
                                        <th class="px-3 py-2 text-left font-medium">{{ __('Agency') }}</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in dropouts" :key="index">
                                        <tr class="border-t border-gray-100 dark:border-gray-700">
                                            <td class="px-2 py-1"><input type="text" x-model="row.name" :name="`dropouts_list[${index}][name]`" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                            <td class="px-2 py-1"><input type="text" x-model="row.gender" :name="`dropouts_list[${index}][gender]`" class="w-16 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                            <td class="px-2 py-1"><input type="text" x-model="row.agency" :name="`dropouts_list[${index}][agency]`" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                            <td class="px-2 py-1 text-right"><button type="button" @click="dropouts.splice(index,1)" class="text-red-500 hover:text-red-700 text-xs">✕</button></td>
                                        </tr>
                                    </template>
                                    <tr x-show="dropouts.length === 0"><td colspan="4" class="px-3 py-3 text-center text-xs text-gray-400">{{ __('No dropouts listed.') }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Photos: existing thumbnails are plain Blade (not Alpine) — checking one
                     marks its path for deletion via `remove_photos[]`; AtarReportController@update
                     deletes those from storage and drops them from the saved list. New files
                     picked below are appended, they don't replace what's already stored. --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-4">
                    <h2 class="text-lg font-semibold text-[#152A4E] dark:text-white">{{ __('Photos') }}</h2>
                    @if (!empty($report->photos))
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach ($report->photos as $path)
                                {{-- The checkbox overlays the thumbnail; :checked on it drives the "Remove" label's opacity via the group-has-[:checked] Tailwind selector, no JS needed --}}
                                <label class="relative block group">
                                    <img src="{{ asset('storage/'.$path) }}" class="w-full h-28 object-cover rounded-lg border border-gray-200 dark:border-gray-700" />
                                    <span class="absolute inset-0 bg-black/50 opacity-0 group-has-[:checked]:opacity-100 rounded-lg flex items-center justify-center text-white text-xs font-semibold">{{ __('Remove') }}</span>
                                    <input type="checkbox" name="remove_photos[]" value="{{ $path }}" class="absolute top-1 right-1" />
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Add photos') }}</label>
                        <input type="file" name="photos[]" multiple accept="image/*" class="mt-1 w-full text-sm text-gray-600 dark:text-gray-300" />
                    </div>
                </div>

                {{-- Lecturers: seeded from any Instructor records linked to the training
                     (AtarReportGenerator::lecturers()), but guest resource persons with no
                     Instructor record won't be there automatically — add those rows by hand. --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-[#152A4E] dark:text-white">{{ __('Lecturers, Resource Persons, Facilitators, and Secretariat') }}</h2>
                        <button type="button" @click="lecturers.push({name:'',organization:'',role:''})" class="text-xs font-semibold text-[#152A4E] dark:text-blue-300 hover:underline">+ {{ __('Add row') }}</button>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">{{ __('Name') }}</th>
                                    <th class="px-3 py-2 text-left font-medium">{{ __('Organization / Agency') }}</th>
                                    <th class="px-3 py-2 text-left font-medium">{{ __('Role') }}</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, index) in lecturers" :key="index">
                                    <tr class="border-t border-gray-100 dark:border-gray-700">
                                        <td class="px-2 py-1"><input type="text" x-model="row.name" :name="`lecturers_list[${index}][name]`" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                        <td class="px-2 py-1"><input type="text" x-model="row.organization" :name="`lecturers_list[${index}][organization]`" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                        <td class="px-2 py-1"><input type="text" x-model="row.role" :name="`lecturers_list[${index}][role]`" placeholder="Resource Person" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                        <td class="px-2 py-1 text-right"><button type="button" @click="lecturers.splice(index,1)" class="text-red-500 hover:text-red-700 text-xs">✕</button></td>
                                    </tr>
                                </template>
                                <tr x-show="lecturers.length === 0"><td colspan="4" class="px-3 py-3 text-center text-xs text-gray-400">{{ __('No lecturers listed yet.') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- L1 Reaction Evaluation: each module row's `distribution` is a
                     {1:count,2:count,...,5:count} object, one input per rating value.
                     Only the raw counts are ever submitted — AtarReportController@update
                     always recomputes `responses` and `average` server-side from whatever
                     counts land here, so what's displayed after saving is authoritative,
                     not whatever this page happened to compute client-side. --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-[#152A4E] dark:text-white">{{ __('Annex: Level 1 Reaction Evaluation') }}</h2>
                        <button type="button" @click="modules.push({module:'',distribution:{1:0,2:0,3:0,4:0,5:0}})" class="text-xs font-semibold text-[#152A4E] dark:text-blue-300 hover:underline">+ {{ __('Add module') }}</button>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">{{ __('Module') }}</th>
                                    <th class="px-2 py-2 text-center font-medium">1</th>
                                    <th class="px-2 py-2 text-center font-medium">2</th>
                                    <th class="px-2 py-2 text-center font-medium">3</th>
                                    <th class="px-2 py-2 text-center font-medium">4</th>
                                    <th class="px-2 py-2 text-center font-medium">5</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, index) in modules" :key="index">
                                    <tr class="border-t border-gray-100 dark:border-gray-700">
                                        <td class="px-2 py-1"><input type="text" x-model="row.module" :name="`l1_modules[${index}][module]`" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                        <template x-for="n in [1,2,3,4,5]" :key="n">
                                            <td class="px-1 py-1"><input type="number" min="0" x-model.number="row.distribution[n]" :name="`l1_modules[${index}][distribution][${n}]`" class="w-14 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs text-center" /></td>
                                        </template>
                                        <td class="px-2 py-1 text-right"><button type="button" @click="modules.splice(index,1)" class="text-red-500 hover:text-red-700 text-xs">✕</button></td>
                                    </tr>
                                </template>
                                <tr x-show="modules.length === 0"><td colspan="7" class="px-3 py-3 text-center text-xs text-gray-400">{{ __('No modules yet — add one, or link a training and recompute.') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-gray-400">{{ __('Legend: 1 - Poor, 2 - Unsatisfactory, 3 - Satisfactory, 4 - Very Satisfactory, 5 - Outstanding. Averages recalculate automatically when you save.') }}</p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('L1 Analysis') }}</label>
                        <textarea name="l1_analysis" rows="4" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">{{ old('l1_analysis', $report->l1_analysis) }}</textarea>
                    </div>
                </div>

                {{-- L2 Learning Evaluation: a fixed pretest/posttest x mean/median/mode/
                     min/max/count grid — plain named inputs, no Alpine, since there's
                     always exactly these two columns and six rows (no add/remove needed). --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-4">
                    <h2 class="text-lg font-semibold text-[#152A4E] dark:text-white">{{ __('Annex: Level 2 Learning Evaluation') }}</h2>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium"></th>
                                    <th class="px-3 py-2 text-center font-medium">{{ __('Pre-Test') }}</th>
                                    <th class="px-3 py-2 text-center font-medium">{{ __('Post-Test') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach (['mean' => 'Mean', 'median' => 'Median', 'mode' => 'Mode', 'min' => 'Lowest Score', 'max' => 'Highest Score', 'count' => 'Number of Takers'] as $key => $label)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ __($label) }}</td>
                                        <td class="px-2 py-1"><input type="number" step="0.01" name="l2_stats[pretest][{{ $key }}]" value="{{ old("l2_stats.pretest.$key", $l2['pretest'][$key] ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs text-center" /></td>
                                        <td class="px-2 py-1"><input type="number" step="0.01" name="l2_stats[posttest][{{ $key }}]" value="{{ old("l2_stats.posttest.$key", $l2['posttest'][$key] ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs text-center" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('L2 Analysis') }}</label>
                        <textarea name="l2_analysis" rows="4" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">{{ old('l2_analysis', $report->l2_analysis) }}</textarea>
                    </div>
                </div>

                {{-- Signatories: printed on the main report AND reprinted below every annex
                     in the PDF (see pdf/atar-report.blade.php's $signatureBlock closure) —
                     real ATARs get re-signed per section, not just once at the end. --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-[#152A4E] dark:text-white">{{ __('Signatories') }}</h2>
                        <button type="button" @click="signatories.push({role:'',name:'',title:''})" class="text-xs font-semibold text-[#152A4E] dark:text-blue-300 hover:underline">+ {{ __('Add signatory') }}</button>
                    </div>
                    <p class="text-xs text-gray-400">{{ __('ATARs are wet-ink signed — this only prints the name/title lines. Print the finished PDF for physical signatures.') }}</p>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">{{ __('Role (e.g. Prepared by)') }}</th>
                                    <th class="px-3 py-2 text-left font-medium">{{ __('Name') }}</th>
                                    <th class="px-3 py-2 text-left font-medium">{{ __('Title / Position') }}</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, index) in signatories" :key="index">
                                    <tr class="border-t border-gray-100 dark:border-gray-700">
                                        <td class="px-2 py-1"><input type="text" x-model="row.role" :name="`signatories[${index}][role]`" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                        <td class="px-2 py-1"><input type="text" x-model="row.name" :name="`signatories[${index}][name]`" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                        <td class="px-2 py-1"><input type="text" x-model="row.title" :name="`signatories[${index}][title]`" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" /></td>
                                        <td class="px-2 py-1 text-right"><button type="button" @click="signatories.splice(index,1)" class="text-red-500 hover:text-red-700 text-xs">✕</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.atar-reports.index') }}" class="inline-flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        {{ __('Back') }}
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                        {{ __('Save ATAR') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{--
        Alpine component backing every repeatable table above. It just holds
        the five arrays as reactive state — x-for in the tables re-renders
        rows (and their indexed `name` attributes) whenever a row is pushed
        or spliced. Defined here, at the bottom of the page, rather than in a
        bundled JS file since nothing else on the site needs it; Alpine
        doesn't evaluate the x-data="atarEditor(...)" call at the top of the
        page until after the whole document (including this script) has
        parsed, so the function is safely defined before it's needed.
    --}}
    <script>
        function atarEditor(seed) {
            return {
                graduates: seed.graduates,
                dropouts: seed.dropouts,
                lecturers: seed.lecturers,
                signatories: seed.signatories,
                modules: seed.modules,
            };
        }
    </script>
</x-app-layout>
