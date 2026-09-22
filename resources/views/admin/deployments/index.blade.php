<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Graduate Deployments') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="flex items-start gap-3 text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <x-chart-card
                :title="__('Graduate Deployments')"
                :subtitle="Auth::user()->isAdmin()
                    ? __('Record where a graduate has been deployed after finishing their training. Showing graduates in :region.', ['region' => Auth::user()->region ?? __('your region')])
                    : __('Record where a graduate has been deployed after finishing their training.')"
                body-class="p-6 sm:p-8">

                <form data-live-form data-live-section="graduates" data-live-target="deployment-graduates"
                    method="GET" action="{{ route('admin.deployments.index') }}#deployment-graduates"
                    class="flex flex-col sm:flex-row gap-2 bg-gray-50 dark:bg-gray-700/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2 mb-6">
                    <input type="hidden" name="_section" value="graduates">

                    <div class="relative flex-1">
                        <input type="text" name="q" value="{{ $search }}"
                            placeholder="{{ __('Search graduates by name, organization, or city...') }}"
                            class="w-full rounded-xl border-0 bg-white dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>

                    <div class="relative sm:w-52 shrink-0">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                        <select name="status"
                            class="w-full rounded-xl border-0 bg-white dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 pr-9 py-2.5 transition">
                            <option value="">{{ __('All Graduates') }}</option>
                            <option value="not_deployed" @selected($status === 'not_deployed')>{{ __('Not Deployed') }}</option>
                            <option value="deployed" @selected($status === 'deployed')>{{ __('Deployed') }}</option>
                        </select>
                    </div>
                </form>

                <div id="deployment-graduates">
                    @include('admin.partials.deployment-graduates')
                </div>
            </x-chart-card>

        </div>
    </div>

    @include('admin.partials.live-search-script')
</x-app-layout>
