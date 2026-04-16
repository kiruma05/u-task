<?php

namespace App\Exports;

use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RecordsExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithCustomChunkSize,
    WithStyles
{
    /**
     * Resolved JSON column names derived from the first matching record.
     */
    private array $columns;

    public function __construct(
        private readonly ?string $search,
        private readonly ?int    $uploadId,
    ) {
        // Resolve columns once at construction — used by headings() and map()
        $first         = $this->buildQuery()->first();
        $this->columns = $first ? array_keys($first->data ?? []) : [];
    }

    // ── Query ────────────────────────────────────────────────────────────────

    public function query(): Builder
    {
        return $this->buildQuery();
    }

    /**
     * Process 500 rows at a time — keeps memory flat regardless of dataset size.
     */
    public function chunkSize(): int
    {
        return 500;
    }

    // ── Shape ────────────────────────────────────────────────────────────────

    public function headings(): array
    {
        $jsonHeadings = array_map(
            fn (string $col) => ucwords(str_replace('_', ' ', $col)),
            $this->columns
        );

        return array_merge(['ID', 'Upload ID', 'Imported At'], $jsonHeadings);
    }

    /**
     * Map a single Record model row into a flat array for the spreadsheet.
     * Called once per row — no accumulation in memory.
     */
    public function map($record): array
    {
        $row = [
            $record->id,
            $record->upload_id,
            $record->created_at->format('Y-m-d H:i:s'),
        ];

        foreach ($this->columns as $col) {
            $row[] = $record->data[$col] ?? '';
        }

        return $row;
    }

    /**
     * Bold the header row.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    private function buildQuery(): Builder
    {
        $query = Record::query()
            ->select('id', 'upload_id', 'data', 'created_at')
            ->orderBy('id');

        if ($this->uploadId) {
            $query->where('upload_id', $this->uploadId);
        }

        if ($this->search !== null && $this->search !== '') {
            $query->whereRaw('CAST(data AS CHAR) LIKE ?', ['%' . $this->search . '%']);
        }

        return $query;
    }
}
