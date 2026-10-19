{{--
    The graduate list. Rendered inline on first load and swapped in on its own
    by the live-search script as the admin types or changes the filter.
--}}
<div class="space-y-3">
    @forelse ($graduates as $graduate)
        @php
            $placedCity = \App\Support\CityDirectory::match($graduate->city, $graduate->region);
            // first() gives '' rather than null when there's no error.
            $cityError = $errors->getBag('city_'.$graduate->id)->first('city') ?: null;
        @endphp
        <div x-data="{ open: @js($cityError !== null) }" class="border border-gray-200 dark:border-gray-700 rounded-lg">
            <button type="button" @click="open = ! open"
                class="w-full flex items-center justify-between gap-4 px-4 py-3 text-left">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-[#152A4E] dark:text-white truncate">{{ $graduate->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ collect([$graduate->assignedOrganization?->name ?: $graduate->organization, $graduate->city, $graduate->region])->filter()->join(' · ') ?: __('No organization on file') }}
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    @unless ($placedCity)
                        <span class="inline-flex items-center text-[10px] font-semibold uppercase tracking-wide rounded-full border px-2 py-1 bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600">
                            {{ __('Not on map') }}
                        </span>
                    @endunless
                    @if ($graduate->deployments->isNotEmpty())
                        <span class="inline-flex items-center text-[10px] font-semibold uppercase tracking-wide rounded-full border px-2 py-1 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700">
                            {{ trans_choice(':count deployment|:count deployments', $graduate->deployments->count(), ['count' => $graduate->deployments->count()]) }}
                        </span>
                    @else
                        <span class="text-xs text-gray-400">{{ __('Not deployed') }}</span>
                    @endif
                    <svg class="w-4 h-4 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </button>

            <div x-show="open" x-cloak class="border-t border-gray-100 dark:border-gray-700 px-4 py-4 space-y-4">
                {{-- Where this graduate is counted on the Graduates by Location map. --}}
                <form method="POST" action="{{ route('admin.deployments.city', $graduate) }}"
                    class="flex flex-col sm:flex-row sm:items-end gap-3 pb-4 border-b border-gray-100 dark:border-gray-700">
                    @csrf
                    <div class="flex-1">
                        <label for="city-{{ $graduate->id }}" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                            {{ __('City') }}
                            <span class="font-normal text-gray-400">{{ __('— where they\'re counted on the dashboard map') }}</span>
                        </label>
                        <input id="city-{{ $graduate->id }}" type="text" name="city" list="ph-city-options" required autocomplete="off"
                            value="{{ $cityError ? old('city') : ($placedCity ? \App\Support\CityDirectory::label($placedCity) : $graduate->city) }}"
                            placeholder="{{ __('Start typing a city or municipality...') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        @if ($cityError)
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $cityError }}</p>
                        @elseif (! $placedCity && filled($graduate->city))
                            <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">{{ __('":city" isn\'t a city or municipality the map recognises — pick one from the list.', ['city' => $graduate->city]) }}</p>
                        @endif
                    </div>
                    <button type="submit" class="shrink-0 inline-flex items-center justify-center border border-[#152A4E] text-[#152A4E] dark:border-white/40 dark:text-white text-xs font-semibold rounded-lg px-5 py-2.5 hover:bg-[#152A4E]/5 dark:hover:bg-white/10 transition">
                        {{ __('Save City') }}
                    </button>
                </form>

                @if ($graduate->deployments->isNotEmpty())
                    <ul class="space-y-2">
                        @foreach ($graduate->deployments as $deployment)
                            <li class="flex items-start justify-between gap-3 text-sm bg-gray-50 dark:bg-gray-700/40 rounded-lg px-3 py-2">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-800 dark:text-gray-100">
                                        {{ $deployment->deployment }}
                                        @if ($deployment->deployment_role)
                                            <span class="text-gray-500 dark:text-gray-400">— {{ $deployment->deployment_role }}</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $deployment->deployment_date->format('M j, Y') }}
                                        @if ($deployment->city)
                                            · {{ $deployment->city }}
                                        @endif
                                        @if ($deployment->recordedBy)
                                            · {{ __('recorded by :name', ['name' => $deployment->recordedBy->name]) }}
                                        @endif
                                    </p>
                                    @if ($deployment->notes)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $deployment->notes }}</p>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('admin.deployments.destroy', $deployment) }}"
                                    onsubmit="return confirm('{{ __('Remove this deployment record?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="shrink-0 text-xs font-semibold text-red-600 dark:text-red-400 hover:underline">
                                        {{ __('Remove') }}
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('admin.deployments.store', $graduate) }}"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    @csrf
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Operation / Deployment') }}</label>
                        <input type="text" name="deployment" required placeholder="{{ __('e.g. Mayon Operations') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Date') }}</label>
                        <input type="date" name="deployment_date" required max="{{ now()->toDateString() }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Role (optional)') }}</label>
                        <input type="text" name="deployment_role" placeholder="{{ __('e.g. Team Leader') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    {{-- Where they were sent, which is not the same as the City
                         saved above: that one is where this graduate is based and
                         is what the Graduates by Location map counts them under. --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('City deployed to (optional)') }}</label>
                        <input type="text" name="city" list="ph-city-options" autocomplete="off" placeholder="{{ __('e.g. City of Legazpi, Albay') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                        <button type="submit" class="inline-flex items-center bg-[#152A4E] text-white text-xs font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                            {{ __('Record Deployment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-10">
            @if ($search !== '' || $status)
                {{ __('No graduates match that search.') }}
            @else
                {{ __('No graduates yet. Participants appear here once a training they attended is marked Completed.') }}
            @endif
        </p>
    @endforelse
</div>

<div class="mt-6 graduates-pagination">
    {{ $graduates->links() }}
</div>
