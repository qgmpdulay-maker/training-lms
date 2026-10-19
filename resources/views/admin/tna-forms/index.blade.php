{{--
    The Training Needs Assessments themselves — one per participant type, plus
    the fallback for types that have none. Super Admin only; reached from the
    Needs Assessment page rather than its own sidebar entry.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Training Needs Assessment') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admin.training-needs-assessment') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to submissions') }}
            </a>

            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white">{{ __('Assessment Forms') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('The questions each participant type is asked, and the scoring their answers carry.') }}</p>
                </div>
                <a href="{{ route('admin.tna-forms.create') }}"
                    class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition shrink-0">
                    {{ __('New Assessment') }}
                </a>
            </div>

            <x-chart-card body-class="p-0" :title="__('All Assessments')">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                <th class="py-3 px-5">{{ __('Assessment') }}</th>
                                <th class="py-3 px-5">{{ __('Participant Type') }}</th>
                                <th class="py-3 px-5">{{ __('Questions') }}</th>
                                <th class="py-3 px-5">{{ __('Status') }}</th>
                                <th class="py-3 px-5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($forms as $form)
                                <tr>
                                    <td class="py-3 px-5 font-medium text-[#152A4E] dark:text-white">
                                        {{ $form->label }}
                                        @if ($form->description)
                                            <div class="text-xs text-gray-400 font-normal max-w-md">{{ $form->description }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-300">
                                        {{ $form->participant_type ?? __('Any unmapped type (fallback)') }}
                                    </td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-300 tabular-nums">{{ $form->questions_count }}</td>
                                    <td class="py-3 px-5">
                                        <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1
                                            {{ $form->is_active
                                                ? 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800'
                                                : 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600' }}">
                                            {{ $form->is_active ? __('In use') : __('Draft') }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-5 text-right">
                                        <a href="{{ route('admin.tna-forms.edit', $form) }}"
                                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                            {{ __('Edit Questions') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-chart-card>
        </div>
    </div>
</x-app-layout>
