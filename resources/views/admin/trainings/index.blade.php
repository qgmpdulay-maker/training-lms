<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Training') }}
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

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white">
                        {{ Auth::user()->isAdmin() ? __('Trainings in :region', ['region' => Auth::user()->region]) : __('Trainings') }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        @if (Auth::user()->isAdmin())
                            {{ __('Trainings you have scheduled for your region. Scheduling one needs no approval — it is set the moment you save it.') }}
                        @else
                            {{ __('Every training on record, most recent first. Scheduled by each Regional Admin; use Manage to edit one or mark it Completed.') }}
                        @endif
                    </p>
                </div>
                {{-- Scheduling belongs to the Regional Admin: they run the trainings in
                     their region and are already authorised to, so there's no approval
                     step and no Super Admin button here. --}}
                @if (Auth::user()->isAdmin())
                    <a href="{{ route('admin.trainings.create') }}"
                        class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition shrink-0">
                        {{ __('Schedule Training') }}
                    </a>
                @endif
            </div>

            <form method="GET" class="w-full">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                    <div class="relative flex-1 min-w-[14rem]">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('Search training, agency, venue, or contact…') }}"
                            class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                    </div>
                    <select name="status"
                        class="sm:w-48 shrink-0 rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm py-2.5 transition">
                        <option value="all" @selected($selectedStatus === 'all')>{{ __('All statuses') }}</option>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    {{-- A Regional Admin's list is already fixed to their own region. --}}
                    @unless (Auth::user()->isAdmin())
                        <select name="region"
                            class="sm:w-48 shrink-0 rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm py-2.5 transition">
                            <option value="">{{ __('All Regions') }}</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption }}" @selected($selectedRegion === $regionOption)>{{ $regionOption }}</option>
                            @endforeach
                        </select>
                    @endunless
                    <button type="submit"
                        class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                        {{ __('Search') }}
                    </button>
                </div>
            </form>

            <x-chart-card body-class="p-6 sm:p-8" :title="__('Scheduled Trainings')">
                {{-- Same table the Super Admin's Summary renders, so a training reads
                     identically wherever it's listed. --}}
                @include('admin.partials.summary-training-requests')
            </x-chart-card>
        </div>
    </div>
</x-app-layout>
