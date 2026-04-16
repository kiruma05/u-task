<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Record;
use App\Models\Upload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    /**
     * GET /api/records
     *
     * Query parameters:
     *   search      string   — search across all JSON fields
     *   upload_id   int      — filter by upload
     *   sort        string   — column to sort by (id, upload_id, created_at, or any JSON key)
     *   direction   asc|desc — sort direction (default: asc)
     *   per_page    10|25|50 — page size (default: 25)
     *   page        int      — page number (default: 1)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage  = in_array((int) $request->per_page, [10, 25, 50]) ? (int) $request->per_page : 25;
        $search   = trim($request->input('search', ''));
        $uploadId = $request->integer('upload_id') ?: null;
        $sortDir  = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $sortCol  = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('sort', 'id')) ?: 'id';

        $nativeColumns = ['id', 'upload_id', 'created_at'];

        $query = Record::query()->select('id', 'upload_id', 'data', 'created_at');

        if ($uploadId) {
            // Validate upload exists before filtering
            if (!Upload::where('id', $uploadId)->exists()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Upload #{$uploadId} not found.",
                    'data'    => null,
                ], 404);
            }

            $query->where('upload_id', $uploadId);
        }

        if ($search !== '') {
            $query->whereRaw('CAST(data AS CHAR) LIKE ?', ['%' . $search . '%']);
        }

        if (in_array($sortCol, $nativeColumns)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderByRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(data, ?)) {$sortDir}",
                ['$.' . $sortCol]
            );
        }

        $paginator = $query->paginate($perPage);

        // Decode JSON data for each record in the current page
        $records = $paginator->getCollection()->map(function (Record $record): array {
            return [
                'id'          => $record->id,
                'upload_id'   => $record->upload_id,
                'data'        => $record->data,
                'imported_at' => $record->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'status'     => 'success',
            'message'    => 'Records retrieved successfully.',
            'data'       => $records,
            'pagination' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ]);
    }
}
