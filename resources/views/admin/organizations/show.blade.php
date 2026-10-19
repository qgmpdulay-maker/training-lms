<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Manage Organization') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to Organizations') }}
            </a>

            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <x-chart-card
                :title="$organization->name"
                :subtitle="$organization->typeLabel().($organization->locationLabel() ? ' · '.$organization->locationLabel() : '')"
                body-class="p-6 sm:p-8">

                <form method="POST" action="{{ route('admin.organizations.update', $organization) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @csrf
                    @method('PATCH')
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Name') }}</label>
                        <input type="text" name="name" required value="{{ old('name', $organization->name) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Type') }}</label>
                        <select name="type" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                            @foreach ($typeLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $organization->type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Region') }}</label>
                        <select name="region" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="">{{ __('Not set') }}</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region }}" @selected(old('region', $organization->region) === $region)>{{ $region }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="edit-city" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('City / Municipality') }}</label>
                        @include('partials.city-autocomplete', ['id' => 'edit-city', 'value' => old('city', $organization->city ?? '')])
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Contact Person') }}</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $organization->contact_person) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Contact Email') }}</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $organization->contact_email) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Contact Number') }}</label>
                        <input type="text" name="contact_number" value="{{ old('contact_number', $organization->contact_number) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" class="inline-flex items-center bg-[#152A4E] text-white text-xs font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                            {{ __('Save Changes') }}
                        </button>
                    </div>
                </form>
            </x-chart-card>

            <x-chart-card
                :title="trans_choice(':count Member|:count Members', $organization->members->count(), ['count' => $organization->members->count()])"
                :subtitle="__('Assigned here only — nobody joins themselves, which is what makes a member trustworthy enough to act for this body.')"
                body-class="p-6 sm:p-8">

                @if ($organization->members->isNotEmpty())
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($organization->members as $member)
                            <li class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $member->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ $member->position ?: __('No position set') }} · {{ $member->email }}
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('admin.organizations.members.remove', [$organization, $member]) }}"
                                    onsubmit="return confirm('{{ __('Remove this member from the organization?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="shrink-0 text-xs font-semibold text-red-600 dark:text-red-400 hover:underline">{{ __('Remove') }}</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No members yet — tick people below to add them.') }}</p>
                @endif
            </x-chart-card>

            <x-chart-card
                :title="__('Add Members')"
                :subtitle="$organization->region
                    ? __('Participants in :region who are not yet in an organization. Tick everyone who belongs to this body.', ['region' => $organization->region])
                    : __('Participants not yet in an organization. Tick everyone who belongs to this body.')"
                body-class="p-6 sm:p-8">

                <form data-live-form data-live-section="candidates" data-live-target="organization-candidates"
                    method="GET" action="{{ route('admin.organizations.show', $organization) }}#organization-candidates"
                    class="flex gap-2 bg-gray-50 dark:bg-gray-700/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2 mb-6">
                    <input type="hidden" name="_section" value="candidates">
                    <div class="relative flex-1">
                        <input type="text" name="candidates_q" value="{{ $candidateSearch }}"
                            placeholder="{{ __('Search by name, email, or what they typed at signup...') }}"
                            class="w-full rounded-xl border-0 bg-white dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>
                </form>

                <div id="organization-candidates">
                    @include('admin.partials.organization-candidates')
                </div>
            </x-chart-card>

            <x-chart-card
                :title="__('Remove Organization')"
                :subtitle="__('Members are not deleted — they return to the unassigned list.')"
                border-class="border-red-100 dark:border-red-900/40"
                body-class="p-6 sm:p-8">

                <form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}"
                    onsubmit="return confirm('{{ __('Remove this organization?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 text-xs font-semibold rounded-lg px-4 py-2 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                        {{ __('Remove Organization') }}
                    </button>
                </form>
            </x-chart-card>

        </div>
    </div>

    @include('admin.partials.live-search-script')
</x-app-layout>
