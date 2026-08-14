<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Instructors') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <!-- Add Instructor -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Add Instructor') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Instructor ratings are computed automatically from L1 Evaluation data once that feature is built — leave it blank for now.') }}</p>

                <form method="POST" action="{{ route('admin.instructors.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @csrf

                    <div>
                        <x-input-label for="name" :value="__('Instructor Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required value="{{ old('name') }}" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="training_type" :value="__('Type of Training')" />
                        <x-text-input id="training_type" name="training_type" type="text" class="mt-1 block w-full" required value="{{ old('training_type') }}" />
                        <x-input-error :messages="$errors->get('training_type')" class="mt-1" />
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

            <!-- Instructor Roster -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Instructor Roster') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('All instructors on file, across all regions.') }}</p>

                @if ($instructors->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No instructors on file yet.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Name') }}</th>
                                    <th class="py-2 pr-4">{{ __('Type of Training') }}</th>
                                    <th class="py-2 pr-4">{{ __('Certificate Code') }}</th>
                                    <th class="py-2 pr-4">{{ __('Deployment') }}</th>
                                    <th class="py-2 pr-4">{{ __('Agency / LGU') }}</th>
                                    <th class="py-2 pr-4">{{ __('Region') }}</th>
                                    <th class="py-2 pr-4">{{ __('Rating') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($instructors as $instructor)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $instructor->name }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->training_type }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->certificate_code ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->deployment ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->agency_organization ?? $instructor->lgu ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->region ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructor->rating ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5">
                        {{ $instructors->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
