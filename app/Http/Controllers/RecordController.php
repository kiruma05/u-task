<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecordController extends Controller
{
    public function index(Request $request): View
    {
        $perPage  = in_array((int) $request->per_page, [10, 25, 50]) ? (int) $request->per_page : 25;
        $search   = trim($request->input('search', ''));
        $uploadId = $request->integer('upload_id') ?: null;
        $sortDir  = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        // Sanitise sort column — alphanumeric + underscores only, prevents injection
        $sortCol = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('sort', 'id')) ?: 'id';

        $nativeColumns = ['id', 'upload_id', 'created_at'];

        $query = Record::query()->select('id', 'upload_id', 'data', 'created_at');

        // Filter by upload
        if ($uploadId) {
            $query->where('upload_id', $uploadId);
        }

        // Full-text search across all JSON fields (cast to char, safe LIKE)
        if ($search !== '') {
            $query->whereRaw('CAST(data AS CHAR) LIKE ?', ['%' . $search . '%']);
        }

        // Sort — native column uses orderBy; JSON key uses parameterised JSON_EXTRACT
        if (in_array($sortCol, $nativeColumns)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderByRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(data, ?)) {$sortDir}",
                ['$.' . $sortCol]
            );
        }

        // Paginate without loading all rows — appends current query string to page links
        $records = $query->paginate($perPage)->withQueryString();

        // Derive column names dynamically from the first visible record
        $columns = $records->isNotEmpty()
            ? array_keys($records->first()->data ?? [])
            : [];

        $uploads = Upload::select('id', 'original_name')->latest()->get();

        return view('records.index', compact(
            'records',
            'columns',
            'uploads',
            'perPage',
            'search',
            'uploadId',
            'sortCol',
            'sortDir'
        ));
    }
}
