<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UploadController extends Controller
{
    public function index(): View
    {
        $uploads = Upload::latest()->get();

        return view('uploads.index', compact('uploads'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ]);

        $file        = $request->file('csv_file');
        $originalName = $file->getClientOriginalName();
        $filename    = uniqid('csv_', true) . '.csv';

        $file->storeAs('uploads', $filename);

        $upload = Upload::create([
            'filename'      => $filename,
            'original_name' => $originalName,
            'total_rows'    => 0,
            'status'        => 'processing',
        ]);

        $filePath = storage_path('app/uploads/' . $filename);
        $handle   = fopen($filePath, 'r');

        if ($handle === false) {
            $upload->update(['status' => 'failed']);
            return back()->withErrors(['csv_file' => 'Could not open the uploaded file.']);
        }

        // First line is the header row
        $headers = fgetcsv($handle);

        if ($headers === false || empty($headers)) {
            fclose($handle);
            $upload->update(['status' => 'failed']);
            return back()->withErrors(['csv_file' => 'CSV file has no header row.']);
        }

        // Trim any BOM or whitespace from header names
        $headers = array_map('trim', $headers);

        DB::beginTransaction();

        try {
            $chunk     = [];
            $totalRows = 0;
            $now       = now();

            while (($row = fgetcsv($handle)) !== false) {
                // Skip completely empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Pad row if it has fewer columns than headers
                $row = array_pad($row, count($headers), null);

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

            // Insert any remaining rows under 1000
            if (!empty($chunk)) {
                DB::table('records')->insert($chunk);
            }

            $upload->update([
                'total_rows' => $totalRows,
                'status'     => 'completed',
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();

            $upload->update(['status' => 'failed']);

            Log::error('CSV processing failed', [
                'upload_id' => $upload->id,
                'error'     => $e->getMessage(),
            ]);

            fclose($handle);

            return back()->withErrors(['csv_file' => 'Processing failed: ' . $e->getMessage()]);
        }

        fclose($handle);

        return redirect()->route('uploads.index')
            ->with('success', "CSV imported successfully — {$totalRows} rows processed.");
    }
}
