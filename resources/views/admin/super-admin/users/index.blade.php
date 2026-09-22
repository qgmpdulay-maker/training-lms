<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Users') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <ul class="space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('tempPasswords'))
                @php $tempPasswords = session('tempPasswords'); @endphp
                <div x-data="{ dismissed: false, copiedAll: false, copiedIndex: null }" x-show="! dismissed"
                    class="bg-white dark:bg-gray-800 rounded-xl border-2 border-amber-300 dark:border-amber-700 shadow-sm overflow-hidden">
                    <div class="flex items-start gap-3 px-5 py-4 bg-amber-50 dark:bg-amber-900/30 border-b border-amber-200 dark:border-amber-800">
                        <svg class="w-5 h-5 shrink-0 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-amber-900 dark:text-amber-200 text-sm">
                                {{ trans_choice(':count new password was generated|:count new passwords were generated', count($tempPasswords), ['count' => count($tempPasswords)]) }}
                            </h3>
                            <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">{{ __('Relay these to the account owners now — they will not be shown again.') }}</p>
                        </div>
                        <div class="shrink-0 flex items-center gap-2">
                            @if (count($tempPasswords) > 1)
                                <button type="button"
                                    @click="navigator.clipboard.writeText(@js(collect($tempPasswords)->map(fn ($p) => $p['name'].': '.$p['password'])->implode(PHP_EOL))); copiedAll = true; setTimeout(() => copiedAll = false, 2000)"
                                    class="text-xs font-semibold rounded-md px-3 py-1.5 border border-amber-300 dark:border-amber-700 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition whitespace-nowrap">
                                    <span x-show="!copiedAll">{{ __('Copy All') }}</span>
                                    <span x-show="copiedAll" x-cloak>{{ __('Copied!') }}</span>
                                </button>
                            @endif
                            <button type="button" @click="dismissed = true" title="{{ __('Dismiss') }}"
                                class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-300">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="divide-y divide-amber-100 dark:divide-amber-900/40">
                        @foreach ($tempPasswords as $i => $entry)
                            <div class="flex items-center justify-between gap-3 px-5 py-3">
                                <span class="text-sm font-medium text-[#152A4E] dark:text-white truncate">{{ $entry['name'] }}</span>
                                <div class="flex items-center gap-2 shrink-0">
                                    <code class="text-sm font-bold tracking-wide bg-amber-50/60 dark:bg-gray-900 border border-amber-200 dark:border-amber-700 rounded-md px-2.5 py-1">{{ $entry['password'] }}</code>
                                    <button type="button"
                                        @click="navigator.clipboard.writeText(@js($entry['password'])); copiedIndex = {{ $i }}; setTimeout(() => copiedIndex = null, 2000)"
                                        class="text-xs font-semibold rounded-md px-2.5 py-1 border border-amber-300 dark:border-amber-700 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition">
                                        <span x-show="copiedIndex !== {{ $i }}">{{ __('Copy') }}</span>
                                        <span x-show="copiedIndex === {{ $i }}" x-cloak>{{ __('Copied!') }}</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Pending Approvals -->
            @if ($pendingAccounts->isNotEmpty())
                <x-chart-card body-class="p-6 sm:p-8"
                    border-class="border-amber-200 dark:border-amber-800"
                    :title="__('Pending Approvals')">
                    <x-slot:action>
                        <span class="inline-flex items-center text-xs font-bold rounded-full px-2.5 py-1 bg-amber-400 text-[#152A4E]">
                            {{ $pendingAccounts->count() }}
                        </span>
                    </x-slot:action>

                    <div x-data="{ activeRegion: @js($pendingAccountsByRegion->keys()->first()) }">
                        <div class="flex items-center gap-1 overflow-x-auto bg-gray-100 dark:bg-gray-900/40 rounded-xl p-1.5">
                            @foreach ($pendingAccountsByRegion as $region => $accounts)
                                <button type="button" @click="activeRegion = @js($region)"
                                    :class="activeRegion === @js($region)
                                        ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition whitespace-nowrap">
                                    {{ $region }}
                                    <span :class="activeRegion === @js($region)
                                            ? 'bg-[#152A4E]/10 text-[#152A4E] dark:bg-white/15 dark:text-white px-1.5 py-0.5 rounded-full text-xs font-semibold'
                                            : 'text-gray-400 dark:text-gray-500 text-xs font-normal'">
                                        {{ $accounts->count() }}
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        @foreach ($pendingAccountsByRegion as $region => $accounts)
                            <div x-show="activeRegion === @js($region)" x-cloak class="mt-5 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                            <th class="py-2 pr-4">{{ __('Name') }}</th>
                                            <th class="py-2 pr-4">{{ __('Email') }}</th>
                                            <th class="py-2 pr-4">{{ __('Participant Type') }}</th>
                                            <th class="py-2 pr-4">{{ __('Agency / City') }}</th>
                                            <th class="py-2 pr-4">{{ __('Registered') }}</th>
                                            <th class="py-2 pr-4"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @foreach ($accounts as $account)
                                            <tr>
                                                <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $account->name }}</td>
                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $account->email }}</td>
                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $account->participant_type ?? '—' }}</td>
                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $account->agency ?? $account->city ?? '—' }}</td>
                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $account->created_at->format('M j, Y') }}</td>
                                                <td class="py-3 pr-4 text-right whitespace-nowrap">
                                                    {{-- Security: the applicant's name is typed by the public, so it goes
                                                         through Js::from(), which turns it into a safely escaped JavaScript
                                                         string. Pasting it straight inside confirm('...') let a name
                                                         containing a quote run its own JavaScript in the Super Admin's
                                                         browser (stored XSS). --}}
                                                    <form method="POST" action="{{ route('admin.users.reject', $account) }}" class="inline"
                                                        onsubmit="return confirm({{ Js::from(__('Reject the account for :name?', ['name' => $account->name])) }});">
                                                        @csrf
                                                        <button type="submit"
                                                            class="inline-flex items-center justify-center border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-xs font-semibold rounded-md px-3 py-1.5 hover:bg-red-50 dark:hover:bg-red-900/30 transition whitespace-nowrap">
                                                            {{ __('Reject') }}
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.users.approve', $account) }}" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-3 py-1.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                                            {{ __('Approve') }}
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                </x-chart-card>
            @endif

            <!-- Current Admins -->
            <x-chart-card body-class="p-6 sm:p-8"
                :title="__('Current Admins')"
                :subtitle="__('Regional admins and super admins with access to the admin dashboard.')">

                <form data-live-form data-live-section="admins" data-live-target="admins-results"
                    method="GET" action="{{ route('admin.users.index') }}" class="w-full mb-5">
                    <input type="hidden" name="_section" value="admins">
                    <input type="hidden" name="participants_q" value="{{ $participantSearch }}">
                    <div class="flex items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                        <div class="relative flex-1">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <input type="text" name="admins_q" value="{{ $adminSearch }}" placeholder="{{ __('Search name, email, or organization…') }}"
                                class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                        </div>
                        <button type="submit"
                            class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                    </div>
                    @if ($adminSearch !== '')
                        <a href="{{ route('admin.users.index', array_filter(['participants_q' => $participantSearch ?: null])) }}"
                            class="inline-block mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </form>

                <div id="admins-results">
                    @include('admin.partials.manage-admins-results')
                </div>
            </x-chart-card>

            <!-- Participants -->
            <x-chart-card body-class="p-6 sm:p-8"
                :title="__('Participants')"
                :subtitle="__('Assign participants to an organization, or elevate one to Regional Admin.')">

                <form data-live-form data-live-section="participants" data-live-target="participants-results"
                    method="GET" action="{{ route('admin.users.index') }}" class="w-full mb-5">
                    <input type="hidden" name="_section" value="participants">
                    <input type="hidden" name="admins_q" value="{{ $adminSearch }}">
                    <div class="flex items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                        <div class="relative flex-1">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <input type="text" name="participants_q" value="{{ $participantSearch }}" placeholder="{{ __('Search name, email, or organization…') }}"
                                class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                        </div>
                        <button type="submit"
                            class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                    </div>
                    @if ($participantSearch !== '')
                        <a href="{{ route('admin.users.index', array_filter(['admins_q' => $adminSearch ?: null])) }}"
                            class="inline-block mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </form>

                <div id="participants-results">
                    @include('admin.partials.manage-participants-results')
                </div>
            </x-chart-card>

        </div>
    </div>

    @include('admin.partials.live-search-script')
</x-app-layout>
