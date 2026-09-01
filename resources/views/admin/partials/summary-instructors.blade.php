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

    <div class="mt-5 instructors-pagination">
        {{ $instructors->links() }}
    </div>
@endif
