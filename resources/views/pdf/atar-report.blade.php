{{--
    Renders one AtarReport as a printable PDF (dompdf), laid out to match a
    real ATAR: a header table, a signatures block, then one page per annex
    (Declaration of Graduates, Level 1 Reaction Evaluation, Level 2 Learning
    Evaluation) — each annex only appears if there's actual data for it, and
    each one reprints the signatures block below it, matching how the real
    document gets signed per-section rather than once at the very end.

    dompdf has limited flexbox/grid support, so everything below is plain
    <table>/<td> layout, same as this app's other PDF templates.
--}}
@php
    $lecturers = $report->lecturers_list ?: [];
    $graduates = $report->graduates_list ?: [];
    $dropouts = $report->dropouts_list ?: [];
    $modules = $report->l1_modules ?: [];
    $l2 = $report->l2_stats ?: [];
    $signatories = $report->signatories ?: [];

    // Only list an annex (and later, only render its page) if it actually
    // has content — a report with no evaluation data yet shouldn't print an
    // empty "Level 1 Reaction Evaluation Results" page.
    $annexes = [];
    if (! empty($graduates) || ! empty($dropouts)) {
        $annexes[] = 'List of Graduates';
    }
    if (! empty($modules)) {
        $annexes[] = 'Level 1 (Reaction) Evaluation Results';
    }
    if (($l2['pretest']['count'] ?? 0) > 0 || ($l2['posttest']['count'] ?? 0) > 0) {
        $annexes[] = 'Level 2 (Learning) Evaluation Results';
    }

    // Small print repeated at the bottom of every page (via the fixed-position .footer div below).
    $footerLine = collect([$report->title, $report->venue])->filter()->implode(' — ');

    // Builds the "Prepared by / Approved by / ..." row of blank signature
    // lines. Returned as a raw HTML string (via {!! !!} at each call site)
    // and called once after the main report and again after each annex,
    // since real ATARs are signed per-section, not just once at the end.
    // No actual signature image is ever drawn here — ATARs stay wet-ink
    // signed (see project_atar_wet_ink_signatures memory), so this just
    // leaves blank space above the printed name for that.
    $signatureBlock = function () use ($signatories) {
        if (empty($signatories)) {
            return '';
        }
        $html = '<table class="signatures"><tr>';
        foreach ($signatories as $signatory) {
            $html .= '<td class="signature-cell">';
            $html .= '<div class="signature-role">'.e($signatory['role'] ?? '').':</div>';
            $html .= '<div class="signature-line"></div>';
            $html .= '<div class="signature-name">'.e(mb_strtoupper($signatory['name'] ?? '')).'</div>';
            $html .= '<div class="signature-title">'.e($signatory['title'] ?? '').'</div>';
            $html .= '</td>';
        }
        $html .= '</tr></table>';

        return $html;
    };
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 90px 50px 70px 50px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; }
        h1.doc-title { text-align: center; font-size: 14px; text-decoration: underline; margin: 0 0 14px 0; }
        h2.annex-title { text-align: center; font-size: 13px; text-decoration: underline; margin: 0 0 14px 0; }
        table.header-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.header-table td { border: 1px solid #333; padding: 6px 8px; vertical-align: top; }
        table.header-table td.label { width: 22%; font-weight: bold; background: #f2f2f2; }
        /* Forces each annex onto its own page in the printed PDF */
        .annex-section { page-break-before: always; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.data-table th, table.data-table td { border: 1px solid #333; padding: 5px 6px; font-size: 10px; }
        table.data-table th { background: #f2f2f2; text-align: center; }
        table.data-table td.text-left { text-align: left; }
        table.data-table td.text-center { text-align: center; }
        .analysis-heading { font-weight: bold; margin: 10px 0 4px 0; }
        table.signatures { width: 100%; border-collapse: collapse; margin-top: 26px; }
        table.signatures td.signature-cell { border: none; padding: 0 16px 0 0; vertical-align: bottom; width: 33%; }
        .signature-role { font-size: 10px; margin-bottom: 24px; }
        .signature-line { border-bottom: 1px solid #333; height: 1px; margin-bottom: 3px; }
        .signature-name { font-weight: bold; font-size: 11px; }
        .signature-title { font-size: 10px; }
        .photos-grid img { width: 47%; margin: 1%; border: 1px solid #ccc; }
        /* dompdf repeats any position:fixed element on every page — this is how the footer prints on the main report and every annex page without being repeated in the markup */
        .footer { position: fixed; bottom: -50px; left: 0; right: 0; text-align: center; font-size: 9px; color: #555; }
        .muted { color: #888; }
    </style>
</head>
<body>
    <div class="footer">{{ $footerLine }}</div>

    <h1 class="doc-title">AFTER TRAINING ACTIVITY REPORT (ATAR)</h1>

    <table class="header-table">
        <tr><td class="label">TITLE OF ACTIVITY</td><td>{{ $report->title ?: '—' }}</td></tr>
        <tr><td class="label">VENUE/LOCATION</td><td>{{ $report->venue ?: '—' }}</td></tr>
        <tr><td class="label">DATE(S)</td><td>{{ $report->date_range ?: '—' }}</td></tr>
        <tr><td class="label">BACKGROUND</td><td>{!! $report->background ? nl2br(e($report->background)) : '<span class="muted">—</span>' !!}</td></tr>
        <tr><td class="label">OBJECTIVE(S)</td><td>{!! $report->objectives ? nl2br(e($report->objectives)) : '<span class="muted">—</span>' !!}</td></tr>
        <tr><td class="label">ATTENDEES</td><td>{!! $report->attendees_narrative ? nl2br(e($report->attendees_narrative)) : '<span class="muted">—</span>' !!}</td></tr>
        <tr><td class="label">FUNDING SOURCE</td><td>{{ $report->funding_source ?: '—' }}</td></tr>
        <tr><td class="label">HIGHLIGHTS</td><td>{!! $report->highlights ? nl2br(e($report->highlights)) : '<span class="muted">—</span>' !!}</td></tr>
        <tr><td class="label">ISSUES AND CONCERNS</td><td>{!! $report->issues_and_concerns ? nl2br(e($report->issues_and_concerns)) : '<span class="muted">—</span>' !!}</td></tr>
        <tr><td class="label">WAYS FORWARD</td><td>{!! $report->ways_forward ? nl2br(e($report->ways_forward)) : '<span class="muted">—</span>' !!}</td></tr>
        <tr>
            <td class="label">PHOTOS</td>
            <td>
                @if (! empty($report->photos))
                    <div class="photos-grid">
                        @foreach ($report->photos as $path)
                            <img src="{{ public_path('storage/'.$path) }}" />
                        @endforeach
                    </div>
                @else
                    <span class="muted">—</span>
                @endif
            </td>
        </tr>
        <tr><td class="label">GRADUATES</td><td>{!! $report->graduates_summary ? nl2br(e($report->graduates_summary)) : '<span class="muted">—</span>' !!}</td></tr>
        <tr>
            <td class="label">LIST OF LECTURERS, RESOURCE PERSONS, FACILITATORS, AND SECRETARIAT</td>
            <td>
                @forelse ($lecturers as $lecturer)
                    {{ $lecturer['name'] ?? '' }}{{ !empty($lecturer['organization']) ? ' ('.$lecturer['organization'].')' : '' }}{{ !empty($lecturer['role']) ? ' — '.$lecturer['role'] : '' }}<br>
                @empty
                    <span class="muted">—</span>
                @endforelse
            </td>
        </tr>
        <tr>
            <td class="label">ANNEXES</td>
            <td>
                @forelse ($annexes as $annex)
                    {{ $annex }}<br>
                @empty
                    <span class="muted">—</span>
                @endforelse
            </td>
        </tr>
    </table>

    {!! $signatureBlock() !!}

    {{-- Annex 1: Declaration of Graduates — a List of Graduates table, plus a List of
         Dropouts table if there are any, then the signatories reprinted. --}}
    @if (! empty($graduates) || ! empty($dropouts))
        <div class="annex-section">
            <h2 class="annex-title">DECLARATION OF GRADUATES</h2>

            <p style="font-weight:bold; margin-bottom:6px;">LIST OF GRADUATES</p>
            <table class="data-table">
                <tr>
                    <th style="width:6%;">#</th>
                    <th style="width:24%;">CERTIFICATE CODE</th>
                    <th style="width:35%;">FULL NAME</th>
                    <th style="width:12%;">GENDER</th>
                    <th>AGENCY</th>
                </tr>
                @forelse ($graduates as $i => $graduate)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="text-left">{{ $graduate['code'] ?? '' }}</td>
                        <td class="text-left">{{ $graduate['name'] ?? '' }}</td>
                        <td class="text-center">{{ $graduate['gender'] ?? '' }}</td>
                        <td class="text-left">{{ $graduate['agency'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center muted">No graduates listed.</td></tr>
                @endforelse
            </table>

            @if (! empty($dropouts))
                <p style="font-weight:bold; margin-bottom:6px;">LIST OF DROPOUTS</p>
                <table class="data-table">
                    <tr>
                        <th style="width:6%;">#</th>
                        <th style="width:47%;">FULL NAME</th>
                        <th style="width:15%;">GENDER</th>
                        <th>AGENCY</th>
                    </tr>
                    @foreach ($dropouts as $i => $dropout)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td class="text-left">{{ $dropout['name'] ?? '' }}</td>
                            <td class="text-center">{{ $dropout['gender'] ?? '' }}</td>
                            <td class="text-left">{{ $dropout['agency'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            {!! $signatureBlock() !!}
        </div>
    @endif

    {{-- Annex 2: Level 1 Reaction Evaluation — one row per module, each cell showing
         "count (percentage%)" for that rating value, matching the real sample's table. --}}
    @if (! empty($modules))
        <div class="annex-section">
            <h2 class="annex-title">LEVEL 1 REACTION EVALUATION REPORT</h2>

            <table class="data-table">
                <tr>
                    <th style="width:34%;">MODULES</th>
                    <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th>
                    <th style="width:14%;">AVERAGE</th>
                </tr>
                @foreach ($modules as $module)
                    {{-- responses/average were already computed server-side on save (AtarReportController@update)
                         — array_sum here is just a fallback for a row that somehow skipped that (e.g. old data) --}}
                    @php $responses = $module['responses'] ?? array_sum($module['distribution'] ?? []); @endphp
                    <tr>
                        <td class="text-left">{{ $module['module'] }}</td>
                        @for ($rating = 1; $rating <= 5; $rating++)
                            @php
                                $count = $module['distribution'][$rating] ?? 0;
                                $pct = $responses > 0 ? round($count / $responses * 100) : 0;
                            @endphp
                            <td class="text-center">{{ $count }} ({{ $pct }}%)</td>
                        @endfor
                        <td class="text-center">{{ $module['average'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </table>
            <p class="muted" style="font-size:9px;">Legend: 1 - Poor, 2 - Unsatisfactory, 3 - Satisfactory, 4 - Very Satisfactory, 5 - Outstanding.</p>

            @if ($report->l1_analysis)
                <p class="analysis-heading">Analysis</p>
                <p>{!! nl2br(e($report->l1_analysis)) !!}</p>
            @endif

            {!! $signatureBlock() !!}
        </div>
    @endif

    {{-- Annex 3: Level 2 Learning Evaluation — pretest vs posttest stats table --}}
    @if (($l2['pretest']['count'] ?? 0) > 0 || ($l2['posttest']['count'] ?? 0) > 0)
        <div class="annex-section">
            <h2 class="annex-title">LEVEL 2 LEARNING EVALUATION REPORT</h2>

            <table class="data-table">
                <tr><th style="width:40%;"></th><th>PRE-TEST</th><th>POST-TEST</th></tr>
                @foreach ([
                    'mean' => 'Mean', 'median' => 'Median', 'mode' => 'Mode',
                    'min' => 'Lowest Score (Minimum)', 'max' => 'Highest Score (Maximum)', 'count' => 'Number of Takers (Count)',
                ] as $key => $label)
                    <tr>
                        <td class="text-left">{{ $label }}</td>
                        <td class="text-center">{{ $l2['pretest'][$key] ?? '—' }}</td>
                        <td class="text-center">{{ $l2['posttest'][$key] ?? '—' }}</td>
                    </tr>
                @endforeach
            </table>

            @if ($report->l2_analysis)
                <p class="analysis-heading">Analysis</p>
                <p>{!! nl2br(e($report->l2_analysis)) !!}</p>
            @endif

            {!! $signatureBlock() !!}
        </div>
    @endif
</body>
</html>
