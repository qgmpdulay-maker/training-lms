<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tools') }}
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

            @if ($regionLocked)
                <div class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ __('Showing :region only. Requests need to be tagged with a region on the Summary tab to show up here.', ['region' => $region]) }}</span>
                </div>
            @else
                <div class="flex items-center flex-wrap gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm px-5 py-3">
                    <span class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ __('Region') }}</span>
                    <form method="GET" action="{{ route('admin.tools') }}" class="flex items-center gap-2">
                        <select name="region" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="">{{ __('All Regions (Philippines)') }}</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption }}" @selected($region === $regionOption)>{{ $regionOption }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if ($region)
                        <a href="{{ route('admin.tools') }}"
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Reset to all regions') }}
                        </a>
                    @endif
                </div>
            @endif

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Training Status (Accomplished vs Pending) -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Training Status') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Share of training requests completed vs. still in progress.') }}</p>

                    <div class="flex items-center gap-8">
                        <div class="relative w-40 h-40 shrink-0">
                            <svg viewBox="0 0 100 100" class="w-40 h-40 -rotate-90">
                                @if ($statusDonut['total'] === 0)
                                    <circle cx="50" cy="50" r="{{ $statusDonut['radius'] }}" fill="none" stroke="#e1e0d9" stroke-width="14" />
                                @else
                                    @foreach ($statusDonut['segments'] as $segment)
                                        @if ($segment['value'] > 0)
                                            <circle cx="50" cy="50" r="{{ $statusDonut['radius'] }}" fill="none"
                                                stroke="{{ $segment['color'] }}" stroke-width="14"
                                                stroke-dasharray="{{ $segment['dasharray'] }}"
                                                stroke-dashoffset="{{ $segment['dashoffset'] }}">
                                                <title>{{ $segment['label'] }}: {{ $segment['value'] }} ({{ $segment['percent'] }}%)</title>
                                            </circle>
                                        @endif
                                    @endforeach
                                @endif
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold text-[#152A4E] dark:text-white">{{ $statusDonut['total'] }}</span>
                                <span class="text-[11px] text-gray-400 uppercase tracking-wide">{{ __('Total') }}</span>
                            </div>
                        </div>

                        <ul class="space-y-2.5 text-sm">
                            @if ($statusDonut['total'] === 0)
                                <li class="text-gray-400">{{ __('No training requests on record yet.') }}</li>
                            @else
                                @foreach ($statusDonut['segments'] as $segment)
                                    <li class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background-color: {{ $segment['color'] }};"></span>
                                        <span class="text-gray-600 dark:text-gray-300">{{ $segment['label'] }}</span>
                                        <span class="font-semibold text-[#152A4E] dark:text-white tabular-nums">{{ $segment['value'] }}</span>
                                        <span class="text-gray-400 text-xs">({{ $segment['percent'] }}%)</span>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Technical Assistance Accomplishment -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Technical Assistance Accomplishment') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Target vs. graduates accomplished, per Technical Assistance training type.') }}</p>

                    @if (empty($taAccomplishment))
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No Technical Assistance training types with a target or completed request yet.') }}
                        </div>
                    @else
                        <div x-data="{ activeTa: @js(array_key_first($taAccomplishment)) }">
                            <div class="flex items-center gap-1 overflow-x-auto bg-gray-100 dark:bg-gray-900/40 rounded-xl p-1.5">
                                @foreach ($taAccomplishment as $title => $row)
                                    <button type="button" @click="activeTa = @js($title)"
                                        :class="activeTa === @js($title)
                                            ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm'
                                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                        class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition whitespace-nowrap">
                                        {{ $title }}
                                        <span :class="activeTa === @js($title)
                                                ? 'bg-[#152A4E]/10 text-[#152A4E] dark:bg-white/15 dark:text-white px-1.5 py-0.5 rounded-full text-xs font-semibold'
                                                : 'text-gray-400 dark:text-gray-500 text-xs font-normal'">
                                            {{ $row['accomplished'] }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>

                            @foreach ($taAccomplishment as $title => $row)
                                <div x-show="activeTa === @js($title)" x-cloak class="mt-5">
                                    <div class="flex items-center justify-between gap-3 flex-wrap">
                                        <div>
                                            <p class="text-2xl font-bold text-[#152A4E] dark:text-white tabular-nums">{{ $row['accomplished'] }}</p>
                                            <p class="text-xs text-gray-400">
                                                {{ $row['target'] > 0 ? __(':target target', ['target' => $row['target']]) : __('No target set') }}
                                            </p>
                                        </div>
                                        @if (Auth::user()->isSuperAdmin())
                                            <form method="POST" action="{{ route('admin.tools.ta-targets') }}" class="flex items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="training_title" value="{{ $title }}">
                                                <label class="text-xs text-gray-400" for="target-{{ Str::slug($title) }}">{{ __('Target:') }}</label>
                                                <input id="target-{{ Str::slug($title) }}" type="number" name="target" min="0" value="{{ $row['target'] }}"
                                                    class="w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                                <button type="submit"
                                                    class="inline-flex items-center justify-center bg-[#152A4E] text-white text-xs font-semibold rounded-md px-4 py-2 hover:bg-[#1E3A66] transition">
                                                    {{ __('Save') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    <div class="relative h-4 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden mt-3">
                                        <div class="h-full rounded-full bg-[#152A4E] dark:bg-[#E2762D]" style="width: {{ min($row['accomplished_percent'], 100) }}%;"></div>
                                        @if ($row['target'] > 0)
                                            <div class="absolute inset-y-0 w-0.5 bg-gray-500 dark:bg-gray-300" style="left: {{ min($row['target_percent'], 100) }}%;" title="{{ __('Target') }}: {{ $row['target'] }}"></div>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-4 mt-2 text-[11px] text-gray-400">
                                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#152A4E] dark:bg-[#E2762D]"></span>{{ __('Accomplished') }}</span>
                                        @if ($row['target'] > 0)
                                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-0.5 bg-gray-500 dark:bg-gray-300"></span>{{ __('Target') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            <!-- Graduates per training -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates per Training') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Auto-generated from completed training requests, broken down by year.') }}</p>

                @if ($graduatesByTraining->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No completed trainings on record yet.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Training') }}</th>
                                    <th class="py-2 pr-4">{{ __('Total Graduates') }}</th>
                                    <th class="py-2 pr-4">{{ __('By Year') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($graduatesByTraining as $trainingTitle => $data)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $trainingTitle }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $data['total'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                            @foreach ($data['byYear'] as $year => $count)
                                                <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2 py-0.5 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 mr-1">
                                                    {{ $year }}: {{ $count }}
                                                </span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Certificates & ATAR -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-1">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white">{{ __('Certificates & ATAR') }}</h2>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('admin.tools.atar-template') }}" target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-md border border-gray-200 dark:border-gray-600 text-[#152A4E] dark:text-white px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">
                            {{ __('Download ATAR Template') }}
                        </a>
                        <a href="{{ route('admin.tools.certificate-template') }}" target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-md border border-gray-200 dark:border-gray-600 text-[#152A4E] dark:text-white px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">
                            {{ __('Download Certificate Template') }}
                        </a>
                    </div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ __("Download blank ATAR and certificate templates above (generic placeholders pending OCD's branded files), then upload a completed certificate (shown on the participant's own dashboard) and/or ATAR per record below. Files are stored on the app's own storage, not Google Drive.") }}
                </p>

                <div id="files-section">
                    @include('admin.partials.files-table')
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const container = document.getElementById('files-section');

                    container.addEventListener('click', function (event) {
                        const link = event.target.closest('.files-pagination a');
                        if (!link || !link.href) {
                            return;
                        }

                        event.preventDefault();

                        fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then((response) => response.text())
                            .then((html) => {
                                container.innerHTML = html;
                                window.history.replaceState({}, '', link.href);
                            });
                    });
                });
            </script>

            <!-- Evaluation Computation (L1 / L2) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Evaluation Computation (L1 / L2)') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ __('Combines the admin-entered evaluation (use "Add Evaluation" in the table above) with what participants submitted themselves. Pick a training below, then expand a session to see its L1 and L2 results.') }}
                </p>

                @if (empty($evaluationsByTraining))
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No evaluations entered yet.') }}
                    </div>
                @else
                    <div x-data="{ activeTraining: @js(array_key_first($evaluationsByTraining)) }">
                        <div class="flex items-center gap-1 overflow-x-auto bg-gray-100 dark:bg-gray-900/40 rounded-xl p-1.5">
                            @foreach ($evaluationsByTraining as $trainingTitle => $sessions)
                                <button type="button" @click="activeTraining = @js($trainingTitle)"
                                    :class="activeTraining === @js($trainingTitle)
                                        ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition whitespace-nowrap">
                                    {{ $trainingTitle }}
                                    <span :class="activeTraining === @js($trainingTitle)
                                            ? 'bg-[#152A4E]/10 text-[#152A4E] dark:bg-white/15 dark:text-white px-1.5 py-0.5 rounded-full text-xs font-semibold'
                                            : 'text-gray-400 dark:text-gray-500 text-xs font-normal'">
                                        {{ $sessions->count() }}
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        @foreach ($evaluationsByTraining as $trainingTitle => $sessions)
                            <div x-show="activeTraining === @js($trainingTitle)" x-cloak class="mt-5">
                                <div class="border border-gray-100 dark:border-gray-700 rounded-lg overflow-hidden divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($sessions as $session)
                                        <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                                            <button type="button" @click="open = !open"
                                                class="w-full flex items-center justify-between gap-4 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <svg class="w-4 h-4 shrink-0 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                    </svg>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-semibold text-[#152A4E] dark:text-white truncate">{{ $session['venue'] }}</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $session['preferred_date']->format('M j, Y') }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 shrink-0">
                                                    @if ($session['participant_total'] > 0)
                                                        <span class="hidden sm:inline-flex items-center gap-1 text-xs font-semibold text-gray-500 dark:text-gray-400" title="{{ __('Participants who submitted their own evaluation') }}">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                                                            {{ $session['participant_response_count'] }}/{{ $session['participant_total'] }} {{ __('evaluated') }}
                                                        </span>
                                                    @endif
                                                    @if ($session['overall_trainer_rating'])
                                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.958c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.958a1 1 0 00-.364-1.118L2.062 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.286-3.958z" /></svg>
                                                            {{ $session['overall_trainer_rating'] }}
                                                        </span>
                                                    @endif
                                                    <span class="hidden sm:inline text-xs text-gray-400" title="{{ $session['updated_at']?->format('M j, Y g:i A') }}">
                                                        {{ __('Updated') }} {{ $session['updated_at']?->diffForHumans() ?? '—' }}
                                                    </span>
                                                </div>
                                            </button>

                                            <div x-show="open" x-cloak class="px-4 pb-4 space-y-5 bg-gray-50/60 dark:bg-gray-900/20">
                                                @if ($session['modules']->isNotEmpty())
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2 pt-1">{{ __('L1 — Module & Trainer Ratings') }}</p>
                                                        <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                            <table class="min-w-full text-sm">
                                                                <thead>
                                                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                        <th class="py-2 pl-4 pr-4">{{ __('Module') }}</th>
                                                                        <th class="py-2 pr-4">{{ __('Module Rating') }}</th>
                                                                        <th class="py-2 pr-4">{{ __('Trainer Rating') }}</th>
                                                                        <th class="py-2 pr-4">{{ __('Participant Avg') }}</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                    @foreach ($session['modules'] as $module)
                                                                        <tr>
                                                                            <td class="py-2 pl-4 pr-4 text-gray-700 dark:text-gray-200">{{ $module['module'] }}</td>
                                                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $module['module_rating'] ?? '—' }}</td>
                                                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $module['trainer_rating'] ?? '—' }}</td>
                                                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">
                                                                                {{ $module['participant_rating'] ?? '—' }}
                                                                                @if ($module['participant_responses'] > 0)
                                                                                    <span class="text-gray-400">({{ trans_choice(':count response|:count responses', $module['participant_responses'], ['count' => $module['participant_responses']]) }})</span>
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                            {{ __('Overall Trainer Rating:') }} <span class="font-semibold text-[#152A4E] dark:text-white">{{ $session['overall_trainer_rating'] ?? '—' }}</span>
                                                            {{ __('(reflected on the Instructors tab when exactly one instructor teaches this training)') }}
                                                        </p>
                                                    </div>

                                                    @if ($session['modules']->contains(fn ($module) => $module['participant_responses'] > 0))
                                                        <div x-data="{ open: false }">
                                                            <button type="button" @click="open = !open" class="text-xs font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D] inline-flex items-center gap-1">
                                                                <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                                {{ __('Participant Module Ratings — Distribution & Comments') }}
                                                            </button>
                                                            <div x-show="open" x-cloak class="mt-2 overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                                <table class="min-w-full text-sm">
                                                                    <thead>
                                                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                            <th class="py-2 pl-4 pr-4">{{ __('Module') }}</th>
                                                                            @foreach (range(1, 5) as $value)
                                                                                <th class="py-2 pr-4 text-center">{{ $value }}</th>
                                                                            @endforeach
                                                                            <th class="py-2 pr-4">{{ __('Responses') }}</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                        @foreach ($session['modules'] as $module)
                                                                            <tr>
                                                                                <td class="py-2 pl-4 pr-4 text-gray-700 dark:text-gray-200">{{ $module['module'] }}</td>
                                                                                @foreach (range(1, 5) as $value)
                                                                                    <td class="py-2 pr-4 text-center text-gray-600 dark:text-gray-300 tabular-nums">{{ $module['rating_distribution'][$value] }}</td>
                                                                                @endforeach
                                                                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $module['participant_responses'] }}</td>
                                                                            </tr>
                                                                            @if (! empty($module['comments']))
                                                                                <tr>
                                                                                    <td colspan="7" class="py-2 pl-4 pr-4 bg-gray-50/60 dark:bg-gray-900/20">
                                                                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __(':module — Comments (anonymous)', ['module' => $module['module']]) }}</p>
                                                                                        <ul class="space-y-1">
                                                                                            @foreach ($module['comments'] as $comment)
                                                                                                <li class="text-xs text-gray-600 dark:text-gray-300">"{{ $comment }}"</li>
                                                                                            @endforeach
                                                                                        </ul>
                                                                                    </td>
                                                                                </tr>
                                                                            @endif
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if ($session['trainer_ratings_by_module']->isNotEmpty())
                                                        <div>
                                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __("Summary of Trainer's Rating per Module") }}</p>
                                                            <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                                <table class="min-w-full text-sm">
                                                                    <thead>
                                                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                            <th class="py-2 pl-4 pr-4">{{ __('Module') }}</th>
                                                                            @foreach (range(1, 5) as $value)
                                                                                <th class="py-2 pr-4 text-center">{{ $value }}</th>
                                                                            @endforeach
                                                                            <th class="py-2 pr-4">{{ __('Avg') }}</th>
                                                                            <th class="py-2 pr-4">{{ __('Trainer Name') }}</th>
                                                                            <th class="py-2 pr-4">{{ __('Organization / Agency') }}</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                        @foreach ($session['trainer_ratings_by_module'] as $moduleTrainerRating)
                                                                            <tr>
                                                                                <td class="py-2 pl-4 pr-4 text-gray-700 dark:text-gray-200">{{ $moduleTrainerRating['module'] }}</td>
                                                                                @foreach (range(1, 5) as $value)
                                                                                    <td class="py-2 pr-4 text-center text-gray-600 dark:text-gray-300 tabular-nums">{{ $moduleTrainerRating['rating_distribution'][$value] }}</td>
                                                                                @endforeach
                                                                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $moduleTrainerRating['rating'] }}</td>
                                                                                <td class="py-2 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $moduleTrainerRating['trainer'] ?? '—' }}</td>
                                                                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $moduleTrainerRating['organization'] ?? '—' }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if ($session['instructor_ratings']->isNotEmpty())
                                                        <div>
                                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __('Participant Trainer Ratings') }}</p>
                                                            <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                                <table class="min-w-full text-sm">
                                                                    <thead>
                                                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                            <th class="py-2 pl-4 pr-4">{{ __('Trainer') }}</th>
                                                                            <th class="py-2 pr-4">{{ __('Organization') }}</th>
                                                                            <th class="py-2 pr-4">{{ __('Avg') }}</th>
                                                                            @foreach (range(1, 5) as $value)
                                                                                <th class="py-2 pr-4 text-center">{{ $value }}</th>
                                                                            @endforeach
                                                                            <th class="py-2 pr-4">{{ __('Responses') }}</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                        @foreach ($session['instructor_ratings'] as $instructorRating)
                                                                            <tr>
                                                                                <td class="py-2 pl-4 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $instructorRating['instructor'] }}</td>
                                                                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $instructorRating['agency_organization'] ?? '—' }}</td>
                                                                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $instructorRating['rating'] }}</td>
                                                                                @foreach (range(1, 5) as $value)
                                                                                    <td class="py-2 pr-4 text-center text-gray-600 dark:text-gray-300 tabular-nums">{{ $instructorRating['rating_distribution'][$value] }}</td>
                                                                                @endforeach
                                                                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $instructorRating['responses'] }}</td>
                                                                            </tr>
                                                                            @if (! empty($instructorRating['comments']))
                                                                                <tr>
                                                                                    <td colspan="8" class="py-2 pl-4 pr-4 bg-gray-50/60 dark:bg-gray-900/20">
                                                                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __(':trainer — Comments (anonymous)', ['trainer' => $instructorRating['instructor']]) }}</p>
                                                                                        <ul class="space-y-1">
                                                                                            @foreach ($instructorRating['comments'] as $comment)
                                                                                                <li class="text-xs text-gray-600 dark:text-gray-300">"{{ $comment }}"</li>
                                                                                            @endforeach
                                                                                        </ul>
                                                                                    </td>
                                                                                </tr>
                                                                            @endif
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endif

                                                @if (! empty($session['module_matrix_columns']))
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __('L1 — Per-Taker Module & Trainer Ratings') }}</p>
                                                        <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                            <table class="min-w-full text-sm">
                                                                <thead>
                                                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                        <th class="py-2 pl-4 pr-4" rowspan="2">{{ __('Taker') }}</th>
                                                                        @foreach ($session['module_matrix_columns'] as $moduleName)
                                                                            <th class="py-2 pr-4 text-center" colspan="2">{{ $moduleName }}</th>
                                                                        @endforeach
                                                                        <th class="py-2 pr-4" rowspan="2">{{ __('Overall') }}</th>
                                                                    </tr>
                                                                    <tr class="text-left text-[10px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700">
                                                                        @foreach ($session['module_matrix_columns'] as $moduleName)
                                                                            <th class="py-1 pr-2 text-center font-normal">{{ __('Module') }}</th>
                                                                            <th class="py-1 pr-4 text-center font-normal">{{ __('Trainer') }}</th>
                                                                        @endforeach
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                    @foreach ($session['module_matrix'] as $takerRow)
                                                                        <tr>
                                                                            <td class="py-2 pl-4 pr-4 text-gray-700 dark:text-gray-200">{{ $takerRow['participant'] }}</td>
                                                                            @foreach ($session['module_matrix_columns'] as $moduleName)
                                                                                <td class="py-2 pr-2 text-center text-gray-600 dark:text-gray-300 tabular-nums">{{ $takerRow['scores'][$moduleName]['module_rating'] ?? '—' }}</td>
                                                                                <td class="py-2 pr-4 text-center text-gray-600 dark:text-gray-300 tabular-nums">{{ $takerRow['scores'][$moduleName]['trainer_rating'] ?? '—' }}</td>
                                                                            @endforeach
                                                                            <td class="py-2 pr-4 font-semibold text-[#152A4E] dark:text-white tabular-nums">{{ $takerRow['overall'] ?? '—' }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($session['pretest_stats']['count'] > 0 || $session['posttest_stats']['count'] > 0)
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __('L2 — Pre/Post Test') }}</p>
                                                        <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                            <table class="min-w-full text-sm">
                                                                <thead>
                                                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                        <th class="py-2 pl-4 pr-4"></th>
                                                                        <th class="py-2 pr-4">{{ __('Mean') }}</th>
                                                                        <th class="py-2 pr-4">{{ __('Median') }}</th>
                                                                        <th class="py-2 pr-4">{{ __('Mode') }}</th>
                                                                        <th class="py-2 pr-4">{{ __('Min') }}</th>
                                                                        <th class="py-2 pr-4">{{ __('Max') }}</th>
                                                                        <th class="py-2 pr-4">{{ __('Count') }}</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                    @foreach (['Pre-Test' => $session['pretest_stats'], 'Post-Test' => $session['posttest_stats']] as $label => $stats)
                                                                        <tr>
                                                                            <td class="py-2 pl-4 pr-4 font-medium text-[#152A4E] dark:text-white">{{ __($label) }}</td>
                                                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $stats['mean'] ?? '—' }}</td>
                                                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $stats['median'] ?? '—' }}</td>
                                                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $stats['mode'] ?? '—' }}</td>
                                                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $stats['min'] ?? '—' }}</td>
                                                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $stats['max'] ?? '—' }}</td>
                                                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $stats['count'] }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Graduates by LGU -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates by LGU') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    {{ __('Completed trainings grouped by the LGU recorded on Summary.') }}
                    {{ __('For a point-level map of graduates by LGU / Volunteers / RDRRMC member agencies, see the') }}
                    <a href="{{ route('admin.monitoring.map', $region ? ['regions' => [$region]] : []) }}" class="font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D]">{{ __('Graduates Map') }}</a>.
                </p>
                <p class="text-xs text-amber-700 dark:text-amber-400 mb-5">
                    {{ __('"Teams Organized" isn\'t shown here since there\'s no team data in the system yet.') }}
                </p>

                @if (empty($graduatesByLgu))
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No completed trainings with an LGU recorded yet.') }}
                    </div>
                @else
                    <div x-data="{ activeLguRegion: @js(array_key_first($graduatesByLgu)) }">
                        <div class="flex items-center gap-1 overflow-x-auto bg-gray-100 dark:bg-gray-900/40 rounded-xl p-1.5">
                            @foreach ($graduatesByLgu as $regionName => $regionGroup)
                                <button type="button" @click="activeLguRegion = @js($regionName)"
                                    :class="activeLguRegion === @js($regionName)
                                        ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition whitespace-nowrap">
                                    {{ $regionName }}
                                    <span :class="activeLguRegion === @js($regionName)
                                            ? 'bg-[#152A4E]/10 text-[#152A4E] dark:bg-white/15 dark:text-white px-1.5 py-0.5 rounded-full text-xs font-semibold'
                                            : 'text-gray-400 dark:text-gray-500 text-xs font-normal'">
                                        {{ $regionGroup['total'] }}
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        @foreach ($graduatesByLgu as $regionName => $regionGroup)
                            <div x-show="activeLguRegion === @js($regionName)" x-cloak class="mt-5">
                                @php $regionMax = max(array_column($regionGroup['lgus'], 'total')); @endphp
                                <div class="space-y-3">
                                    @foreach ($regionGroup['lgus'] as $row)
                                        <div class="flex items-center gap-3">
                                            <div class="w-48 shrink-0 text-xs font-medium text-gray-600 dark:text-gray-300 truncate">{{ $row['lgu'] }}</div>
                                            <div class="flex-1 h-5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                                <div class="h-full rounded-full bg-[#152A4E] dark:bg-[#E2762D] transition-all" style="width: {{ round(($row['total'] / $regionMax) * 100) }}%;"></div>
                                            </div>
                                            <div class="w-8 text-right text-xs font-semibold text-gray-700 dark:text-gray-200 tabular-nums">{{ $row['total'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
