@php
    $initialStep = 1;
    if ($errors->hasAny(['requesting_agency', 'contact_person', 'contact_number', 'contact_email', 'number_of_participants', 'preferred_date', 'venue', 'purpose'])) {
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
<x-app-layout>
    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white mb-6">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to Dashboard') }}
            </a>

            <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Request a Training') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">{{ __('Fill this out one step at a time. You can go back at any point before you submit.') }}</p>

            <div
                x-data="{
                    step: {{ $initialStep }},
                    training_slug: '{{ old('training_slug', $preselected['slug'] ?? '') }}',
                    trainingTitle: '{{ addslashes(old('training_slug') ? (collect($trainings)->firstWhere('slug', old('training_slug'))['title'] ?? '') : ($preselected['title'] ?? '')) }}',
                    preferredDate: '{{ old('preferred_date') }}',
                    venue: '{{ addslashes(old('venue', '')) }}',
                    tnaFileName: '',
                    signedLetterName: '',
                    get daysUntil() {
                        if (!this.preferredDate) return null;
                        const diff = (new Date(this.preferredDate) - new Date(new Date().toDateString())) / 86400000;
                        return Math.round(diff);
                    },
                    next() {
                        const stepEl = document.getElementById('step-' + this.step);
                        const invalid = stepEl.querySelector(':invalid');
                        if (invalid) { invalid.reportValidity(); return; }
                        if (this.step < 5) this.step++;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    },
                    back() {
                        if (this.step > 1) this.step--;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    },
                }"
            >
                <!-- Progress -->
                <div class="mb-8">
                    <p class="text-sm font-semibold text-[#152A4E] dark:text-white mb-2">{{ __('Step') }} <span x-text="step"></span> {{ __('of 5') }}</p>
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-[#152A4E] to-[#E2762D] transition-all duration-300"
                            :style="'width: ' + (step / 5 * 100) + '%'"></div>
                    </div>
                </div>

                <form method="POST" action="{{ route('training-requests.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <!-- STEP 1: Choose Training -->
                    <div id="step-1" x-show="step === 1" x-cloak>
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                            <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('1. Which training do you want to request?') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ __('Tap a card to select it.') }}</p>

                            <x-input-error :messages="$errors->get('training_slug')" class="mb-4" />

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach ($trainings as $training)
                                    <label
                                        class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition"
                                        :class="training_slug === '{{ $training['slug'] }}' ? 'border-[#152A4E] bg-[#152A4E]/5 dark:bg-[#152A4E]/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
                                    >
                                        <input type="radio" name="training_slug" value="{{ $training['slug'] }}"
                                            class="sr-only" required
                                            x-model="training_slug"
                                            @click="trainingTitle = '{{ addslashes($training['title']) }}'">
                                        <span class="text-[11px] font-semibold tracking-wide uppercase text-[#152A4E] dark:text-white bg-[#152A4E]/8 dark:bg-[#152A4E]/30 rounded-full px-2.5 py-1 w-fit mb-2">
                                            {{ $training['category'] }}
                                        </span>
                                        <span class="font-bold text-[#152A4E] dark:text-white leading-snug mb-1">{{ $training['title'] }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $training['hours'] }} {{ __('training hours') }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Your Details -->
                    <div id="step-2" x-show="step === 2" x-cloak>
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-5">
                            <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('2. Tell us about your request') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __("We've filled in what we know from your profile — please check it's correct.") }}</p>

                            <div>
                                <label for="requesting_agency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Your Agency / Organization') }}</label>
                                <input id="requesting_agency" type="text" name="requesting_agency" required
                                    value="{{ old('requesting_agency', $user->organization) }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                <x-input-error :messages="$errors->get('requesting_agency')" class="mt-1" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="contact_person" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Contact Person') }}</label>
                                    <input id="contact_person" type="text" name="contact_person" required
                                        value="{{ old('contact_person', $user->name) }}"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                    <x-input-error :messages="$errors->get('contact_person')" class="mt-1" />
                                </div>
                                <div>
                                    <label for="contact_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Contact Number') }}</label>
                                    <input id="contact_number" type="text" name="contact_number" required
                                        value="{{ old('contact_number', $user->mobile_number) }}"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                    <x-input-error :messages="$errors->get('contact_number')" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <label for="contact_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Email Address') }}</label>
                                <input id="contact_email" type="email" name="contact_email" required
                                    value="{{ old('contact_email', $user->email) }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                <x-input-error :messages="$errors->get('contact_email')" class="mt-1" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="number_of_participants" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Number of Participants') }}</label>
                                    <input id="number_of_participants" type="number" name="number_of_participants" min="1" max="1000" required
                                        value="{{ old('number_of_participants') }}"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                    <x-input-error :messages="$errors->get('number_of_participants')" class="mt-1" />
                                </div>
                                <div>
                                    <label for="preferred_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Preferred Training Date') }}</label>
                                    <input id="preferred_date" type="date" name="preferred_date" required
                                        x-model="preferredDate"
                                        min="{{ now()->addDay()->toDateString() }}"
                                        value="{{ old('preferred_date') }}"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                    <x-input-error :messages="$errors->get('preferred_date')" class="mt-1" />
                                </div>
                            </div>

                            <p x-show="daysUntil !== null && daysUntil < 30" x-cloak
                                class="flex items-start gap-2 text-sm text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-lg px-4 py-3">
                                <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ __('This date is less than a month away. Trainings should ideally be requested at least one month ahead — you can still submit, but please expect a shorter time to prepare.') }}</span>
                            </p>

                            <div>
                                <label for="venue" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Where would you like to hold the training? (Venue)') }}</label>
                                <input id="venue" type="text" name="venue" required
                                    x-model="venue"
                                    placeholder="{{ __('e.g. Municipal Hall Function Room, Barangay Covered Court') }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __("This tells the instructor and OCD's training staff exactly where to go — it will be included in your request letter.") }}</p>
                                <x-input-error :messages="$errors->get('venue')" class="mt-1" />
                            </div>

                            <div>
                                <label for="purpose" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Why do you need this training? (brief reason)') }}</label>
                                <textarea id="purpose" name="purpose" rows="3" required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">{{ old('purpose') }}</textarea>
                                <x-input-error :messages="$errors->get('purpose')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Training Needs Assessment -->
                    <div id="step-3" x-show="step === 3" x-cloak>
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-5">
                            <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('3. Training Needs Assessment') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Before requesting a training, OCD asks that you first complete a Training Needs Assessment (TNA). This helps them prepare the right training for your group.') }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __("Haven't taken it yet?") }}
                                <a href="{{ route('training-needs-assessment.index') }}" class="text-[#152A4E] dark:text-white font-semibold hover:text-[#E2762D]">{{ __('Take the Training Needs Assessment') }}</a>
                                {{ __('or email') }}
                                <a href="mailto:training@ocd.gov.ph" class="text-[#152A4E] font-semibold hover:text-[#E2762D]">training@ocd.gov.ph</a>
                                {{ __('for the paper form.') }}
                            </p>

                            <label class="flex items-start gap-3 p-4 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer has-[:checked]:border-[#152A4E] has-[:checked]:bg-[#152A4E]/5 dark:has-[:checked]:bg-[#152A4E]/20">
                                <input type="checkbox" name="tna_completed" value="1" required
                                    class="mt-0.5 h-5 w-5 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-[#152A4E] focus:ring-[#152A4E]">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('I have completed the Training Needs Assessment for this request.') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('tna_completed')" class="mt-1" />

                            <div>
                                <label for="tna_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    {{ __('Upload your completed TNA (optional — a PDF or photo is fine)') }}
                                </label>
                                <input id="tna_file" type="file" name="tna_file" accept=".pdf,.jpg,.jpeg,.png"
                                    @change="tnaFileName = $event.target.files[0]?.name ?? ''"
                                    class="block w-full text-sm text-gray-600 dark:text-gray-400 file:me-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#152A4E]/8 dark:file:bg-[#152A4E]/30 file:text-[#152A4E] dark:file:text-white hover:file:bg-[#152A4E]/15">
                                <x-input-error :messages="$errors->get('tna_file')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: Please Read This -->
                    <div id="step-4" x-show="step === 4" x-cloak>
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-5">
                            <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('4. Please read this before you continue') }}</h2>

                            <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 space-y-3">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('OCD trainings are free. Your agency will need to arrange and pay for:') }}</p>
                                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
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

                            <label class="flex items-start gap-3 p-4 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer has-[:checked]:border-[#152A4E] has-[:checked]:bg-[#152A4E]/5 dark:has-[:checked]:bg-[#152A4E]/20">
                                <input type="checkbox" name="logistics_acknowledged" value="1" required
                                    class="mt-0.5 h-5 w-5 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-[#152A4E] focus:ring-[#152A4E]">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('I understand and my agency agrees to arrange these.') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('logistics_acknowledged')" class="mt-1" />
                        </div>
                    </div>

                    <!-- STEP 5: Review & Sign -->
                    <div id="step-5" x-show="step === 5" x-cloak>
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-6">
                            <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('5. Review and sign') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Please check that everything below is correct before submitting.') }}</p>

                            <dl class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                <div class="py-3 flex justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Training') }}</dt>
                                    <dd class="font-medium text-gray-800 dark:text-gray-100 text-right" x-text="trainingTitle"></dd>
                                </div>
                                <div class="py-3 flex justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Preferred Date') }}</dt>
                                    <dd class="font-medium text-gray-800 dark:text-gray-100 text-right" x-text="preferredDate"></dd>
                                </div>
                                <div class="py-3 flex justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Venue') }}</dt>
                                    <dd class="font-medium text-gray-800 dark:text-gray-100 text-right" x-text="venue"></dd>
                                </div>
                            </dl>

                            <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5">
                                <label for="signature_name" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1.5">
                                    {{ __('Type your full name to sign this request') }}
                                </label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ __('By typing your name, you confirm the details above are true and correct.') }}</p>
                                <input id="signature_name" type="text" name="signature_name" required
                                    value="{{ old('signature_name', $user->name) }}"
                                    placeholder="{{ __('Full Name') }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                <x-input-error :messages="$errors->get('signature_name')" class="mt-1" />
                            </div>

                            <div>
                                <label for="signed_letter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    {{ __('Already have a printed, signed request letter? Upload a photo or scan instead (optional)') }}
                                </label>
                                <input id="signed_letter" type="file" name="signed_letter" accept=".pdf,.jpg,.jpeg,.png"
                                    @change="signedLetterName = $event.target.files[0]?.name ?? ''"
                                    class="block w-full text-sm text-gray-600 dark:text-gray-400 file:me-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#152A4E]/8 dark:file:bg-[#152A4E]/30 file:text-[#152A4E] dark:file:text-white hover:file:bg-[#152A4E]/15">
                                <x-input-error :messages="$errors->get('signed_letter')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="back()" x-show="step > 1" x-cloak
                            class="text-sm font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white px-4 py-3">
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
</x-app-layout>
