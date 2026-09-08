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
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-amber-200 dark:border-amber-800 shadow-sm p-6 sm:p-8">
                    <div class="flex items-center gap-2 mb-1">
                        <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">{{ __('Pending Approvals') }}</h2>
                        <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700">
                            {{ $pendingAccounts->count() }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                        {{ __('These accounts have verified their email and are waiting for a decision before they can log in. Grouped by region — only OCD Personnel have a known region at this stage, so everyone else falls under "Unspecified Region" for now.') }}
                    </p>

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
                                                    <form method="POST" action="{{ route('admin.users.reject', $account) }}" class="inline"
                                                        onsubmit="return confirm('{{ __('Reject the account for :name?', ['name' => $account->name]) }}');">
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
                </div>
            @endif

            <!-- Current Admins -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between flex-wrap gap-3 mb-1">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">{{ __('Current Admins') }}</h2>
                    <form data-live-form data-live-section="admins" data-live-target="admins-results"
                        method="GET" action="{{ route('admin.users.index') }}" class="flex items-center flex-wrap gap-2">
                        <input type="hidden" name="_section" value="admins">
                        <input type="hidden" name="participants_q" value="{{ $participantSearch }}">
                        <input type="text" name="admins_q" value="{{ $adminSearch }}" placeholder="{{ __('Search name, email, or organization…') }}"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-72">
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                        @if ($adminSearch !== '')
                            <a href="{{ route('admin.users.index', array_filter(['participants_q' => $participantSearch ?: null])) }}"
                                class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </form>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Regional admins and super admins with access to the admin dashboard.') }}</p>

                <div id="admins-results">
                    @include('admin.partials.manage-admins-results')
                </div>
            </div>

            <!-- Participants -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between flex-wrap gap-3 mb-1">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">{{ __('Participants') }}</h2>
                    <form data-live-form data-live-section="participants" data-live-target="participants-results"
                        method="GET" action="{{ route('admin.users.index') }}" class="flex items-center flex-wrap gap-2">
                        <input type="hidden" name="_section" value="participants">
                        <input type="hidden" name="admins_q" value="{{ $adminSearch }}">
                        <input type="text" name="participants_q" value="{{ $participantSearch }}" placeholder="{{ __('Search name, email, or organization…') }}"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E] w-72">
                        <button type="submit"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition whitespace-nowrap">
                            {{ __('Search') }}
                        </button>
                        @if ($participantSearch !== '')
                            <a href="{{ route('admin.users.index', array_filter(['admins_q' => $adminSearch ?: null])) }}"
                                class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </form>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Elevate a participant to Regional Admin by assigning them a region.') }}</p>

                <div id="participants-results">
                    @include('admin.partials.manage-participants-results')
                </div>
            </div>

        </div>
    </div>

    @include('admin.partials.live-search-script')
</x-app-layout>
