<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New ATAR') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($errors->any())
                <div class="text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white">{{ __('New After Training Activity Report') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Auto-fill from an already-completed training, or start from a blank report.') }}</p>
            </div>

            {{-- Alpine `mode` just toggles which card looks selected and whether the
                 training dropdown shows; the hidden input below is the actual field
                 AtarReportController@store reads to decide generate vs blank. --}}
            <form method="POST" action="{{ route('admin.atar-reports.store') }}" x-data="{ mode: 'generate' }" class="space-y-6">
                @csrf

                {{-- Mode picker: two clickable cards, not radio buttons, so the copy explaining each option can sit inside the control itself --}}
                <div class="grid sm:grid-cols-2 gap-4">
                    <button type="button" @click="mode = 'generate'"
                        :class="mode === 'generate' ? 'border-[#152A4E] ring-2 ring-[#152A4E]/20 bg-[#152A4E]/5' : 'border-gray-200 dark:border-gray-700'"
                        class="text-left rounded-xl border bg-white dark:bg-gray-800 p-5 transition">
                        <p class="font-semibold text-gray-800 dark:text-white">{{ __('Auto-generate from a training') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Pulls the title, venue, date, attendees, graduates, lecturers, and evaluation results from a completed training. You still write the narrative sections yourself.') }}</p>
                    </button>
                    <button type="button" @click="mode = 'blank'"
                        :class="mode === 'blank' ? 'border-[#152A4E] ring-2 ring-[#152A4E]/20 bg-[#152A4E]/5' : 'border-gray-200 dark:border-gray-700'"
                        class="text-left rounded-xl border bg-white dark:bg-gray-800 p-5 transition">
                        <p class="font-semibold text-gray-800 dark:text-white">{{ __('Write a new ATAR from scratch') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Start with a blank report — useful for trainings not tracked in this system, or a fully custom document.') }}</p>
                    </button>
                </div>

                <input type="hidden" name="mode" :value="mode" />

                {{-- Only shown/required when generating from a training; hidden entirely in blank mode --}}
                <div x-show="mode === 'generate'" x-cloak class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 space-y-3">
                    <label for="training_request_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Completed training') }}</label>
                    <select id="training_request_id" name="training_request_id"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        <option value="">{{ __('Select a training...') }}</option>
                        @foreach ($trainingRequests as $trainingRequest)
                            <option value="{{ $trainingRequest->id }}">
                                {{ $trainingRequest->training_title }} — {{ $trainingRequest->region }} ({{ optional($trainingRequest->preferred_date)->format('M d, Y') }})
                            </option>
                        @endforeach
                    </select>
                    @if ($trainingRequests->isEmpty())
                        <p class="text-sm text-gray-400">{{ __('No completed trainings found yet.') }}</p>
                    @endif
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.atar-reports.index') }}" class="inline-flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                        {{ __('Continue') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
