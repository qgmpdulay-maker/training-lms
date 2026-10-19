{{-- Both admins add instructors now: regional staff know who in their region
     can teach what. A Regional Admin's instructors are locked to their own
     region (the field below is read-only for them, and the controller forces
     it server-side regardless).

     Courses, certifications, and deployments are each many-per-instructor, so
     all three are repeatable rows — a person who teaches four courses is
     entered once, not four times. Alpine only manages the row arrays; every
     field still submits as a plain named input (`training_types[0][...]`). --}}
<x-chart-card body-class="p-6 sm:p-8"
    :title="__('Add Instructor')"
    :subtitle="__('An instructor can teach several types of training, hold several certifications, and be deployed more than once — add as many rows as you need.')">

    <form method="POST" action="{{ route('admin.instructors.store') }}" enctype="multipart/form-data" class="space-y-8"
        x-data="{
            trainingTypes: [{ training_type: '', certificate_code: '' }],
            certifications: [],
            deployments: [],
        }">
        @csrf

        @if ($errors->any())
            <div class="text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- The person --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <x-input-label for="name" :value="__('Instructor Name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required value="{{ old('name') }}" />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="photo" :value="__('Photo (optional)')" />
                <input id="photo" name="photo" type="file" accept="image/*"
                    class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#152A4E]/8 dark:file:bg-white/10 file:text-[#152A4E] dark:file:text-white hover:file:bg-[#152A4E]/15">
                <x-input-error :messages="$errors->get('photo')" class="mt-1" />
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
                <x-input-label for="specialization" :value="__('Specialization')" />
                <x-text-input id="specialization" name="specialization" type="text" class="mt-1 block w-full" value="{{ old('specialization') }}" />
                <x-input-error :messages="$errors->get('specialization')" class="mt-1" />
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
        </div>

        {{-- Types of training --}}
        <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div>
                    <h3 class="text-sm font-bold text-[#152A4E] dark:text-white">{{ __('Types of Training') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Every course this instructor can teach. At least one is required.') }}</p>
                </div>
                <button type="button" @click="trainingTypes.push({ training_type: '', certificate_code: '' })"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D] dark:hover:text-[#E2762D] transition">
                    + {{ __('Add training type') }}
                </button>
            </div>

            <div class="space-y-2">
                <template x-for="(row, index) in trainingTypes" :key="index">
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="flex-1 min-w-[12rem]">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1" x-text="index === 0 ? '{{ __('Type of Training') }}' : ''"></label>
                            <input type="text" :name="`training_types[${index}][training_type]`" x-model="row.training_type"
                                placeholder="{{ __('e.g. Incident Command System (ICS)') }}"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <div class="flex-1 min-w-[10rem]">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1" x-text="index === 0 ? '{{ __('Certificate Code') }}' : ''"></label>
                            <input type="text" :name="`training_types[${index}][certificate_code]`" x-model="row.certificate_code"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <button type="button" @click="trainingTypes.splice(index, 1)" x-show="trainingTypes.length > 1"
                            class="shrink-0 inline-flex items-center justify-center text-xs font-semibold text-red-600 hover:text-red-800 px-2 py-2.5 transition">
                            {{ __('Remove') }}
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Certifications --}}
        <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div>
                    <h3 class="text-sm font-bold text-[#152A4E] dark:text-white">{{ __('Certifications') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Optional. Each certification keeps its own scanned copy.') }}</p>
                </div>
                <button type="button" @click="certifications.push({ title: '', code: '', issued_on: '' })"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D] dark:hover:text-[#E2762D] transition">
                    + {{ __('Add certification') }}
                </button>
            </div>

            <p x-show="certifications.length === 0" class="text-xs text-gray-400">{{ __('None added.') }}</p>

            <div class="space-y-2">
                <template x-for="(row, index) in certifications" :key="index">
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="flex-1 min-w-[12rem]">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1" x-text="index === 0 ? '{{ __('Certification') }}' : ''"></label>
                            <input type="text" :name="`certifications[${index}][title]`" x-model="row.title"
                                placeholder="{{ __('e.g. Accredited ICS Instructor') }}"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <div class="w-36">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1" x-text="index === 0 ? '{{ __('Code') }}' : ''"></label>
                            <input type="text" :name="`certifications[${index}][code]`" x-model="row.code"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <div class="w-40">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1" x-text="index === 0 ? '{{ __('Issued') }}' : ''"></label>
                            <input type="date" :name="`certifications[${index}][issued_on]`" x-model="row.issued_on"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <div class="flex-1 min-w-[12rem]">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1" x-text="index === 0 ? '{{ __('Scanned copy') }}' : ''"></label>
                            <input type="file" :name="`certifications[${index}][file]`" accept=".pdf,.jpg,.jpeg,.png"
                                class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#152A4E]/8 dark:file:bg-white/10 file:text-[#152A4E] dark:file:text-white hover:file:bg-[#152A4E]/15">
                        </div>
                        <button type="button" @click="certifications.splice(index, 1)"
                            class="shrink-0 inline-flex items-center justify-center text-xs font-semibold text-red-600 hover:text-red-800 px-2 py-2.5 transition">
                            {{ __('Remove') }}
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Deployments --}}
        <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div>
                    <h3 class="text-sm font-bold text-[#152A4E] dark:text-white">{{ __('Deployments') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Optional. Add one row per deployment — more can be recorded later from their profile.') }}</p>
                </div>
                <button type="button" @click="deployments.push({ deployment: '', deployment_date: '', deployment_role: '', area: '', city: '' })"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D] dark:hover:text-[#E2762D] transition">
                    + {{ __('Add deployment') }}
                </button>
            </div>

            <p x-show="deployments.length === 0" class="text-xs text-gray-400">{{ __('None added.') }}</p>

            <div class="space-y-2">
                <template x-for="(row, index) in deployments" :key="index">
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="flex-1 min-w-[10rem]">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1" x-text="index === 0 ? '{{ __('Deployment') }}' : ''"></label>
                            <input type="text" :name="`deployments[${index}][deployment]`" x-model="row.deployment"
                                placeholder="{{ __('e.g. RDANA, PDNA, EOC') }}"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <div class="w-40">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1" x-text="index === 0 ? '{{ __('Date') }}' : ''"></label>
                            <input type="date" :name="`deployments[${index}][deployment_date]`" x-model="row.deployment_date"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <div class="flex-1 min-w-[9rem]">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1" x-text="index === 0 ? '{{ __('Role') }}' : ''"></label>
                            <input type="text" :name="`deployments[${index}][deployment_role]`" x-model="row.deployment_role"
                                placeholder="{{ __('e.g. Team Leader') }}"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <div class="flex-1 min-w-[9rem]">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1" x-text="index === 0 ? '{{ __('City') }}' : ''"></label>
                            <input type="text" :name="`deployments[${index}][city]`" x-model="row.city" list="ph-city-options" autocomplete="off"
                                placeholder="{{ __('Where they were sent') }}"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <div class="flex-1 min-w-[9rem]">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1" x-text="index === 0 ? '{{ __('Area (Agency / LGU)') }}' : ''"></label>
                            <input type="text" :name="`deployments[${index}][area]`" x-model="row.area"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <button type="button" @click="deployments.splice(index, 1)"
                            class="shrink-0 inline-flex items-center justify-center text-xs font-semibold text-red-600 hover:text-red-800 px-2 py-2.5 transition">
                            {{ __('Remove') }}
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                {{ __('Add Instructor') }}
            </button>
        </div>
    </form>
</x-chart-card>

@include('admin.partials.city-options')
