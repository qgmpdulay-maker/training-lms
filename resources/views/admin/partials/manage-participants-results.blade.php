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
                        <th class="py-2 pr-4">{{ __('Make Admin For') }}</th>
                        <th class="py-2 pr-4"></th>
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
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->organization }}</td>
                            <td class="py-3 pr-4">
                                <form method="POST" action="{{ route('admin.users.promote', $participant) }}" class="flex items-center gap-2" x-data
                                    @submit="if (! confirm('Make ' + @js($participant->name) + ' a Regional Admin?')) $event.preventDefault()">
                                    @csrf
                                    <select name="region" required
                                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                        <option value="" disabled selected>{{ __('Select region') }}</option>
                                        @foreach ($regions as $region)
                                            <option value="{{ $region }}">{{ $region }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit"
                                        class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-3 py-1.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                        {{ __('Make Admin') }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 pr-4 text-right whitespace-nowrap">
                                <x-reset-password-button :account="$participant" />
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
