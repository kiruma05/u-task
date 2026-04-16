<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Services\CsvProcessingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UploadController extends Controller
{
    public function __construct(private readonly CsvProcessingService $processor) {}

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
            return back()->withErrors(['csv_file' => $result['error']]);
        }

        return redirect()->route('uploads.index')
            ->with('success', "CSV imported successfully — {$result['total_rows']} rows processed.");
    }
}
