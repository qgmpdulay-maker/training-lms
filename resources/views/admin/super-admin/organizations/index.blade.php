<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Organizations') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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

            <div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">
                <x-chart-card
                    :title="__('Add an Organization')"
                    :subtitle="__('LGUs, national government agencies, academe bodies, and teams.')"
                    body-class="p-0">

                    <x-slot:action>
                        {{-- Styled for the dark band: white field, navy text. --}}
                        <button type="button" @click="open = ! open"
                            class="inline-flex items-center gap-1.5 rounded-md bg-white px-4 py-2 text-xs font-semibold text-[#152A4E] hover:bg-white/90 transition">
                            <span x-text="open ? '{{ __('Cancel') }}' : '{{ __('Add Organization') }}'"></span>
                            <svg class="w-3.5 h-3.5 transition" :class="open && 'rotate-45'" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </button>
                    </x-slot:action>

                    <div x-show="open" x-cloak class="p-6 sm:p-8">
                        <form method="POST" action="{{ route('admin.organizations.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @csrf
                            <div class="lg:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Name') }}</label>
                                <input type="text" name="name" required value="{{ old('name') }}" placeholder="{{ __('e.g. Municipality of Fictional Town') }}"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Type') }}</label>
                                <select name="type" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                                    @foreach ($typeLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Region') }}</label>
                                <select name="region" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                                    <option value="">{{ __('Not set') }}</option>
                                    @foreach ($regions as $region)
                                        <option value="{{ $region }}" @selected(old('region') === $region)>{{ $region }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="new-city" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('City / Municipality') }}</label>
                                @include('partials.city-autocomplete', ['id' => 'new-city', 'value' => old('city', '')])
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Contact Person') }}</label>
                                <input type="text" name="contact_person" value="{{ old('contact_person') }}"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3 flex justify-end">
                                <button type="submit" class="inline-flex items-center bg-[#152A4E] text-white text-xs font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                                    {{ __('Add Organization') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </x-chart-card>
            </div>

            <x-chart-card
                :title="__('All Organizations')"
                :subtitle="trans_choice(':count organization on file|:count organizations on file', $organizations->total(), ['count' => $organizations->total()])"
                body-class="p-6 sm:p-8">
                <form method="GET" action="{{ route('admin.organizations.index') }}"
                    class="flex flex-col sm:flex-row gap-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-2 mb-6">
                    <div class="relative flex-1">
                        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('e.g. Municipality of, MDRRMO, Quezon City') }}"
                            class="w-full rounded-xl border-0 bg-gray-50 dark:bg-gray-700 dark:text-white focus:bg-white dark:focus:bg-gray-600 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>

                    <div class="relative sm:w-52 shrink-0">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                        <select name="type" onchange="this.form.submit()"
                            class="w-full rounded-xl border-0 bg-gray-50 dark:bg-gray-700 dark:text-white focus:bg-white dark:focus:bg-gray-600 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 pr-9 py-2.5 transition">
                            <option value="">{{ __('All Types') }}</option>
                            @foreach ($typeLabels as $value => $label)
                                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="relative sm:w-44 shrink-0">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                        <select name="region" onchange="this.form.submit()"
                            class="w-full rounded-xl border-0 bg-gray-50 dark:bg-gray-700 dark:text-white focus:bg-white dark:focus:bg-gray-600 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 pr-9 py-2.5 transition">
                            <option value="">{{ __('All Regions') }}</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region }}" @selected($selectedRegion === $region)>{{ $region }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <th class="py-3 pr-4">{{ __('Name') }}</th>
                                <th class="py-3 pr-4">{{ __('Type') }}</th>
                                <th class="py-3 pr-4">{{ __('Location') }}</th>
                                <th class="py-3 pr-4">{{ __('Members') }}</th>
                                <th class="py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($organizations as $organization)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-gray-800 dark:text-gray-100">{{ $organization->name }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="inline-flex items-center text-[10px] font-semibold uppercase tracking-wide rounded-full border px-2 py-1 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600">
                                            {{ $organization->shortTypeLabel() }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 text-gray-500 dark:text-gray-400">{{ $organization->locationLabel() ?: '—' }}</td>
                                    <td class="py-3 pr-4 text-gray-500 dark:text-gray-400">{{ $organization->members_count }}</td>
                                    <td class="py-3 text-right">
                                        <a href="{{ route('admin.organizations.show', $organization) }}" class="text-xs font-semibold text-[#152A4E] dark:text-blue-300 hover:underline">
                                            {{ __('Manage') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('No organizations yet. Add the first one above.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">{{ $organizations->links() }}</div>
            </x-chart-card>

        </div>
    </div>
</x-app-layout>
