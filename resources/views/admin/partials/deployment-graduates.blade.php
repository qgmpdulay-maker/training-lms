{{--
    The graduate list. Rendered inline on first load and swapped in on its own
    by the live-search script as the admin types or changes a filter, so the
    counts at the top follow the region filter too.
--}}
@php
    $tiles = [
        ['label' => __('Graduates'), 'value' => $counts['total'], 'class' => 'text-[#152A4E] dark:text-white'],
        ['label' => __('Deployed'), 'value' => $counts['deployed'], 'class' => 'text-green-700 dark:text-green-400'],
        ['label' => __('Not Deployed'), 'value' => $counts['not_deployed'], 'class' => 'text-gray-600 dark:text-gray-300'],
    ];
    // Only Super Admin has a region filter to carry along; for a Regional
    // Admin $region is their own and the controller applies it regardless.
    $regionParam = Auth::user()->isAdmin() ? [] : array_filter(['region' => $region]);
@endphp

<div class="grid grid-cols-3 gap-3 mb-4">
    @foreach ($tiles as $tile)
        <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40 px-4 py-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $tile['label'] }}</p>
            <p class="text-xl font-semibold {{ $tile['class'] }}">{{ number_format($tile['value']) }}</p>
        </div>
    @endforeach
</div>

@if ($status === 'no_city')
    <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3 mb-4">
        <span>{{ __('Showing only graduates with no home city on the dashboard map.') }}</span>
        <a href="{{ route('admin.deployments.index', $regionParam) }}" class="text-xs font-semibold hover:underline">{{ __('Show all graduates') }}</a>
    </div>
@elseif ($counts['no_city'] > 0)
    <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3 mb-4">
        <span>{{ trans_choice(':count graduate has no home city yet, so the dashboard map leaves them out.|:count graduates have no home city yet, so the dashboard map leaves them out.', $counts['no_city'], ['count' => number_format($counts['no_city'])]) }}</span>
        <a href="{{ route('admin.deployments.index', [...$regionParam, 'status' => 'no_city']) }}" class="text-xs font-semibold hover:underline">{{ __('Show them') }}</a>
    </div>
@endif

<div class="space-y-3">
    @forelse ($graduates as $graduate)
        @php
            $placedCity = \App\Support\CityDirectory::match($graduate->city, $graduate->region);
            // first() gives '' rather than null when there's no error.
            $cityError = $errors->getBag('city_'.$graduate->id)->first('city') ?: null;
            $latest = $graduate->deployments->first();
        @endphp
        <div x-data="{ open: @js($cityError !== null) }" class="border border-gray-200 dark:border-gray-700 rounded-lg">
            <button type="button" @click="open = ! open" :aria-expanded="open"
                class="w-full flex items-center justify-between gap-4 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/30 rounded-lg transition">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-[#152A4E] dark:text-white truncate">{{ $graduate->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ collect([$graduate->assignedOrganization?->name ?: $graduate->organization, $graduate->region])->filter()->join(' · ') ?: __('No organization on file') }}
                    </p>
                    @if ($latest)
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                            {{ __('Latest: :operation, :date', ['operation' => $latest->deployment, 'date' => $latest->deployment_date->format('M j, Y')]) }}
                        </p>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @unless ($placedCity)
                        <span class="hidden sm:inline-flex items-center text-[10px] font-semibold uppercase tracking-wide rounded-full border px-2 py-1 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700">
                            {{ __('No home city') }}
                        </span>
                    @endunless
                    @if ($graduate->deployments->isNotEmpty())
                        <span class="inline-flex items-center text-[10px] font-semibold uppercase tracking-wide rounded-full border px-2 py-1 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-700">
                            {{ __('Deployed') }} · {{ $graduate->deployments->count() }}
                        </span>
                    @else
                        <span class="inline-flex items-center text-[10px] font-semibold uppercase tracking-wide rounded-full border px-2 py-1 bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600">
                            {{ __('Not deployed') }}
                        </span>
                    @endif
                    <svg class="w-4 h-4 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </button>

            <div x-show="open" x-cloak class="border-t border-gray-100 dark:border-gray-700 px-4 py-4 space-y-5">
                {{-- 1. What's already on record. --}}
                <section>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">{{ __('Deployment history') }}</h4>
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
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No deployments recorded yet.') }}</p>
                    @endif
                </section>

                {{-- 2. Adding to it — the main thing this page is for. --}}
                <section class="rounded-lg border border-[#152A4E]/15 dark:border-white/10 bg-[#152A4E]/[0.03] dark:bg-white/5 p-4">
                    <h4 class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ __('Record a new deployment') }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ __('Add one after the graduate has been sent out. Pick several cities if they served in more than one place.') }}</p>

                    <form method="POST" action="{{ route('admin.deployments.store', $graduate) }}"
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        @csrf
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Operation / Deployment') }}</label>
                            <input type="text" name="deployment" required placeholder="{{ __('e.g. Mayon Operations') }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Date deployed') }}</label>
                            <input type="date" name="deployment_date" required max="{{ now()->toDateString() }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Role (optional)') }}</label>
                            <input type="text" name="deployment_role" placeholder="{{ __('e.g. Team Leader') }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        {{-- Where they were sent, which is not the same as the
                             home city below: that one is where this graduate
                             lives and is what the dashboard map counts. --}}
                        <div class="sm:col-span-2 lg:col-span-4">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Cities deployed to (optional)') }}</label>
                            @include('partials.city-picker', [
                                'name' => 'cities[]',
                                'multiple' => true,
                                'label' => __('City deployed to'),
                            ])
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">{{ __('Notes (optional)') }}</label>
                            <input type="text" name="notes" maxlength="1000"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                            <button type="submit" class="inline-flex items-center bg-[#152A4E] text-white text-xs font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                                {{ __('Record Deployment') }}
                            </button>
                        </div>
                    </form>
                </section>

                {{-- 3. Where they live, for the Graduates by Location map. Kept
                     last and apart, since it's about the person rather than
                     any one deployment. --}}
                <section class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Home city') }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ __('Where this graduate lives, not where they were deployed. It decides where they\'re counted on the dashboard map.') }}</p>
                    <form method="POST" action="{{ route('admin.deployments.city', $graduate) }}"
                        class="flex flex-col sm:flex-row sm:items-start gap-3">
                        @csrf
                        <div class="flex-1">
                            @include('partials.city-picker', [
                                'name' => 'city',
                                'selected' => $placedCity ? [\App\Support\CityDirectory::label($placedCity)] : [],
                                'label' => __('Home city'),
                            ])
                            @if ($cityError)
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $cityError }}</p>
                            @elseif (! $placedCity && filled($graduate->city))
                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">{{ __('":city" isn\'t a city or municipality the map recognises — pick one from the list.', ['city' => $graduate->city]) }}</p>
                            @endif
                        </div>
                        <button type="submit" class="shrink-0 inline-flex items-center justify-center border border-[#152A4E] text-[#152A4E] dark:border-white/40 dark:text-white text-xs font-semibold rounded-lg px-5 py-2.5 hover:bg-[#152A4E]/5 dark:hover:bg-white/10 transition">
                            {{ __('Save Home City') }}
                        </button>
                    </form>
                </section>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-10">
            @if ($search !== '' || $status || $city)
                {{ __('No graduates match that search.') }}
            @elseif ($region && ! Auth::user()->isAdmin())
                {{ __('No graduates in :region yet.', ['region' => $region]) }}
            @else
                {{ __('No graduates yet. Participants appear here once a training they attended is marked Completed.') }}
            @endif
        </p>
    @endforelse
</div>

<div class="mt-6 graduates-pagination">
    {{ $graduates->links() }}
</div>
