@if ($participants->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ $participantSearch !== '' ? __('No participants match your search.') : __('No participant accounts yet.') }}
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <th class="py-2 pr-4">{{ __('Name') }}</th>
                    <th class="py-2 pr-4">{{ __('Email') }}</th>
                    <th class="py-2 pr-4">{{ __('Organization') }}</th>
                    <th class="py-2 pr-4">{{ __('Make Admin For') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($participants as $participant)
                    <tr>
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
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5 participants-pagination">
        {{ $participants->links() }}
    </div>
@endif
