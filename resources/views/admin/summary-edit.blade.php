<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Training Request') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admin.summary') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to Summary') }}
            </a>

            @if ($errors->any())
                <div class="flex items-start gap-3 text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ __('Please check the highlighted fields below.') }}</span>
                </div>
            @endif

            <!-- Overview -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h1 class="text-xl font-bold text-[#152A4E] dark:text-white mb-1">{{ $record->training_title }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ $record->reference_number ?? __('No reference number yet') }}</p>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-0.5">{{ __('Requesting Agency') }}</dt>
                        <dd class="text-gray-700 dark:text-gray-200">{{ $record->requesting_agency }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-0.5">{{ __('Contact') }}</dt>
                        <dd class="text-gray-700 dark:text-gray-200">{{ $record->contact_person }} &middot; {{ $record->contact_number }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-0.5">
                            {{ trans_choice(':count Participant|:count Participants', $participants->count(), ['count' => $participants->count()]) }}
                        </dt>
                        <dd class="text-gray-700 dark:text-gray-200">
                            @if ($participants->isEmpty())
                                <span class="text-gray-400">{{ __('None on file') }}</span>
                            @else
                                {{ $participants->pluck('name')->join(', ') }}
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <form method="POST" action="{{ route('admin.summary.update', $record) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Status -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Status') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Where this request stands right now.') }}</p>

                    <select id="status" name="status"
                        class="w-full sm:w-72 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $record->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                </div>

                <!-- Reschedule -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Date & Venue') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __("Only change these if the training needs to move — for example, because of weather. If it's already Approved, remember to update the Status above too.") }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="preferred_date" :value="__('Date')" />
                            <x-text-input id="preferred_date" name="preferred_date" type="date" class="mt-1 block w-full" required
                                value="{{ old('preferred_date', $record->preferred_date->format('Y-m-d')) }}" />
                            <x-input-error :messages="$errors->get('preferred_date')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="venue" :value="__('Venue')" />
                            <x-text-input id="venue" name="venue" type="text" class="mt-1 block w-full" required
                                value="{{ old('venue', $record->venue) }}" />
                            <x-input-error :messages="$errors->get('venue')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Category & Region -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Category & Region') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Used to filter the Calendar tab.') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="category" :value="__('Category')" />
                            <select id="category" name="category"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                                <option value="">{{ __('No category') }}</option>
                                @foreach ($categoryLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('category', $record->category) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('category')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label :value="__('Region')" />
                            @if (Auth::user()->isAdmin())
                                <x-text-input type="text" class="mt-1 block w-full bg-gray-50 dark:bg-gray-700" value="{{ Auth::user()->region }}" disabled />
                                <p class="text-xs text-gray-400 mt-1">{{ __('Locked to your region.') }}</p>
                            @else
                                <select name="region"
                                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                                    <option value="">{{ __('No region') }}</option>
                                    @foreach ($regions as $region)
                                        <option value="{{ $region }}" @selected(old('region', $record->region) === $region)>{{ $region }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('region')" class="mt-1" />
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Certificate Details -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Certificate Details') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Applies to every participant listed above.') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <x-input-label for="lgu" :value="__('LGU')" />
                            <x-text-input id="lgu" name="lgu" type="text" class="mt-1 block w-full" value="{{ old('lgu', $record->lgu) }}" />
                            <x-input-error :messages="$errors->get('lgu')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="certificate_code" :value="__('Certificate Code')" />
                            <x-text-input id="certificate_code" name="certificate_code" type="text" class="mt-1 block w-full" value="{{ old('certificate_code', $record->certificate_code) }}" />
                            <x-input-error :messages="$errors->get('certificate_code')" class="mt-1" />
                        </div>
                    </div>

                    <x-input-label for="certificate_remarks" :value="__('Certificate Remarks')" />
                    <select id="certificate_remarks" name="certificate_remarks"
                        class="mt-1 block w-full sm:w-72 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                        <option value="">{{ __('No remarks') }}</option>
                        @foreach ($certificateRemarksLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('certificate_remarks', $record->certificate_remarks) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('certificate_remarks')" class="mt-1" />
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-8 py-3 hover:bg-[#1E3A66] transition">
                        {{ __('Save Changes') }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
