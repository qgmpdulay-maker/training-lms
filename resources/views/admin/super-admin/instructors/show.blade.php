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
                    <div class="shrink-0 flex flex-col items-center gap-2">
                        <x-instructor-avatar :instructor="$instructor" class="w-24 h-24 text-2xl" />

                        @php $photoInputId = 'instructor-photo-'.$instructor->id; @endphp
                        <form method="POST" action="{{ route('admin.instructors.photo', $instructor) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="photo" id="{{ $photoInputId }}" accept="image/*" class="hidden" onchange="this.form.submit()">
                            <label for="{{ $photoInputId }}"
                                class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400 border border-dashed border-gray-300 dark:border-gray-600 rounded-full pl-2 pr-2.5 py-1 hover:text-[#152A4E] dark:hover:text-white hover:border-gray-400 dark:hover:border-gray-500 cursor-pointer transition">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                {{ $instructor->photo_path ? __('Replace photo') : __('Upload photo') }}
                            </label>
                        </form>
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
                        {{-- Certifications are a section of their own further down —
                             an instructor can hold several, so a single line here
                             could only ever have shown one of them. --}}
                        <div class="sm:col-span-2">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Organization / LGU / Corporation') }}</div>
                            <div class="text-sm text-gray-700 dark:text-gray-200">{{ $instructor->agency_organization ?? $instructor->lgu ?? '—' }}</div>
                        </div>
                        {{-- Editable in place, Super Admin only: instructors get posted to
                             another regional office, or sent to run a training outside
                             their own, and the roster is grouped and filtered by region
                             everywhere. A Regional Admin maintains their own region's
                             roster but can't post anyone out of it, so they see the value. --}}
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Region') }}</div>
                            @if (Auth::user()->isSuperAdmin())
                                <form method="POST" action="{{ route('admin.instructors.region', $instructor) }}"
                                    x-data="{ region: @js($instructor->region) }" class="mt-1 flex flex-wrap items-center gap-2"
                                    @submit="if (! confirm('Move ' + @js($instructor->name) + ' to ' + region + '?')) $event.preventDefault()">
                                    @csrf
                                    @method('PATCH')
                                    <select name="region" x-model="region"
                                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                        @if (! $instructor->region)
                                            <option value="" disabled selected>{{ __('Central / Unassigned') }}</option>
                                        @endif
                                        @foreach ($regions as $regionOption)
                                            <option value="{{ $regionOption }}" @selected($instructor->region === $regionOption)>{{ $regionOption }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" x-show="region !== @js($instructor->region)" x-cloak
                                        class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-3 py-1.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                        {{ __('Move') }}
                                    </button>
                                </form>
                            @else
                                <div class="text-sm text-gray-700 dark:text-gray-200">{{ $instructor->region ?? __('Central / Unassigned') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Types of Training -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Types of Training') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Every course this instructor can teach. Ratings are per course, rolled up from L1 evaluation data.') }}</p>

                <x-input-error :messages="$errors->get('training_type')" class="mb-3" />

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                <th class="py-2 pr-4">{{ __('Training Title') }}</th>
                                <th class="py-2 pr-4">{{ __('Certificate Code') }}</th>
                                <th class="py-2 pr-4">{{ __('Rating') }}</th>
                                <th class="py-2 pr-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($records as $record)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $record->training_type }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->certificate_code ?? '—' }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->rating ?? '—' }}</td>
                                    <td class="py-3 pr-4 text-right">
                                        {{-- The last course can't be removed: an instructor with none
                                             drops off every roster filter (controller enforces it too). --}}
                                        @if ($records->count() > 1)
                                            <form method="POST" action="{{ route('admin.instructors.training-types.destroy', $record) }}" class="inline"
                                                onsubmit="return confirm('{{ __('Remove this training type?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('admin.instructors.training-types.store', $instructor) }}"
                    class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[14rem]">
                        <x-input-label :value="__('Add a type of training')" class="text-xs" />
                        <input type="text" name="training_type" required placeholder="{{ __('e.g. Incident Command System (ICS)') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div class="w-48">
                        <x-input-label :value="__('Certificate Code')" class="text-xs" />
                        <input type="text" name="certificate_code"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <button type="submit"
                        class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2.5 hover:bg-[#1E3A66] transition">
                        {{ __('Add') }}
                    </button>
                </form>
            </div>

            <!-- Certifications -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Certifications') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Each certification keeps its own scanned copy — adding one never replaces another.') }}</p>

                @if ($instructor->certifications->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No certifications on file.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Certification') }}</th>
                                    <th class="py-2 pr-4">{{ __('Code') }}</th>
                                    <th class="py-2 pr-4">{{ __('Issued') }}</th>
                                    <th class="py-2 pr-4">{{ __('Scan') }}</th>
                                    <th class="py-2 pr-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($instructor->certifications as $certification)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $certification->title }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $certification->code ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $certification->issued_on?->format('M j, Y') ?? '—' }}</td>
                                        <td class="py-3 pr-4">
                                            @if ($certification->file_path)
                                                <a href="{{ Storage::url($certification->file_path) }}" target="_blank"
                                                    class="text-xs font-semibold text-[#152A4E] dark:text-blue-300 hover:underline">{{ __('View') }}</a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 text-right">
                                            <form method="POST" action="{{ route('admin.instructors.certifications.destroy', $certification) }}" class="inline"
                                                onsubmit="return confirm('{{ __('Remove this certification? Its scanned copy is deleted too.') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.instructors.certifications.store', $instructor) }}" enctype="multipart/form-data"
                    class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[14rem]">
                        <x-input-label :value="__('Add a certification')" class="text-xs" />
                        <input type="text" name="title" required placeholder="{{ __('e.g. Accredited ICS Instructor') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div class="w-40">
                        <x-input-label :value="__('Code')" class="text-xs" />
                        <input type="text" name="code"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div class="w-44">
                        <x-input-label :value="__('Issued')" class="text-xs" />
                        <input type="date" name="issued_on"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div class="flex-1 min-w-[14rem]">
                        <x-input-label :value="__('Scanned copy')" class="text-xs" />
                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png"
                            class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#152A4E]/8 dark:file:bg-white/10 file:text-[#152A4E] dark:file:text-white hover:file:bg-[#152A4E]/15">
                    </div>
                    <button type="submit"
                        class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2.5 hover:bg-[#1E3A66] transition">
                        {{ __('Add') }}
                    </button>
                </form>
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

                @if ($instructor->deployments->isEmpty())
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
                                    <th class="py-2 pr-4">{{ __('City') }}</th>
                                    <th class="py-2 pr-4">{{ __('Area') }}</th>
                                    <th class="py-2 pr-4">{{ __('Role') }}</th>
                                    <th class="py-2 pr-4"></th>
                                </tr>
                            </thead>
                            {{-- One tbody per record, so the row and the edit row
                                 below it share an Alpine scope. x-data on the first
                                 <tr> wouldn't reach its sibling, and a wrapping
                                 <div> isn't valid inside a table. --}}
                            @foreach ($instructor->deployments as $record)
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700" x-data="{ editing: false }">
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $record->deployment }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->deployment_date?->format('M j, Y') ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->city ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->area ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->deployment_role ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-right whitespace-nowrap">
                                            <button type="button" @click="editing = !editing" class="text-xs font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D] mr-3">
                                                {{ __('Edit') }}
                                            </button>
                                            <form method="POST" action="{{ route('admin.instructors.deployments.destroy', $record) }}" class="inline"
                                                onsubmit="return confirm('{{ __('Remove this deployment record?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr x-show="editing" x-cloak>
                                        <td colspan="6" class="py-3 pl-0 pr-4 bg-gray-50/60 dark:bg-gray-900/20">
                                            <form method="POST" action="{{ route('admin.instructors.deployments.update', $record) }}" class="flex flex-wrap items-end gap-3">
                                                @csrf
                                                @method('PATCH')
                                                <div>
                                                    <x-input-label :value="__('Deployment')" class="text-xs" />
                                                    <input type="text" name="deployment" required value="{{ $record->deployment }}"
                                                        class="mt-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Date')" class="text-xs" />
                                                    <input type="date" name="deployment_date" value="{{ $record->deployment_date?->format('Y-m-d') }}"
                                                        class="mt-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('City')" class="text-xs" />
                                                    <input type="text" name="city" value="{{ $record->city }}" list="ph-city-options" autocomplete="off"
                                                        class="mt-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Area (Agency / LGU)')" class="text-xs" />
                                                    <input type="text" name="area" value="{{ $record->area }}"
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
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.instructors.deployments.store', $instructor) }}"
                    class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[12rem]">
                        <x-input-label :value="__('Record a deployment')" class="text-xs" />
                        <input type="text" name="deployment" required placeholder="{{ __('e.g. RDANA, PDNA, EOC') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div class="w-44">
                        <x-input-label :value="__('Date')" class="text-xs" />
                        <input type="date" name="deployment_date"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div class="flex-1 min-w-[10rem]">
                        <x-input-label :value="__('City')" class="text-xs" />
                        <input type="text" name="city" list="ph-city-options" autocomplete="off" placeholder="{{ __('Where they were sent') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div class="flex-1 min-w-[10rem]">
                        <x-input-label :value="__('Area (Agency / LGU)')" class="text-xs" />
                        <input type="text" name="area"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div class="flex-1 min-w-[10rem]">
                        <x-input-label :value="__('Role')" class="text-xs" />
                        <input type="text" name="deployment_role" placeholder="{{ __('e.g. Team Leader') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <button type="submit"
                        class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2.5 hover:bg-[#1E3A66] transition">
                        {{ __('Add') }}
                    </button>
                </form>
            </div>

            @include('admin.partials.city-options')

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
