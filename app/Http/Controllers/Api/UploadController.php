<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Services\CsvProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(private readonly CsvProcessingService $processor) {}

    /**
     * POST /api/upload-csv
     *
     * Accept a CSV file, persist it, and process all rows into the records table.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'data'    => ['errors' => $validator->errors()],
            ], 422);
        }

        $file     = $request->file('csv_file');
        $filename = uniqid('csv_', true) . '.csv';

        $file->storeAs('uploads', $filename);

        $upload = Upload::create([
            'filename'      => $filename,
            'original_name' => $file->getClientOriginalName(),
            'total_rows'    => 0,
            'status'        => 'processing',
        ]);

        $result = $this->processor->process($upload, storage_path('app/uploads/' . $filename));

        if (!$result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['error'],
                'data'    => ['upload_id' => $upload->id],
            ], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => "CSV imported successfully — {$result['total_rows']} rows processed.",
            'data'    => [
                'upload_id'     => $upload->id,
                'original_name' => $upload->original_name,
                'total_rows'    => $result['total_rows'],
                'status'        => 'completed',
            ],
        ], 201);
    }
}
