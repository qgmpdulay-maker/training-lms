<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Summary') }}
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

            @if (Auth::user()->isSuperAdmin())
                <div class="flex items-center flex-wrap gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm px-5 py-3">
                    <span class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ __('Region') }}</span>
                    <form method="GET" action="{{ route('admin.summary') }}" class="flex items-center gap-2">
                        @if ($search !== '')
                            <input type="hidden" name="q" value="{{ $search }}">
                        @endif
                        @if (! $statusDefaulted)
                            <input type="hidden" name="status" value="{{ $selectedStatus }}">
                        @endif
                        @if ($participantSearch !== '')
                            <input type="hidden" name="participants_q" value="{{ $participantSearch }}">
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
                    @if ($selectedRegion)
                        <a href="{{ route('admin.summary', array_filter(['q' => $search ?: null, 'status' => ! $statusDefaulted ? $selectedStatus : null, 'participants_q' => $participantSearch ?: null, 'instructors_q' => $instructorSearch ?: null])) }}"
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Reset to all regions') }}
                        </a>
                    @endif
                </div>
            @endif

            <div id="training-requests" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                    <div>
                        <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Training Requests') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @php
                                $regionPhrase = Auth::user()->isAdmin()
                                    ? __('for :region', ['region' => Auth::user()->region])
                                    : ($selectedRegion ? __('for :region', ['region' => $selectedRegion]) : __('across all regions'));
                            @endphp
                            @if ($statusDefaulted)
                                {{ __('Newly received training requests :region, most recent first, so nothing gets missed. Use the status filter to review requests that are being reviewed, approved, completed, or not approved. Click Manage to update status, certificate details, or move date and venue.', ['region' => $regionPhrase]) }}
                            @elseif ($selectedStatus === 'all')
                                {{ __('Every training request on record :region, most recent first. Click Manage to update its status, certificate details, or move its date and venue.', ['region' => $regionPhrase]) }}
                            @else
                                {{ __('Training requests marked ":status" :region, most recent first. Click Manage to update status, certificate details, or move date and venue.', ['status' => $statusLabels[$selectedStatus] ?? $selectedStatus, 'region' => $regionPhrase]) }}
                            @endif
                        </p>
                    </div>
                    <form method="GET" action="{{ route('admin.summary') }}#training-requests" class="flex items-center flex-wrap gap-2">
                        @if ($participantSearch !== '')
                            <input type="hidden" name="participants_q" value="{{ $participantSearch }}">
                        @endif
                        @if ($instructorSearch !== '')
                            <input type="hidden" name="instructors_q" value="{{ $instructorSearch }}">
                        @endif
                        @if ($selectedRegion)
                            <input type="hidden" name="region" value="{{ $selectedRegion }}">
                        @endif
                        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('Search training, agency, participant, venue, or date…') }}"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-64">
                        <select name="status" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="all" @selected($selectedStatus === 'all')>{{ __('All statuses') }}</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                        @if ($search !== '' || ! $statusDefaulted)
                            <a href="{{ route('admin.summary', array_filter(['participants_q' => $participantSearch ?: null, 'instructors_q' => $instructorSearch ?: null, 'region' => $selectedRegion ?: null])) }}#training-requests"
                                class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </form>
                </div>

                @if ($records->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No training requests match this filter or search.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Training') }}</th>
                                    <th class="py-2 pr-4">{{ __('Requesting Agency') }}</th>
                                    <th class="py-2 pr-4">{{ __('Participants') }}</th>
                                    <th class="py-2 pr-4">{{ __('Date & Venue') }}</th>
                                    <th class="py-2 pr-4">{{ __('Status') }}</th>
                                    <th class="py-2 pr-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($records as $record)
                                    @php $recordParticipants = $record->effectiveParticipants(); @endphp
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $record->training_title }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                            {{ $record->requesting_agency }}
                                            <div class="text-xs text-gray-400">{{ $record->contact_person }} &middot; {{ $record->contact_number }}</div>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                            @if ($recordParticipants->isEmpty())
                                                <span class="text-gray-400">{{ __('None on file') }}</span>
                                            @else
                                                {{ trans_choice(':count participant|:count participants', $recordParticipants->count(), ['count' => $recordParticipants->count()]) }}
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                            {{ $record->preferred_date->format('M j, Y') }}
                                            <div class="text-xs text-gray-400">{{ $record->venue }}</div>
                                        </td>
                                        <td class="py-3 pr-4">
                                            <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1 {{ $statusColors[$record->status] ?? '' }}">
                                                {{ $record->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="py-3 pr-4 text-right">
                                            <a href="{{ route('admin.summary.edit', $record) }}"
                                                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                                {{ __('Manage') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5">
                        {{ $records->links() }}
                    </div>
                @endif
            </div>

            @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                <div id="registered-participants" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                        <div>
                            <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Registered Participants') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @if (Auth::user()->isAdmin())
                                    {{ __('Participants registered under :region.', ['region' => Auth::user()->region]) }}
                                @elseif ($selectedRegion)
                                    {{ __('Participants registered under :region.', ['region' => $selectedRegion]) }}
                                @else
                                    {{ __('Participants registered across all regions.') }}
                                @endif
                            </p>
                        </div>
                        <form method="GET" action="{{ route('admin.summary') }}#registered-participants" class="flex items-center flex-wrap gap-2">
                            @if ($search !== '')
                                <input type="hidden" name="q" value="{{ $search }}">
                            @endif
                            @if (! $statusDefaulted)
                                <input type="hidden" name="status" value="{{ $selectedStatus }}">
                            @endif
                            @if ($selectedRegion)
                                <input type="hidden" name="region" value="{{ $selectedRegion }}">
                            @endif
                            @if ($instructorSearch !== '')
                                <input type="hidden" name="instructors_q" value="{{ $instructorSearch }}">
                            @endif
                            <input type="text" name="participants_q" value="{{ $participantSearch }}" placeholder="{{ __('Search name, type, agency, email, or contact no.…') }}"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-60">
                            <button type="submit"
                                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                {{ __('Search') }}
                            </button>
                            @if ($participantSearch !== '')
                                <a href="{{ route('admin.summary', array_filter(['q' => $search ?: null, 'status' => ! $statusDefaulted ? $selectedStatus : null, 'region' => $selectedRegion ?: null, 'instructors_q' => $instructorSearch ?: null])) }}#registered-participants"
                                    class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </form>
                    </div>

                    @if ($participants->isEmpty())
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                            {{ $participantSearch !== '' ? __('No participants match your search.') : __('No participants registered yet.') }}
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                        <th class="py-2 pr-4"></th>
                                        <th class="py-2 pr-4">{{ __('Name') }}</th>
                                        <th class="py-2 pr-4">{{ __('Age / Sex') }}</th>
                                        <th class="py-2 pr-4">{{ __('Participant Type') }}</th>
                                        <th class="py-2 pr-4">{{ __('Agency / Organization') }}</th>
                                        <th class="py-2 pr-4">{{ __('Email') }}</th>
                                        <th class="py-2 pr-4">{{ __('Contact Number') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($participants as $participant)
                                        <tr>
                                            <td class="py-3 pr-4">
                                                @if ($participant->picture)
                                                    <img src="{{ asset('storage/'.$participant->picture) }}" alt="{{ $participant->name }}" class="w-9 h-9 object-cover rounded-full border border-gray-200 dark:border-gray-600">
                                                @else
                                                    <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600"></div>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $participant->name }}</td>
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->age }} / {{ $participant->sex }}</td>
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->participant_type }}</td>
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->organization ?: $participant->agency }}</td>
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->email }}</td>
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->mobile_number }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-5">
                            {{ $participants->links() }}
                        </div>
                    @endif
                </div>
            @endif

            @if (Auth::user()->isSuperAdmin())
                <div id="instructors" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                        <div>
                            <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Instructors') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @if ($selectedRegion)
                                    {{ __('Instructors on file for :region. Click a name for their full profile, deployment history, and complaints on record.', ['region' => $selectedRegion]) }}
                                @else
                                    {{ __('Instructors on file across all regions. Click a name for their full profile, deployment history, and complaints on record.') }}
                                @endif
                            </p>
                        </div>
                        <form method="GET" action="{{ route('admin.summary') }}#instructors" class="flex items-center flex-wrap gap-2">
                            @if ($search !== '')
                                <input type="hidden" name="q" value="{{ $search }}">
                            @endif
                            @if (! $statusDefaulted)
                                <input type="hidden" name="status" value="{{ $selectedStatus }}">
                            @endif
                            @if ($selectedRegion)
                                <input type="hidden" name="region" value="{{ $selectedRegion }}">
                            @endif
                            @if ($participantSearch !== '')
                                <input type="hidden" name="participants_q" value="{{ $participantSearch }}">
                            @endif
                            <input type="text" name="instructors_q" value="{{ $instructorSearch }}" placeholder="{{ __('Search name, training type, agency, or certificate code…') }}"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-64">
                            <button type="submit"
                                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                {{ __('Search') }}
                            </button>
                            @if ($instructorSearch !== '')
                                <a href="{{ route('admin.summary', array_filter(['q' => $search ?: null, 'status' => ! $statusDefaulted ? $selectedStatus : null, 'region' => $selectedRegion ?: null, 'participants_q' => $participantSearch ?: null])) }}#instructors"
                                    class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </form>
                    </div>

                    @if ($instructors->isEmpty())
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                            {{ $instructorSearch !== '' ? __('No instructors match your search.') : __('No instructors on file yet.') }}
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                        <th class="py-2 pr-4">{{ __('Name') }}</th>
                                        <th class="py-2 pr-4">{{ __('Type of Training') }}</th>
                                        <th class="py-2 pr-4">{{ __('Region') }}</th>
                                        <th class="py-2 pr-4">{{ __('Agency / LGU') }}</th>
                                        <th class="py-2 pr-4">{{ __('Rating') }}</th>
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
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->region ?: __('Central / Unassigned') }}</td>
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->agency_organization ?: $instructor->lgu ?: '—' }}</td>
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->rating ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-5">
                            {{ $instructors->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
