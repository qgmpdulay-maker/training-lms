@if ($participants->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ $participantSearch !== '' ? __('No participants match your search.') : __('No participant accounts yet.') }}
    </div>
@else
    <div x-data="{ selected: [] }">
        <div x-show="selected.length > 0" x-cloak
            class="flex items-center justify-between gap-3 mb-3 bg-[#152A4E]/5 dark:bg-white/5 border border-[#152A4E]/15 dark:border-white/15 rounded-lg px-4 py-2.5">
            <span class="text-xs font-semibold text-[#152A4E] dark:text-white">
                <span x-text="selected.length"></span> {{ __('selected') }}
            </span>
            <form method="POST" action="{{ route('admin.users.reset-passwords') }}"
                @submit="if (! confirm('Reset the password for ' + selected.length + ' selected account(s)? Each gets its own new random password.')) $event.preventDefault()">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="user_ids[]" :value="id">
                </template>
                <button type="submit"
                    class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-3 py-1.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                    {{ __('Reset Passwords for Selected') }}
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                        <th class="py-2 pr-4 w-8">
                            <input type="checkbox"
                                @change="selected = $event.target.checked ? @js($participants->pluck('id')->values()) : []"
                                :checked="selected.length === {{ $participants->count() }}"
                                class="rounded border-gray-300 dark:border-gray-600 text-[#152A4E] focus:ring-[#152A4E]">
                        </th>
                        <th class="py-2 pr-4">{{ __('Name') }}</th>
                        <th class="py-2 pr-4">{{ __('Email') }}</th>
                        <th class="py-2 pr-4">{{ __('Organization') }}</th>
                        <th class="py-2 pr-4">{{ __('Assign To') }}</th>
                        <th class="py-2 pr-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($participants as $participant)
                        <tr>
                            <td class="py-3 pr-4">
                                <input type="checkbox" value="{{ $participant->id }}" x-model="selected"
                                    class="rounded border-gray-300 dark:border-gray-600 text-[#152A4E] focus:ring-[#152A4E]">
                            </td>
                            <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $participant->name }}</td>
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->email }}</td>
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                @if ($participant->assignedOrganization)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#152A4E] dark:text-white">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $participant->assignedOrganization->name }}
                                    </span>
                                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $participant->position ?: __('No position set') }}</p>
                                @else
                                    {{-- Unverified: what they typed about themselves at signup. --}}
                                    <span class="text-xs text-gray-400 italic">
                                        {{ $participant->organization ?: __('Not assigned') }}
                                    </span>
                                    <p class="text-[11px] text-gray-400 mt-0.5">{{ __('self-declared, unconfirmed') }}</p>
                                @endif
                            </td>
                            <td class="py-3 pr-4">
                                <form method="POST" action="{{ route('admin.users.assign-organization', $participant) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="organization_id"
                                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                        <option value="">{{ __('Unassigned') }}</option>
                                        @foreach ($organizations as $organization)
                                            {{-- Pre-selected from the freetext hint where one matched; still has to be confirmed. --}}
                                            <option value="{{ $organization->id }}"
                                                @selected($participant->organization_id === $organization->id || (($suggestedOrganizations[$participant->id] ?? null) === $organization->id))>
                                                {{ $organization->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="position" value="{{ $participant->position }}" placeholder="{{ __('Position') }}"
                                        class="w-28 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                    <button type="submit"
                                        class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-3 py-1.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                        {{ __('Save') }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 pr-4 text-right whitespace-nowrap">
                                {{-- Both open a modal naming the account first: these are the
                                     two consequential actions in the row, and neither should
                                     fire from one stray click on the wrong line. --}}
                                <div class="inline-flex items-center gap-2">
                                    <x-make-admin-button :account="$participant" :regions="$regions" />
                                    <x-reset-password-button :account="$participant" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-5 participants-pagination">
            {{ $participants->links() }}
        </div>
    </div>
@endif
