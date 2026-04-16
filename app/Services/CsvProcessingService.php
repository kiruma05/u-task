<?php

namespace App\Services;

use App\Models\Upload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsvProcessingService
{
    /**
     * Process a CSV file row-by-row and persist records in chunks.
     *
     * Returns an array:
     *   ['success' => bool, 'total_rows' => int, 'error' => string|null]
     */
    public function process(Upload $upload, string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            $upload->update(['status' => 'failed']);

            return ['success' => false, 'total_rows' => 0, 'error' => 'Could not open the uploaded file.'];
        }

        // First row is always the header
        $headers = fgetcsv($handle);

        if ($headers === false || empty($headers)) {
            fclose($handle);
            $upload->update(['status' => 'failed']);

            return ['success' => false, 'total_rows' => 0, 'error' => 'CSV file has no header row.'];
        }

        // Strip BOM / whitespace from header names
        $headers = array_map('trim', $headers);

        DB::beginTransaction();

        try {
            $chunk     = [];
            $totalRows = 0;
            $now       = now();

            while (($row = fgetcsv($handle)) !== false) {
                // Skip blank rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Pad short rows so array_combine never throws
                $row         = array_pad($row, count($headers), null);
                $associative = array_combine($headers, $row);

                $chunk[] = [
                    'upload_id'  => $upload->id,
                    'data'       => json_encode($associative),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $totalRows++;

                if (count($chunk) === 1000) {
                    DB::table('records')->insert($chunk);
                    $chunk = [];
                }
            }

            // Flush remainder
            if (!empty($chunk)) {
                DB::table('records')->insert($chunk);
            }

            $upload->update([
                'total_rows' => $totalRows,
                'status'     => 'completed',
            ]);

            DB::commit();

            return ['success' => true, 'total_rows' => $totalRows, 'error' => null];

        } catch (\Throwable $e) {
            DB::rollBack();

            $upload->update(['status' => 'failed']);

            Log::error('CSV processing failed', [
                'upload_id' => $upload->id,
                'error'     => $e->getMessage(),
            ]);

            return ['success' => false, 'total_rows' => 0, 'error' => $e->getMessage()];

        } finally {
            fclose($handle);
        }
    }
}
