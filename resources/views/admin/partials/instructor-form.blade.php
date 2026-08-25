<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Add Instructor') }}</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Instructor ratings are computed automatically from L1 Evaluation data once exactly one instructor is on file for a given training type.') }}</p>

    <form method="POST" action="{{ route('admin.instructors.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Instructor Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required value="{{ old('name') }}" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="sex" :value="__('Sex')" />
            <select id="sex" name="sex"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                <option value="" {{ old('sex') ? '' : 'selected' }}>{{ __('Unspecified') }}</option>
                <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                <option value="Other" {{ old('sex') == 'Other' ? 'selected' : '' }}>{{ __('Other') }}</option>
            </select>
            <x-input-error :messages="$errors->get('sex')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="position" :value="__('Position')" />
            <x-text-input id="position" name="position" type="text" class="mt-1 block w-full" value="{{ old('position') }}" />
            <x-input-error :messages="$errors->get('position')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email') }}" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" value="{{ old('phone') }}" />
            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="training_type" :value="__('Type of Training')" />
            <x-text-input id="training_type" name="training_type" type="text" class="mt-1 block w-full" required value="{{ old('training_type') }}" />
            <x-input-error :messages="$errors->get('training_type')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="specialization" :value="__('Specialization')" />
            <x-text-input id="specialization" name="specialization" type="text" class="mt-1 block w-full" value="{{ old('specialization') }}" />
            <x-input-error :messages="$errors->get('specialization')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="certification" :value="__('Certification')" />
            <x-text-input id="certification" name="certification" type="text" class="mt-1 block w-full" value="{{ old('certification') }}" />
            <x-input-error :messages="$errors->get('certification')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="certificate_code" :value="__('Certificate Code')" />
            <x-text-input id="certificate_code" name="certificate_code" type="text" class="mt-1 block w-full" value="{{ old('certificate_code') }}" />
            <x-input-error :messages="$errors->get('certificate_code')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="deployment" :value="__('Deployment (if applicable)')" />
            <x-text-input id="deployment" name="deployment" type="text" class="mt-1 block w-full" value="{{ old('deployment') }}" />
            <x-input-error :messages="$errors->get('deployment')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="agency_organization" :value="__('Agency / Organization (if applicable)')" />
            <x-text-input id="agency_organization" name="agency_organization" type="text" class="mt-1 block w-full" value="{{ old('agency_organization') }}" />
            <x-input-error :messages="$errors->get('agency_organization')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="lgu" :value="__('LGU (if applicable)')" />
            <x-text-input id="lgu" name="lgu" type="text" class="mt-1 block w-full" value="{{ old('lgu') }}" />
            <x-input-error :messages="$errors->get('lgu')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="region" :value="__('Region')" />
            @if (Auth::user()->isAdmin())
                <x-text-input type="text" class="mt-1 block w-full bg-gray-50 dark:bg-gray-700" value="{{ Auth::user()->region }}" disabled />
                <p class="text-xs text-gray-400 mt-1">{{ __('Locked to your region.') }}</p>
            @else
                <select id="region" name="region"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-[#152A4E] focus:ring-[#152A4E]">
                    <option value="">{{ __('Central / unassigned') }}</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region }}" @selected(old('region') === $region)>{{ $region }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('region')" class="mt-1" />
            @endif
        </div>

        <div class="sm:col-span-2 lg:col-span-3 flex justify-end">
            <button type="submit"
                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                {{ __('Add Instructor') }}
            </button>
        </div>
    </form>
</div>
