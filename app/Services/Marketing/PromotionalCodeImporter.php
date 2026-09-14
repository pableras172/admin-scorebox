<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Models\PromotionalCode;
use Illuminate\Support\Facades\DB;
use SplFileObject;

final class PromotionalCodeImporter
{
    /**
     * Import promotional codes from a CSV or plain text file into the database.
     *
     * @return array{total: int, inserted: int, skipped: int}
     */
    public static function import(string $filePath): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return ['total' => 0, 'inserted' => 0, 'skipped' => 0];
        }

        $initialCount = PromotionalCode::count();
        $file = new SplFileObject($filePath, 'r');
        $batch = [];
        $total = 0;
        $now = now();

        while (! $file->eof()) {
            $line = $file->fgets();
            if ($line === false) {
                continue;
            }

            $line = trim($line, " \t\n\r\0\x0B,\"");
            if ($line === '') {
                continue;
            }

            // If line contains commas, extract the first column
            if (str_contains($line, ',')) {
                $parts = str_getcsv($line);
                $codeCandidate = trim((string) ($parts[0] ?? ''), " \t\n\r\0\x0B,\"");
            } else {
                $codeCandidate = $line;
            }

            if ($codeCandidate === '') {
                continue;
            }

            // Skip known header names
            $lower = strtolower($codeCandidate);
            if (in_array($lower, ['promotion code', 'promotion_code', 'code', 'código', 'codigo'], true)) {
                continue;
            }

            $total++;
            $batch[] = [
                'code' => strtoupper($codeCandidate),
                'assigned_email' => null,
                'assigned_uid' => null,
                'marketing_campaign_id' => null,
                'assigned_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 500) {
                DB::table('promotional_codes')->insertOrIgnore($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            DB::table('promotional_codes')->insertOrIgnore($batch);
        }

        $finalCount = PromotionalCode::count();
        $inserted = max(0, $finalCount - $initialCount);
        $skipped = max(0, $total - $inserted);

        return [
            'total' => $total,
            'inserted' => $inserted,
            'skipped' => $skipped,
        ];
    }
}

