<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Parses the CDTI "Training Database" ATAR tracker CSV export into rows ready
 * for AtarRecord::create(). The export has 2 header rows, ~37 real data
 * columns, then a "DO NOT INPUT (CDTI ONLY)" pivot-summary block that must
 * never be imported — the boundary is detected by the training title column
 * going blank, since the pivot block never repeats it.
 */
class AtarImportParser
{
    /**
     * Column index (within the real data columns) of the training title —
     * used as the signal that real per-training rows have ended.
     */
    private const TITLE_COLUMN = 2;

    /**
     * @return array{rows: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function parse(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open CSV file at {$filePath}.");
        }

        // Skip the two header rows (section headers, then column headers).
        fgetcsv($handle, 0, ',', '"', '');
        fgetcsv($handle, 0, ',', '"', '');

        $rows = [];
        $warnings = [];
        $rowNumber = 0;

        while (($cells = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $rowNumber++;

            $title = trim((string) ($cells[self::TITLE_COLUMN] ?? ''));

            if ($title === '') {
                // Real data has ended; the rest is the CDTI-only pivot block.
                break;
            }

            [$row, $rowWarnings] = $this->normalizeRow($cells, $rowNumber);
            $rows[] = $row;

            foreach ($rowWarnings as $warning) {
                $warnings[] = "Row {$rowNumber}: {$warning}";
            }
        }

        fclose($handle);

        return ['rows' => $rows, 'warnings' => $warnings];
    }

    /**
     * @param  array<int, string|null>  $cells
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function normalizeRow(array $cells, int $rowNumber): array
    {
        $warnings = [];

        $text = fn (int $i): ?string => $this->nullableText($cells[$i] ?? null);
        $int = fn (int $i): ?int => $this->nullableInt($cells[$i] ?? null);
        $decimal = fn (int $i): ?float => $this->nullableFloat($cells[$i] ?? null);
        $bool = fn (int $i): bool => $this->isTrue($cells[$i] ?? null);

        $dateAtarSubmitted = $this->parseDate($text(35));

        if ($text(35) !== null && $dateAtarSubmitted === null) {
            $warnings[] = "Could not parse \"Date of ATAR Submission\" value \"{$text(35)}\".";
        }

        return [[
            'atar_tracker_code' => $text(0),
            'training_type_code' => $text(1),
            'training_title' => $text(2),
            'mode_of_implementation' => $text(3),
            'month' => $text(4),
            'date_conducted' => $text(5),
            'venue' => $text(6),
            'issues_and_concerns' => $text(7),
            'ways_forward' => $text(8),
            'overall_rating' => $decimal(9),
            'signed' => $bool(10),
            'dropouts' => $int(11),
            'participation' => $int(12),
            'graduates' => $int(13),
            'graduates_rdrrmc' => $int(14),
            'graduates_lgu' => $int(15),
            'graduates_ldrrmo' => $int(16),
            'graduates_academe' => $int(17),
            'graduates_cso' => $int(18),
            'graduates_ngo' => $int(19),
            'graduates_volunteer' => $int(20),
            'graduates_private_sector' => $int(21),
            'graduates_others' => $int(22),
            'graduates_male' => $int(23),
            'graduates_female' => $int(24),
            'graduates_pwd' => $int(25),
            'graduates_youth' => $int(26),
            'source_of_funds' => $text(27),
            'budget' => $decimal(28),
            'actual' => $decimal(29),
            'variance' => $decimal(30),
            'l1_completed' => $bool(31),
            'l2_completed' => $bool(32),
            'remarks' => $text(33),
            'date_atar_submitted' => $dateAtarSubmitted,
            'verified_by' => $text(36),
        ], $warnings];
    }

    private function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(?string $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) round((float) $value);
    }

    private function nullableFloat(?string $value): ?float
    {
        $value = trim((string) $value);

        return $value === '' ? null : (float) $value;
    }

    private function isTrue(?string $value): bool
    {
        return strtoupper(trim((string) $value)) === 'TRUE';
    }

    private function parseDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
