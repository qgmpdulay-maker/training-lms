{{-- The Regional Admin's roster. Courses, certifications, and deployments are
     many per instructor now, so the row summarises each and the name links to
     the profile, where they're managed. The certificate preview modal that
     used to live in this cell moved there too — one instructor can hold
     several certifications, and a modal per row can only ever show one. --}}
@if ($instructors->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ __('No instructors match your search.') }}
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <th class="py-2 pr-4">{{ __('Name') }}</th>
                    <th class="py-2 pr-4">{{ __('Types of Training') }}</th>
                    <th class="py-2 pr-4">{{ __('Certifications') }}</th>
                    <th class="py-2 pr-4">{{ __('Latest Deployment') }}</th>
                    <th class="py-2 pr-4">{{ __('Agency / LGU') }}</th>
                    <th class="py-2 pr-4">{{ __('Rating') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($instructors as $instructor)
                    @php $latest = $instructor->deployments->first(); @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="py-3 pr-4 font-medium">
                            <a href="{{ route('admin.instructors.show', $instructor) }}"
                                class="flex items-center gap-2.5 text-[#152A4E] dark:text-white hover:text-[#E2762D] dark:hover:text-[#E2762D] hover:underline">
                                <x-instructor-avatar :instructor="$instructor" />
                                {{ $instructor->name }}
                            </a>
                        </td>
                        <td class="py-3 pr-4">
                            @forelse ($instructor->trainingTypes as $type)
                                <span class="inline-flex items-center text-xs font-medium rounded-full border px-2 py-0.5 mr-1 mb-1 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600">
                                    {{ $type->training_type }}
                                </span>
                            @empty
                                <span class="text-gray-400">—</span>
                            @endforelse
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                            @if ($instructor->certifications->isEmpty())
                                <span class="text-gray-400">—</span>
                            @else
                                <a href="{{ route('admin.instructors.show', $instructor) }}" class="text-xs font-semibold text-[#152A4E] dark:text-blue-300 hover:underline">
                                    {{ trans_choice(':count certification|:count certifications', $instructor->certifications->count(), ['count' => $instructor->certifications->count()]) }}
                                </a>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                            @if ($latest)
                                {{ $latest->deployment }}
                                @if ($instructor->deployments->count() > 1)
                                    <span class="text-xs text-gray-400">{{ __('+:count more', ['count' => $instructor->deployments->count() - 1]) }}</span>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->agency_organization ?? $instructor->lgu ?? '—' }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->rating ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5 instructor-roster-pagination">
        {{ $instructors->links() }}
    </div>
@endif
