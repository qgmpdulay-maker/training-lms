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

            @if (Auth::user()->isAdmin())
                <div class="flex items-start gap-3 text-sm text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ __("Participants and training requests aren't linked to a region yet, so this shows records for all regions rather than just :region. Filtering by region needs a region field on participants or trainings.", ['region' => Auth::user()->region]) }}</span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                    <div>
                        <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Training Requests') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Every participant training request on record, most recent first. Certificate details are editable per record.') }}</p>
                    </div>
                    <form method="GET" class="flex items-center gap-2">
                        <select name="status" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @if ($records->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No training requests match this filter.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Training') }}</th>
                                    <th class="py-2 pr-4">{{ __('Participant') }}</th>
                                    <th class="py-2 pr-4">{{ __('Age / Sex') }}</th>
                                    <th class="py-2 pr-4">{{ __('Contact') }}</th>
                                    <th class="py-2 pr-4">{{ __('Participant Type') }}</th>
                                    <th class="py-2 pr-4">{{ __('Agency / Organization') }}</th>
                                    <th class="py-2 pr-4">{{ __('Date') }}</th>
                                    <th class="py-2 pr-4">{{ __('Status') }}</th>
                                    <th class="py-2 pr-4 min-w-[280px]">{{ __('Certificate') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($records as $record)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $record->training_title }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                            <div class="flex items-center gap-2">
                                                @if ($record->user->picture)
                                                    <img src="{{ asset('storage/' . $record->user->picture) }}" alt="{{ $record->user->name }}"
                                                        class="h-8 w-8 rounded-full object-cover shrink-0">
                                                @else
                                                    <div class="h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-[10px] font-semibold text-gray-400 shrink-0">
                                                        {{ strtoupper(substr($record->user->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                                <div>
                                                    {{ $record->user->name }}
                                                    <div class="text-xs text-gray-400">{{ $record->user->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->user->age }} / {{ $record->user->sex }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->user->mobile_number }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->user->participant_type }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->user->organization }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $record->preferred_date->format('M j, Y') }}</td>
                                        <td class="py-3 pr-4">
                                            <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600">
                                                {{ $record->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="py-3 pr-4">
                                            <form method="POST" action="{{ route('admin.summary.update', $record) }}" class="flex flex-col gap-1.5 min-w-[260px]">
                                                @csrf
                                                @method('PATCH')
                                                <div class="flex gap-1.5">
                                                    <input type="text" name="lgu" value="{{ old('lgu', $record->lgu) }}" placeholder="{{ __('LGU') }}"
                                                        class="w-1/2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                    <input type="text" name="certificate_code" value="{{ old('certificate_code', $record->certificate_code) }}" placeholder="{{ __('Certificate code') }}"
                                                        class="w-1/2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                </div>
                                                <div class="flex gap-1.5">
                                                    <select name="certificate_remarks"
                                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                        <option value="">{{ __('No remarks') }}</option>
                                                        @foreach ($certificateRemarksLabels as $value => $label)
                                                            <option value="{{ $value }}" @selected(old('certificate_remarks', $record->certificate_remarks) === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit"
                                                        class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-3 py-1.5 hover:bg-[#1E3A66] transition">
                                                        {{ __('Save') }}
                                                    </button>
                                                </div>
                                            </form>
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

        </div>
    </div>
</x-app-layout>
