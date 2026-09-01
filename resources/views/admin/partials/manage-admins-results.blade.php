@if ($admins->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ $adminSearch !== '' ? __('No admins match your search.') : __('No admin accounts yet.') }}
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
