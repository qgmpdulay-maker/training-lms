<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Assessment') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admin.tna-forms.index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to assessments') }}
            </a>

            @if ($errors->any())
                <div class="flex items-start gap-3 text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ __('Name the assessment and say who answers it. You add its questions next, and it stays a draft until you mark it in use.') }}
                </p>

                <form method="POST" action="{{ route('admin.tna-forms.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Name') }}</label>
                        <input id="label" type="text" name="label" value="{{ old('label') }}" required autofocus
                            placeholder="{{ __('e.g. OCD Personnel') }}"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>

                    <div>
                        <label for="participant_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Given to') }}</label>
                        <select id="participant_type" name="participant_type"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="">{{ __('Any type without its own assessment (fallback)') }}</option>
                            @foreach ($participantTypes as $type)
                                <option value="{{ $type }}" @selected(old('participant_type') === $type) @disabled(in_array($type, $takenTypes, true))>
                                    {{ $type }}@if (in_array($type, $takenTypes, true)) — {{ __('already has one') }} @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">{{ __('One assessment per participant type. A type that already has one has to be edited instead.') }}</p>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Description') }}</label>
                        <textarea id="description" name="description" rows="2"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">{{ old('description') }}</textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                            {{ __('Create and Add Questions') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
