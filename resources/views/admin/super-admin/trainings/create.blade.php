@php
    $preselected = collect($trainings)->firstWhere('slug', $selectedSlug);
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Schedule a Training') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <a href="{{ route('admin.calendar') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white mb-6">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to Calendar') }}
            </a>

            <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Schedule a Training') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">{{ __('Create a training on behalf of any region and put it straight on the calendar as approved — no regional review step needed.') }}</p>

            @if ($errors->any())
                <div class="mb-6 flex items-start gap-2 text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ __('Please fix the errors below before scheduling this training.') }}</span>
                </div>
            @endif

            <form
                method="POST" action="{{ route('admin.trainings.store') }}" class="space-y-6"
                x-data="{
                    trainingSlug: '{{ old('training_slug', $preselected['slug'] ?? '') }}',
                    region: '{{ old('region', '') }}',
                    participantSearch: '',
                    searchResults: [],
                    selectedParticipants: @js($selectedParticipants->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'organization' => $p->organization, 'region' => $p->region])->values()),
                    numberOfParticipants: '{{ old('number_of_participants', '') }}',
                    searchParticipants() {
                        const params = new URLSearchParams({ region: this.region, q: this.participantSearch });
                        fetch('{{ route('admin.trainings.participants') }}?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(response => response.json())
                            .then(data => { this.searchResults = data; });
                    },
                    isSelected(id) {
                        return this.selectedParticipants.some(p => p.id === id);
                    },
                    toggleParticipant(participant) {
                        const index = this.selectedParticipants.findIndex(p => p.id === participant.id);
                        if (index > -1) {
                            this.selectedParticipants.splice(index, 1);
                        } else {
                            this.selectedParticipants.push(participant);
                        }
                    },
                }"
                x-init="searchParticipants(); $watch('region', () => searchParticipants())"
                x-effect="if (selectedParticipants.length) numberOfParticipants = selectedParticipants.length"
            >
                @csrf

                <!-- Training -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('1. Which training is this?') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ __('Tap a card to select it.') }}</p>

                    <x-input-error :messages="$errors->get('training_slug')" class="mb-4" />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-96 overflow-y-auto pe-1">
                        @foreach ($trainings as $training)
                            <label
                                class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition"
                                :class="trainingSlug === '{{ $training['slug'] }}' ? 'border-[#152A4E] bg-[#152A4E]/5 dark:bg-[#152A4E]/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
                            >
                                <input type="radio" name="training_slug" value="{{ $training['slug'] }}"
                                    class="sr-only" required
                                    x-model="trainingSlug">
                                <span class="text-[11px] font-semibold tracking-wide uppercase text-[#152A4E] dark:text-white bg-[#152A4E]/8 dark:bg-[#152A4E]/30 rounded-full px-2.5 py-1 w-fit mb-2">
                                    {{ $training['category'] }}
                                </span>
                                <span class="font-bold text-[#152A4E] dark:text-white leading-snug mb-1">{{ $training['title'] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $training['hours'] }} {{ __('training hours') }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Details -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-5">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('2. Training details') }}</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <x-input-label for="region" :value="__('Region')" />
                            <select id="region" name="region" required x-model="region"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                                <option value="" disabled {{ old('region') ? '' : 'selected' }}>{{ __('Select') }}</option>
                                @foreach ($regions as $regionOption)
                                    <option value="{{ $regionOption }}" @selected(old('region') === $regionOption)>{{ $regionOption }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('region')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="agency_type" :value="__('Agency Type')" />
                            <select id="agency_type" name="agency_type"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                                <option value="">{{ __('Not set') }}</option>
                                @foreach ($agencyTypeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('agency_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('agency_type')" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <x-input-label for="requesting_agency" :value="__('Requesting Agency / Organization')" />
                            <x-text-input id="requesting_agency" type="text" name="requesting_agency" class="mt-1 block w-full" required value="{{ old('requesting_agency') }}" />
                            <x-input-error :messages="$errors->get('requesting_agency')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="lgu" :value="__('LGU (optional)')" />
                            <x-text-input id="lgu" type="text" name="lgu" class="mt-1 block w-full" value="{{ old('lgu') }}" />
                            <x-input-error :messages="$errors->get('lgu')" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div>
                            <x-input-label for="contact_person" :value="__('Contact Person')" />
                            <x-text-input id="contact_person" type="text" name="contact_person" class="mt-1 block w-full" required value="{{ old('contact_person') }}" />
                            <x-input-error :messages="$errors->get('contact_person')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="contact_number" :value="__('Contact Number')" />
                            <x-text-input id="contact_number" type="text" name="contact_number" class="mt-1 block w-full" required value="{{ old('contact_number') }}" />
                            <x-input-error :messages="$errors->get('contact_number')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="contact_email" :value="__('Email Address')" />
                            <x-text-input id="contact_email" type="email" name="contact_email" class="mt-1 block w-full" required value="{{ old('contact_email') }}" />
                            <x-input-error :messages="$errors->get('contact_email')" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="purpose" :value="__('Purpose')" />
                        <textarea id="purpose" name="purpose" rows="3" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">{{ old('purpose') }}</textarea>
                        <x-input-error :messages="$errors->get('purpose')" class="mt-1" />
                    </div>
                </div>

                <!-- Schedule -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-5">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('3. Schedule') }}</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div>
                            <x-input-label for="preferred_date" :value="__('Training Date')" />
                            <x-text-input id="preferred_date" type="date" name="preferred_date" class="mt-1 block w-full"
                                required value="{{ old('preferred_date', $defaultPreferredDate) }}" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Weekdays only (Monday–Friday).') }}</p>
                            <x-input-error :messages="$errors->get('preferred_date')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="venue" :value="__('Venue')" />
                            <x-text-input id="venue" type="text" name="venue" class="mt-1 block w-full" required value="{{ old('venue') }}" />
                            <x-input-error :messages="$errors->get('venue')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="number_of_participants" :value="__('Number of Participants')" />
                            <x-text-input id="number_of_participants" type="number" name="number_of_participants" min="1" max="1000" class="mt-1 block w-full"
                                required x-model="numberOfParticipants" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-show="selectedParticipants.length" x-cloak>
                                {{ __('Auto-filled from the participants selected below.') }}
                            </p>
                            <x-input-error :messages="$errors->get('number_of_participants')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Participants -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">{{ __('4. Select Participants') }}</h2>
                        <span class="text-xs font-semibold text-[#152A4E] dark:text-white shrink-0" x-show="selectedParticipants.length" x-cloak>
                            <span x-text="selectedParticipants.length"></span> {{ __('selected') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Optional — pick a region above to narrow this list. Leave empty to just set a participant count.') }}</p>

                    <template x-for="participant in selectedParticipants" :key="participant.id">
                        <input type="hidden" name="participant_ids[]" :value="participant.id">
                    </template>

                    <div class="flex flex-wrap gap-2 mb-4" x-show="selectedParticipants.length" x-cloak>
                        <template x-for="participant in selectedParticipants" :key="participant.id">
                            <span class="inline-flex items-center gap-1.5 bg-[#152A4E]/8 dark:bg-[#152A4E]/30 text-[#152A4E] dark:text-white text-xs font-medium rounded-full ps-3 pe-2 py-1">
                                <span x-text="participant.name"></span>
                                <button type="button" @click="toggleParticipant(participant)" class="hover:text-red-600">&times;</button>
                            </span>
                        </template>
                    </div>

                    <input type="text" x-model="participantSearch" @input.debounce.350ms="searchParticipants()"
                        placeholder="{{ __('Search participants by name...') }}"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm py-2.5 mb-4">

                    <div class="max-h-72 overflow-y-auto border border-gray-100 dark:border-gray-700 rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-for="participant in searchResults" :key="participant.id">
                            <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <input type="checkbox" :checked="isSelected(participant.id)" @change="toggleParticipant(participant)"
                                    class="rounded border-gray-300 text-[#152A4E] focus:ring-[#152A4E]">
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-gray-800 dark:text-gray-100 truncate" x-text="participant.name"></span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 truncate" x-text="(participant.organization || '{{ __('No organization') }}') + ' · ' + (participant.region || '{{ __('No region') }}')"></span>
                                </span>
                            </label>
                        </template>
                        <p class="px-4 py-6 text-sm text-center text-gray-400 dark:text-gray-500" x-show="searchResults.length === 0" x-cloak>
                            {{ __('No participants match this region/search.') }}
                        </p>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2" x-show="searchResults.length === 10" x-cloak>
                        {{ __('Showing the first 10 matches — type a name to narrow the list further.') }}
                    </p>
                    <x-input-error :messages="$errors->get('participant_ids')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-6 py-3 hover:bg-[#1E3A66] transition">
                        {{ __('Schedule Training') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
