@php
    $initialStep = 1;
    if ($errors->hasAny(['agency_type', 'requesting_agency', 'lgu', 'region', 'contact_person', 'contact_number', 'contact_email', 'number_of_participants', 'preferred_date', 'venue', 'purpose'])) {
        $initialStep = 2;
    }
    if ($errors->hasAny(['tna_completed', 'tna_file'])) {
        $initialStep = 3;
    }
    if ($errors->has('logistics_acknowledged')) {
        $initialStep = 4;
    }
    if ($errors->hasAny(['signature_name', 'signed_letter'])) {
        $initialStep = 5;
    }
    if ($errors->has('training_slug')) {
        $initialStep = 1;
    }
    $preselected = collect($trainings)->firstWhere('slug', $selectedSlug);
@endphp
<x-public-layout>
    <div class="pt-28 sm:pt-32 pb-16">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <a href="{{ route('home') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-[#152A4E] mb-6">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to Trainings') }}
            </a>

            <h1 class="text-2xl sm:text-3xl font-bold text-[#152A4E] mb-1">{{ __('Request Technical Assistance') }}</h1>
            <p class="text-sm text-gray-500 mb-8">{{ __("For LGUs and NGAs requesting a training from OCD. Fill this out one step at a time — you can go back at any point before you submit.") }}</p>

            <div
                x-data="{
                    step: {{ $initialStep }},
                    training_slug: '{{ old('training_slug', $preselected['slug'] ?? '') }}',
                    trainingTitle: '{{ addslashes(old('training_slug') ? (collect($trainings)->firstWhere('slug', old('training_slug'))['title'] ?? '') : ($preselected['title'] ?? '')) }}',
                    preferredDate: '{{ old('preferred_date', $defaultPreferredDate) }}',
                    venue: '{{ addslashes(old('venue', '')) }}',
                    stepError: '',
                    get daysUntil() {
                        if (!this.preferredDate) return null;
                        const diff = (new Date(this.preferredDate) - new Date(new Date().toDateString())) / 86400000;
                        return Math.round(diff);
                    },
                    get isWeekend() {
                        if (!this.preferredDate) return false;
                        const day = new Date(this.preferredDate + 'T00:00:00').getDay();
                        return day === 0 || day === 6;
                    },
                    next() {
                        const stepEl = document.getElementById('step-' + this.step);
                        const invalid = stepEl.querySelector(':invalid');
                        if (invalid) {
                            this.stepError = invalid.validationMessage || '{{ __('Please complete this step before continuing.') }}';
                            invalid.focus({ preventScroll: true });
                            invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }
                        if (this.step === 2 && this.isWeekend) {
                            this.stepError = '{{ __('Trainings can only be scheduled on weekdays (Monday–Friday). Please choose a different date.') }}';
                            const dateEl = document.getElementById('preferred_date');
                            dateEl.focus({ preventScroll: true });
                            dateEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }
                        this.stepError = '';
                        if (this.step < 5) this.step++;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    },
                    back() {
                        this.stepError = '';
                        if (this.step > 1) this.step--;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    },
                }"
            >
                <!-- Progress -->
                <div class="mb-8">
                    <p class="text-sm font-semibold text-[#152A4E] mb-2">{{ __('Step') }} <span x-text="step"></span> {{ __('of 5') }}</p>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-[#152A4E] to-[#E2762D] transition-all duration-300"
                            :style="'width: ' + (step / 5 * 100) + '%'"></div>
                    </div>
                </div>

                <div x-show="stepError" x-cloak x-text="stepError"
                    class="mb-6 flex items-start gap-2 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg px-4 py-3"></div>

                <form method="POST" action="{{ route('public.training-requests.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <!-- Honeypot — left blank by real visitors, hidden from the tab order and off-screen so screen readers skip it. -->
                    <div class="absolute -left-[9999px]" aria-hidden="true">
                        <label for="website">{{ __('Website') }}</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <!-- STEP 1: Choose Training -->
                    <div id="step-1" x-show="step === 1" x-cloak>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 sm:p-8">
                            <h2 class="text-lg font-bold text-[#152A4E] mb-1">{{ __('1. Which training are you requesting?') }}</h2>
                            <p class="text-sm text-gray-500 mb-6">{{ __('Tap a card to select it.') }}</p>

                            <x-input-error :messages="$errors->get('training_slug')" class="mb-4" />

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach ($trainings as $training)
                                    <label
                                        class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition"
                                        :class="training_slug === '{{ $training['slug'] }}' ? 'border-[#152A4E] bg-[#152A4E]/5' : 'border-gray-200 hover:border-gray-300'"
                                    >
                                        <input type="radio" name="training_slug" value="{{ $training['slug'] }}"
                                            class="sr-only" required
                                            x-model="training_slug"
                                            @click="trainingTitle = '{{ addslashes($training['title']) }}'; stepError = ''">
                                        <span class="text-[11px] font-semibold tracking-wide uppercase text-[#152A4E] bg-[#152A4E]/8 rounded-full px-2.5 py-1 w-fit mb-2">
                                            {{ $training['category'] }}
                                        </span>
                                        <span class="font-bold text-[#152A4E] leading-snug mb-1">{{ $training['title'] }}</span>
                                        <span class="text-xs text-gray-500">{{ $training['hours'] }} {{ __('training hours') }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Request Details -->
                    <div id="step-2" x-show="step === 2" x-cloak>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 sm:p-8 space-y-5">
                            <h2 class="text-lg font-bold text-[#152A4E] mb-1">{{ __('2. Tell us about the requesting agency') }}</h2>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="agency_type" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('Agency Type') }}</label>
                                    <select id="agency_type" name="agency_type" required
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                        <option value="" disabled {{ old('agency_type') ? '' : 'selected' }}>{{ __('Select') }}</option>
                                        @foreach ($agencyTypeLabels as $value => $label)
                                            <option value="{{ $value }}" @selected(old('agency_type') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('agency_type')" class="mt-1" />
                                </div>
                                <div>
                                    <label for="region" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('Region') }}</label>
                                    <select id="region" name="region" required
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                        <option value="" disabled {{ old('region') ? '' : 'selected' }}>{{ __('Select') }}</option>
                                        @foreach ($regions as $regionOption)
                                            <option value="{{ $regionOption }}" @selected(old('region') === $regionOption)>{{ $regionOption }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('region')" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <label for="requesting_agency" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('Requesting Agency / Organization') }}</label>
                                <input id="requesting_agency" type="text" name="requesting_agency" required
                                    value="{{ old('requesting_agency') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                <x-input-error :messages="$errors->get('requesting_agency')" class="mt-1" />
                            </div>

                            <div>
                                <label for="lgu" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('LGU (city/municipality/barangay, if applicable)') }}</label>
                                <input id="lgu" type="text" name="lgu"
                                    value="{{ old('lgu') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                <x-input-error :messages="$errors->get('lgu')" class="mt-1" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('Contact Person') }}</label>
                                    <input id="contact_person" type="text" name="contact_person" required
                                        value="{{ old('contact_person') }}"
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                    <x-input-error :messages="$errors->get('contact_person')" class="mt-1" />
                                </div>
                                <div>
                                    <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('Contact Number') }}</label>
                                    <input id="contact_number" type="text" name="contact_number" required
                                        value="{{ old('contact_number') }}"
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                    <x-input-error :messages="$errors->get('contact_number')" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('Email Address') }}</label>
                                <input id="contact_email" type="email" name="contact_email" required
                                    value="{{ old('contact_email') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                <p class="text-xs text-gray-500 mt-1">{{ __("We'll send your reference number and any updates here.") }}</p>
                                <x-input-error :messages="$errors->get('contact_email')" class="mt-1" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="number_of_participants" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('Number of Participants') }}</label>
                                    <input id="number_of_participants" type="number" name="number_of_participants" min="1" max="1000" required
                                        value="{{ old('number_of_participants') }}"
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                    <x-input-error :messages="$errors->get('number_of_participants')" class="mt-1" />
                                </div>
                                <div>
                                    <label for="preferred_date" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('Preferred Training Date') }}</label>
                                    <input id="preferred_date" type="date" name="preferred_date" required
                                        x-model="preferredDate"
                                        min="{{ now()->addDay()->toDateString() }}"
                                        value="{{ old('preferred_date', $defaultPreferredDate) }}"
                                        class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                    <p class="text-xs text-gray-500 mt-1">{{ __('Defaults to a month out on the nearest weekday — trainings can only be held on office days (Monday–Friday).') }}</p>
                                    <x-input-error :messages="$errors->get('preferred_date')" class="mt-1" />
                                </div>
                            </div>

                            <p x-show="isWeekend" x-cloak
                                class="flex items-start gap-2 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                                <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ __('Trainings can only be scheduled on weekdays (Monday–Friday). Please choose a different date.') }}</span>
                            </p>

                            <p x-show="!isWeekend && daysUntil !== null && daysUntil < 30" x-cloak
                                class="flex items-start gap-2 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
                                <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ __('This date is less than a month away. Requests should ideally be filed at least one month ahead — you can still submit, but preparation time will be shorter.') }}</span>
                            </p>

                            <div>
                                <label for="venue" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('Where will the training be held? (Venue)') }}</label>
                                <input id="venue" type="text" name="venue" required
                                    x-model="venue"
                                    placeholder="{{ __('e.g. Municipal Hall Function Room, Barangay Covered Court') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                <x-input-error :messages="$errors->get('venue')" class="mt-1" />
                            </div>

                            <div>
                                <label for="purpose" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('Why is this training needed? (brief reason)') }}</label>
                                <textarea id="purpose" name="purpose" rows="3" required
                                    class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">{{ old('purpose') }}</textarea>
                                <x-input-error :messages="$errors->get('purpose')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Training Needs Assessment -->
                    <div id="step-3" x-show="step === 3" x-cloak>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 sm:p-8 space-y-5">
                            <h2 class="text-lg font-bold text-[#152A4E] mb-1">{{ __('3. Training Needs Assessment') }}</h2>
                            <p class="text-sm text-gray-500">
                                {{ __('Before requesting a training, OCD asks that the requesting agency first complete a Training Needs Assessment (TNA). This helps prepare the right training for the group.') }}
                            </p>

                            <label class="flex items-start gap-3 p-4 rounded-lg border border-gray-200 cursor-pointer has-[:checked]:border-[#152A4E] has-[:checked]:bg-[#152A4E]/5">
                                <input type="checkbox" name="tna_completed" value="1" required
                                    class="mt-0.5 h-5 w-5 rounded border-gray-300 text-[#152A4E] focus:ring-[#152A4E]">
                                <span class="text-sm text-gray-700">{{ __('The Training Needs Assessment has been completed for this request.') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('tna_completed')" class="mt-1" />

                            <div>
                                <label for="tna_file" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    {{ __('Upload the completed TNA (optional — a PDF or photo is fine)') }}
                                </label>
                                <input id="tna_file" type="file" name="tna_file" accept=".pdf,.jpg,.jpeg,.png"
                                    class="block w-full text-sm text-gray-600 file:me-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#152A4E]/8 file:text-[#152A4E] hover:file:bg-[#152A4E]/15">
                                <x-input-error :messages="$errors->get('tna_file')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: Please Read This -->
                    <div id="step-4" x-show="step === 4" x-cloak>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 sm:p-8 space-y-5">
                            <h2 class="text-lg font-bold text-[#152A4E] mb-1">{{ __('4. Please read this before you continue') }}</h2>

                            <div class="rounded-lg bg-gray-50 border border-gray-200 p-5 space-y-3">
                                <p class="text-sm font-semibold text-gray-800">{{ __('OCD trainings are free. The requesting agency will need to arrange and pay for:') }}</p>
                                <ul class="space-y-2 text-sm text-gray-600">
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#E2762D] shrink-0"></span>
                                        {{ __('Training venue') }}
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#E2762D] shrink-0"></span>
                                        {{ __('Accommodation, meals, and transportation for the instructor and participants') }}
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#E2762D] shrink-0"></span>
                                        {{ __('Printing of training materials') }}
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#E2762D] shrink-0"></span>
                                        {{ __('Honoraria for instructors and facilitators') }}
                                    </li>
                                </ul>
                            </div>

                            <label class="flex items-start gap-3 p-4 rounded-lg border border-gray-200 cursor-pointer has-[:checked]:border-[#152A4E] has-[:checked]:bg-[#152A4E]/5">
                                <input type="checkbox" name="logistics_acknowledged" value="1" required
                                    class="mt-0.5 h-5 w-5 rounded border-gray-300 text-[#152A4E] focus:ring-[#152A4E]">
                                <span class="text-sm text-gray-700">{{ __('The requesting agency understands and agrees to arrange these.') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('logistics_acknowledged')" class="mt-1" />
                        </div>
                    </div>

                    <!-- STEP 5: Review & Sign -->
                    <div id="step-5" x-show="step === 5" x-cloak>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 sm:p-8 space-y-6">
                            <h2 class="text-lg font-bold text-[#152A4E] mb-1">{{ __('5. Review and sign') }}</h2>
                            <p class="text-sm text-gray-500">{{ __('Please check that everything below is correct before submitting.') }}</p>

                            <dl class="divide-y divide-gray-100 text-sm">
                                <div class="py-3 flex justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Training') }}</dt>
                                    <dd class="font-medium text-gray-800 text-right" x-text="trainingTitle"></dd>
                                </div>
                                <div class="py-3 flex justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Preferred Date') }}</dt>
                                    <dd class="font-medium text-gray-800 text-right" x-text="preferredDate"></dd>
                                </div>
                                <div class="py-3 flex justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Venue') }}</dt>
                                    <dd class="font-medium text-gray-800 text-right" x-text="venue"></dd>
                                </div>
                            </dl>

                            <div class="rounded-lg bg-gray-50 border border-gray-200 p-5">
                                <label for="signature_name" class="block text-sm font-semibold text-gray-800 mb-1.5">
                                    {{ __('Name of authorized signatory') }}
                                </label>
                                <p class="text-xs text-gray-500 mb-3">{{ __('Typing a name here confirms the details above are true and correct.') }}</p>
                                <input id="signature_name" type="text" name="signature_name" required
                                    value="{{ old('signature_name') }}"
                                    placeholder="{{ __('Full Name') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                <x-input-error :messages="$errors->get('signature_name')" class="mt-1" />
                            </div>

                            <div>
                                <label for="signed_letter" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    {{ __('Already have a printed, signed request letter? Upload a photo or scan instead (optional)') }}
                                </label>
                                <input id="signed_letter" type="file" name="signed_letter" accept=".pdf,.jpg,.jpeg,.png"
                                    class="block w-full text-sm text-gray-600 file:me-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#152A4E]/8 file:text-[#152A4E] hover:file:bg-[#152A4E]/15">
                                <x-input-error :messages="$errors->get('signed_letter')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="back()" x-show="step > 1" x-cloak
                            class="text-sm font-semibold text-gray-500 hover:text-[#152A4E] px-4 py-3">
                            {{ __('Back') }}
                        </button>
                        <span x-show="step === 1"></span>

                        <button type="button" @click="next()" x-show="step < 5" x-cloak
                            class="bg-[#152A4E] hover:bg-[#1E3A66] text-white text-sm font-semibold rounded-lg px-8 py-3 transition">
                            {{ __('Next') }}
                        </button>

                        <button type="submit" x-show="step === 5" x-cloak
                            class="bg-[#E2762D] hover:bg-[#c9631f] text-white text-sm font-semibold rounded-lg px-8 py-3 transition">
                            {{ __('Submit Request') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-public-layout>
