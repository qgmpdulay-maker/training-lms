<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AtarRecord;
use App\Models\TrainingRequest;
use App\Services\AtarImportParser;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AtarRecordController extends Controller
{
    private const SESSION_KEY = 'atar_import';

    public function index(Request $request): View
    {
        $records = AtarRecord::query()
            ->when($request->query('region'), fn ($q, $region) => $q->where('region', $region))
            ->when($request->query('training_type_code'), fn ($q, $code) => $q->where('training_type_code', $code))
            ->orderByDesc('date_atar_submitted')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.super-admin.atar-records.index', [
            'records' => $records,
            'regions' => config('regions.list'),
            'selectedRegion' => $request->query('region'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.super-admin.atar-records.import', [
            'regions' => config('regions.list'),
            'preview' => $request->session()->get(self::SESSION_KEY),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'region' => ['required', 'string', 'in:'.implode(',', config('regions.list'))],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $path = $validated['csv_file']->store('atar-imports', 'public');

        $parsed = (new AtarImportParser)->parse(Storage::disk('public')->path($path));

        if (empty($parsed['rows'])) {
            Storage::disk('public')->delete($path);

            return back()->withErrors(['csv_file' => 'No training rows could be read from that file — check it matches the Training Database export format.']);
        }

        $request->session()->put(self::SESSION_KEY, [
            'region' => $validated['region'],
            'rows' => $parsed['rows'],
            'warnings' => $parsed['warnings'],
            'source_file_path' => $path,
        ]);

        return Redirect::route('admin.atar-records.import');
    }

    public function confirmImport(Request $request): RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);

        abort_if(! $preview, 404);

        DB::transaction(function () use ($preview, $request) {
            foreach ($preview['rows'] as $row) {
                $trainingRequest = $this->createLinkedTrainingRequest($row, $preview['region']);

                AtarRecord::create($row + [
                    'region' => $preview['region'],
                    'source_file_path' => $preview['source_file_path'],
                    'imported_by' => $request->user()->id,
                    'training_request_id' => $trainingRequest->id,
                ]);
            }
        });

        $count = count($preview['rows']);
        $request->session()->forget(self::SESSION_KEY);

        return Redirect::route('admin.atar-records.index')
            ->with('status', "Imported {$count} training ".Str::plural('record', $count).' for '.$preview['region'].'.');
    }

    public function cancelImport(Request $request): RedirectResponse
    {
        $preview = $request->session()->pull(self::SESSION_KEY);

        if ($preview && ($preview['source_file_path'] ?? null)) {
            Storage::disk('public')->delete($preview['source_file_path']);
        }

        return Redirect::route('admin.atar-records.import');
    }

    public function destroy(AtarRecord $atarRecord): RedirectResponse
    {
        // The linked TrainingRequest only exists because of this import —
        // remove it too so completed-training stats don't keep a row with no
        // backing ATAR detail behind it.
        $atarRecord->trainingRequest?->delete();
        $atarRecord->delete();

        return back()->with('status', 'ATAR record deleted.');
    }

    /**
     * Creates the completed TrainingRequest each imported row counts as, so
     * it flows into Regional Performance, Graduates by Training/Sex, the
     * Graduates Map, and Summary — not just the ATAR-specific charts.
     *
     * @param  array<string, mixed>  $row
     */
    private function createLinkedTrainingRequest(array $row, string $region): TrainingRequest
    {
        $graduatesByGender = ($row['graduates_male'] ?? 0) + ($row['graduates_female'] ?? 0);

        $participation = $row['participation']
            ?? (($row['graduates'] ?? 0) + ($row['dropouts'] ?? 0)) ?: null;

        // The sheet's own numbers aren't always internally consistent — a few
        // rows' Male+Female sum exceeds their stated Graduates total. Since
        // graduates_male/female (not the raw "graduates" column) is what
        // TrainingRequest::graduates and every completion-rate stat actually
        // read, participants must never come in lower than that sum, or
        // completion rate reports over 100%.
        $numberOfParticipants = max($participation ?: ($row['graduates'] ?? 0), $graduatesByGender);

        $trainingRequest = TrainingRequest::create([
            'training_slug' => Str::slug($row['training_title']),
            'training_title' => $row['training_title'],
            'category' => TrainingRequest::CATEGORY_APB,
            'region' => $region,
            'venue' => $row['venue'],
            'preferred_date' => $this->resolvePreferredDate($row),
            'number_of_participants' => $numberOfParticipants,
            'graduates_male' => $row['graduates_male'] ?? 0,
            'graduates_female' => $row['graduates_female'] ?? 0,
            'signature_name' => $row['verified_by'] ?? null,
            'status' => TrainingRequest::STATUS_COMPLETED,
        ]);

        // 'source' isn't mass-assignable (see TrainingRequest's #[Fillable]
        // list) — set directly, same as SuperAdminTrainingController::store().
        $trainingRequest->source = TrainingRequest::SOURCE_ATAR_IMPORT;
        $trainingRequest->save();

        return $trainingRequest;
    }

    /**
     * The sheet's "Date Conducted" column is freeform and often a range
     * ("May 4-6, 2026", "March 16-18. 2026") — TrainingRequest only has room
     * for one date, so this takes the first day of the range. Falls back to
     * the ATAR submission date, then today, if the text can't be parsed.
     */
    private function resolvePreferredDate(array $row): string
    {
        $dateConducted = $row['date_conducted'] ?? null;

        if ($dateConducted) {
            $firstDayOnly = preg_replace('/(\d+)\s*-\s*\d+/', '$1', $dateConducted);

            try {
                return Carbon::parse($firstDayOnly)->toDateString();
            } catch (\Throwable) {
                // fall through
            }
        }

        return $row['date_atar_submitted'] ?? now()->toDateString();
    }
}
