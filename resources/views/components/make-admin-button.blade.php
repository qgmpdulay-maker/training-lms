@props(['account', 'regions'])

{{--
    Promoting someone to Regional Admin is a privilege grant, so it deliberately
    does NOT sit in the row as a one-click button any more. It opens the same
    kind of modal the Reset Password action uses: the region is chosen inside,
    the submit stays disabled until one is picked, and the person's name, email
    and organization are shown so a stray click on the wrong row can't quietly
    promote a random participant.

    Re-opens itself if this account's own promotion just failed validation,
    identified by a hidden `_promote_for` field — $errors is shared across the
    page, so every row's modal would otherwise think the error was its own.
--}}
@php
    $isReopening = old('_promote_for') == $account->id;
@endphp
<div class="inline-block" x-data="{
        open: {{ $isReopening ? 'true' : 'false' }},
        region: '{{ old('_promote_for') == $account->id ? old('region', '') : '' }}',
    }">
    {{-- Outlined, not filled: it shouldn't compete with the benign "Save" on
         the organization assignment sitting beside it in the same row. --}}
    <button type="button" @click="open = true"
        class="inline-flex items-center justify-center border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-xs font-semibold rounded-md px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">
        {{ __('Make Admin') }}
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="open = false">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" x-show="open" x-transition.opacity @click="open = false"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md"
                x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">
                <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="min-w-0">
                        <h3 class="font-bold text-[#152A4E] dark:text-white">{{ __('Make Regional Admin') }}</h3>
                        <p class="text-xs text-gray-400 truncate">{{ __('This changes what this account can see and do.') }}</p>
                    </div>
                    <button type="button" @click="open = false"
                        class="shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    {{-- Who this actually is — the guard against promoting the wrong row. --}}
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 px-4 py-3 mb-5">
                        <p class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ $account->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 break-all">{{ $account->email }}</p>
                        @if ($account->assignedOrganization)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $account->assignedOrganization->name }}</p>
                        @elseif ($account->organization)
                            <p class="text-xs text-gray-400 italic mt-1">{{ $account->organization }} · {{ __('self-declared') }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.users.promote', $account) }}">
                        @csrf
                        <input type="hidden" name="_promote_for" value="{{ $account->id }}">

                        <label for="promote-region-{{ $account->id }}" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                            {{ __('Region they will administer') }}
                        </label>
                        <select id="promote-region-{{ $account->id }}" name="region" required x-model="region"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="" disabled>{{ __('Select a region') }}</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region }}">{{ $region }}</option>
                            @endforeach
                        </select>
                        @if ($isReopening)
                            <x-input-error :messages="$errors->get('region')" class="mt-1.5" />
                        @endif

                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                            {{ __('A Regional Admin can view all training data for their region. They cannot approve requests, issue certificates, or edit trainings — only record where their graduates have been deployed.') }}
                        </p>

                        <div class="flex items-center gap-2 mt-5">
                            <button type="button" @click="open = false"
                                class="flex-1 inline-flex items-center justify-center border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm font-semibold rounded-md px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                {{ __('Cancel') }}
                            </button>
                            {{-- Stays disabled until a region is chosen, so the modal can't be
                                 dismissed-by-Enter into an accidental promotion. --}}
                            <button type="submit" :disabled="region === ''"
                                class="flex-1 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-md px-3 py-2.5 hover:bg-[#1E3A66] transition disabled:opacity-40 disabled:cursor-not-allowed">
                                {{ __('Make Admin') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
