<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Admin Accounts') }}
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

            <!-- Current Admins -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Current Admins') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Regional admins and super admins with access to the admin dashboard.') }}</p>

                @if ($admins->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No admin accounts yet.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Name') }}</th>
                                    <th class="py-2 pr-4">{{ __('Email') }}</th>
                                    <th class="py-2 pr-4">{{ __('Role') }}</th>
                                    <th class="py-2 pr-4">{{ __('Region') }}</th>
                                    <th class="py-2 pr-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($admins as $admin)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $admin->name }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $admin->email }}</td>
                                        <td class="py-3 pr-4">
                                            @if ($admin->isSuperAdmin())
                                                <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1 bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700">
                                                    {{ __('Super Admin') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700">
                                                    {{ __('Admin') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $admin->region ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-right">
                                            @if ($admin->isAdmin())
                                                <form method="POST" action="{{ route('admin.users.demote', $admin) }}" x-data
                                                    @submit="if (! confirm('Return ' + @js($admin->name) + ' to a participant account?')) $event.preventDefault()">
                                                    @csrf
                                                    <button type="submit"
                                                        class="inline-flex items-center justify-center border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-xs font-semibold rounded-md px-3 py-1.5 hover:bg-red-50 dark:hover:bg-red-900/30 transition whitespace-nowrap">
                                                        {{ __('Demote') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Participants -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Participants') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Elevate a participant to Regional Admin by assigning them a region.') }}</p>

                @if ($participants->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No participant accounts yet.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Name') }}</th>
                                    <th class="py-2 pr-4">{{ __('Email') }}</th>
                                    <th class="py-2 pr-4">{{ __('Organization') }}</th>
                                    <th class="py-2 pr-4">{{ __('Make Admin For') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($participants as $participant)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $participant->name }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->email }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->organization }}</td>
                                        <td class="py-3 pr-4">
                                            <form method="POST" action="{{ route('admin.users.promote', $participant) }}" class="flex items-center gap-2" x-data
                                                @submit="if (! confirm('Make ' + @js($participant->name) + ' a Regional Admin?')) $event.preventDefault()">
                                                @csrf
                                                <select name="region" required
                                                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                    <option value="" disabled selected>{{ __('Select region') }}</option>
                                                    @foreach ($regions as $region)
                                                        <option value="{{ $region }}">{{ $region }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit"
                                                    class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-3 py-1.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                                    {{ __('Make Admin') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5">
                        {{ $participants->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
