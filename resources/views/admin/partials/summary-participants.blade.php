@if ($participants->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
        {{ $participantSearch !== '' ? __('No participants match your search.') : __('No participants registered yet.') }}
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <th class="py-2 pr-4"></th>
                    <th class="py-2 pr-4">{{ __('Name') }}</th>
                    <th class="py-2 pr-4">{{ __('Age / Sex') }}</th>
                    <th class="py-2 pr-4">{{ __('Participant Type') }}</th>
                    <th class="py-2 pr-4">{{ __('Agency / Organization') }}</th>
                    <th class="py-2 pr-4">{{ __('Certificate') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($participants as $participant)
                    <tr>
                        <td class="py-3 pr-4">
                            @if ($participant->picture)
                                <img src="{{ asset('storage/'.$participant->picture) }}" alt="{{ $participant->name }}" class="w-9 h-9 object-cover rounded-full border border-gray-200 dark:border-gray-600">
                            @else
                                <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600"></div>
                            @endif
                        </td>
                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $participant->name }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->age }} / {{ $participant->sex }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->participant_type }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $participant->organization ?: $participant->agency }}</td>
                        <td class="py-3 pr-4">
                            @if ($participant->certificates->isEmpty())
                                <span class="text-gray-400">—</span>
                            @else
                                <div x-data="{ certModalOpen: false }">
                                    <button type="button" @click="certModalOpen = true"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-full border px-2.5 py-1 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-[#152A4E] dark:hover:border-white transition">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-[#E2762D]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1.5a5.25 5.25 0 100 10.5 5.25 5.25 0 000-10.5zM7.5 12.94a6.723 6.723 0 01-2.25-.66v6.472a.75.75 0 001.085.67L9 17.99l2.665 1.433a.75.75 0 00.67 0L15 17.99l2.665 1.433a.75.75 0 001.085-.671v-6.471a6.723 6.723 0 01-2.25.659V15a.75.75 0 01-1.5 0v-2.152a6.76 6.76 0 01-3-.001V15a.75.75 0 01-1.5 0v-2.06z"/></svg>
                                        {{ trans_choice(':count Certificate|:count Certificates', $participant->certificates->count(), ['count' => $participant->certificates->count()]) }}
                                    </button>

                                    <template x-teleport="body">
                                        <div x-show="certModalOpen" x-cloak
                                            class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                            @keydown.escape.window="certModalOpen = false">
                                            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" x-show="certModalOpen" x-transition.opacity @click="certModalOpen = false"></div>

                                            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col"
                                                x-show="certModalOpen"
                                                x-transition:enter="transition ease-out duration-150"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100">
                                                <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
                                                    <div class="min-w-0">
                                                        <h3 class="font-bold text-[#152A4E] dark:text-white">{{ __('Certificates') }}</h3>
                                                        <p class="text-xs text-gray-400 truncate">{{ $participant->name }}</p>
                                                    </div>
                                                    <button type="button" @click="certModalOpen = false"
                                                        class="shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                <div class="overflow-y-auto p-6 space-y-4">
                                                    @foreach ($participant->certificates as $certificate)
                                                        @php $isImage = $certificate->certificate_file_path && preg_match('/\.(jpe?g|png|gif|webp)$/i', $certificate->certificate_file_path); @endphp
                                                        <div class="flex gap-5 border border-gray-100 dark:border-gray-700 rounded-lg p-5">
                                                            @if ($certificate->certificate_file_path)
                                                                <div class="shrink-0 w-36 h-36 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700 flex items-center justify-center">
                                                                    @if ($isImage)
                                                                        <a href="{{ asset('storage/'.$certificate->certificate_file_path) }}" target="_blank" class="block w-full h-full">
                                                                            <img src="{{ asset('storage/'.$certificate->certificate_file_path) }}" alt="{{ $certificate->training_title }}" class="w-full h-full object-cover hover:opacity-90 transition">
                                                                        </a>
                                                                    @else
                                                                        <a href="{{ asset('storage/'.$certificate->certificate_file_path) }}" target="_blank" class="flex flex-col items-center gap-1 text-red-400 hover:text-red-500 transition">
                                                                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                                            </svg>
                                                                            <span class="text-[10px] font-semibold uppercase">{{ __('PDF') }}</span>
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            @endif

                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex items-start justify-between gap-3">
                                                                    <div class="min-w-0">
                                                                        <p class="text-base font-semibold text-[#152A4E] dark:text-white leading-snug">{{ $certificate->training_title }}</p>
                                                                        <p class="text-xs text-gray-400 mt-0.5">{{ $certificate->preferred_date?->format('F j, Y') }}</p>
                                                                    </div>
                                                                    @unless ($certificate->certificate_file_path)
                                                                        <span class="shrink-0 inline-flex items-center gap-1 text-[11px] text-gray-400 dark:text-gray-500">
                                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                                            </svg>
                                                                            {{ __('No file yet') }}
                                                                        </span>
                                                                    @endunless
                                                                </div>

                                                                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                                                    @if ($certificate->certificate_remarks)
                                                                        <span class="inline-flex items-center text-[11px] font-semibold rounded-full border px-2 py-0.5 bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600">
                                                                            {{ $certificateRemarksLabels[$certificate->certificate_remarks] ?? $certificate->certificate_remarks }}
                                                                        </span>
                                                                    @endif
                                                                    @if ($certificate->certificate_code)
                                                                        <span class="text-[11px] text-gray-400">{{ $certificate->certificate_code }}</span>
                                                                    @endif
                                                                </div>

                                                                @if ($certificate->region && $participant->region && $certificate->region !== $participant->region)
                                                                    <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-1.5">{{ __('Earned in :region', ['region' => $certificate->region]) }}</p>
                                                                @endif

                                                                @if ($certificate->certificate_file_path)
                                                                    <a href="{{ asset('storage/'.$certificate->certificate_file_path) }}" target="_blank"
                                                                        class="inline-flex items-center gap-1.5 mt-2 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-full pl-2 pr-2.5 py-1 hover:bg-green-100 dark:hover:bg-green-900/50 transition">
                                                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                        </svg>
                                                                        {{ __('View Full Certificate') }}
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5 participants-pagination">
        {{ $participants->links() }}
    </div>
@endif
