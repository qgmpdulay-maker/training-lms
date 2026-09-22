{{--
    The candidate table. Rendered inline on first load and swapped in on its own
    by the live-search script as the admin types, so the page never reloads.

    Everything lives inside one form: tick the people who belong to this body,
    then save once. Alpine is re-initialized on the swapped-in markup by the
    live-search script, so `selected` starts empty again after each search —
    which is the behaviour you want, since the rows themselves changed.
--}}
<form method="POST" action="{{ route('admin.organizations.members.add', $organization) }}"
    x-data="{ selected: [] }">
    @csrf

    <div x-show="selected.length > 0" x-cloak
        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 bg-[#152A4E]/5 dark:bg-white/5 border border-[#152A4E]/15 dark:border-white/15 rounded-lg px-4 py-3">
        <span class="text-xs font-semibold text-[#152A4E] dark:text-white">
            <span x-text="selected.length"></span> {{ __('selected') }}
        </span>
        <div class="flex items-center gap-2">
            {{-- One position for the batch: add people with different positions separately. --}}
            <input type="text" name="position" placeholder="{{ __('Position for these (optional)') }}"
                class="w-48 sm:w-56 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:border-[#152A4E] focus:ring-[#152A4E]">
            <button type="submit"
                class="shrink-0 inline-flex items-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                {{ __('Add Selected') }}
            </button>
        </div>
    </div>

    @if ($candidates->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
            @if ($candidateSearch !== '')
                {{ $organization->region
                    ? __('Nobody in :region matches that search, among people not already in an organization.', ['region' => $organization->region])
                    : __('No unassigned users match that search.') }}
            @else
                {{ $organization->region
                    ? __('Everyone in :region already belongs to an organization.', ['region' => $organization->region])
                    : __('Everyone already belongs to an organization.') }}
            @endif
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-2 pr-4 w-8">
                            <input type="checkbox"
                                @change="selected = $event.target.checked ? @js($candidates->pluck('id')->values()) : []"
                                :checked="selected.length === {{ $candidates->count() }}"
                                class="rounded border-gray-300 dark:border-gray-600 text-[#152A4E] focus:ring-[#152A4E]">
                        </th>
                        <th class="py-2 pr-4">{{ __('Name') }}</th>
                        <th class="py-2 pr-4">{{ __('Email') }}</th>
                        <th class="py-2 pr-4">{{ __('Typed at Signup') }}</th>
                        <th class="py-2">{{ __('City') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($candidates as $candidate)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                            <td class="py-3 pr-4">
                                <input type="checkbox" name="user_ids[]" value="{{ $candidate->id }}" x-model="selected"
                                    id="candidate-{{ $candidate->id }}"
                                    class="rounded border-gray-300 dark:border-gray-600 text-[#152A4E] focus:ring-[#152A4E]">
                            </td>
                            <td class="py-3 pr-4">
                                <label for="candidate-{{ $candidate->id }}" class="font-medium text-gray-800 dark:text-gray-100 cursor-pointer">
                                    {{ $candidate->name }}
                                </label>
                            </td>
                            <td class="py-3 pr-4 text-gray-500 dark:text-gray-400">{{ $candidate->email }}</td>
                            <td class="py-3 pr-4">
                                {{-- Unverified: what they typed about themselves at signup, shown
                                     so the admin can sanity-check the match rather than go on name. --}}
                                <span class="text-xs text-gray-400 italic">{{ $candidate->organization ?: '—' }}</span>
                            </td>
                            <td class="py-3 text-gray-500 dark:text-gray-400">{{ $candidate->city ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-5 candidates-pagination">
            {{ $candidates->links() }}
        </div>
    @endif
</form>
