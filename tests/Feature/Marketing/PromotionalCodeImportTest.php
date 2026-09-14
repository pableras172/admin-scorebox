<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Models\PromotionalCode;
use App\Services\Marketing\PromotionalCodeImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class PromotionalCodeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_google_play_csv_and_skips_header_and_duplicates(): void
    {
        // Existing code in database
        PromotionalCode::factory()->create([
            'code' => 'EXISTINGCODE12345678901',
        ]);

        $csvContent = <<<CSV
Promotion code
EXISTINGCODE12345678901
4DUJQ6ASZ392Z0EARXBLL64
1V9L9TGAP3FZT5RRFCQXW8L
HCMNAPQA2XW42ZQC44GWYZX
4DUJQ6ASZ392Z0EARXBLL64

CSV;

        $tempPath = tempnam(sys_get_temp_dir(), 'promo_test_') . '.csv';
        File::put($tempPath, $csvContent);

        try {
            $result = PromotionalCodeImporter::import($tempPath);

            $this->assertSame(5, $result['total']); // 5 non-empty rows excluding header
            $this->assertSame(3, $result['inserted']); // 3 new unique codes
            $this->assertSame(2, $result['skipped']); // 1 existing + 1 duplicate in file

            $this->assertDatabaseHas('promotional_codes', [
                'code' => '4DUJQ6ASZ392Z0EARXBLL64',
                'assigned_email' => null,
            ]);

            $this->assertDatabaseHas('promotional_codes', [
                'code' => '1V9L9TGAP3FZT5RRFCQXW8L',
                'assigned_email' => null,
            ]);

            $this->assertDatabaseHas('promotional_codes', [
                'code' => 'HCMNAPQA2XW42ZQC44GWYZX',
                'assigned_email' => null,
            ]);
        } finally {
            if (File::exists($tempPath)) {
                File::delete($tempPath);
            }
        }
    }
}

