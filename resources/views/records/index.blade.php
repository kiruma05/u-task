<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records — Big Data CSV Manager</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f4f6f9;
            color: #333;
            padding: 40px 20px;
        }

        .container { max-width: 1200px; margin: 0 auto; }

        /* ── Header ── */
        .page-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 8px;
        }

        .page-header h1 { font-size: 1.5rem; color: #1a1a2e; }

        .nav-link {
            font-size: 0.85rem;
            color: #3498db;
            text-decoration: none;
        }

        .nav-link:hover { text-decoration: underline; }

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 10px;
            padding: 22px 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
            margin-bottom: 20px;
        }

        /* ── Filter bar ── */
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }

        .filter-group { display: flex; flex-direction: column; gap: 4px; }

        .filter-group label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #777;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .filter-group select,
        .filter-group input[type="text"] {
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.88rem;
            background: #fafafa;
            min-width: 140px;
            color: #333;
        }

        .filter-group input[type="text"] { min-width: 220px; }

        .btn-filter {
            padding: 8px 18px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            align-self: flex-end;
        }

        .btn-filter:hover { background: #2176ae; }

        .btn-reset {
            padding: 8px 14px;
            background: #f0f0f0;
            color: #555;
            border: none;
            border-radius: 6px;
            font-size: 0.88rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            align-self: flex-end;
        }

        .btn-reset:hover { background: #e0e0e0; }

        /* ── Meta bar ── */
        .meta-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.82rem;
            color: #888;
            margin-bottom: 14px;
            flex-wrap: wrap;
            gap: 6px;
        }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        thead th {
            background: #f8f9fb;
            padding: 10px 14px;
            text-align: left;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #888;
            border-bottom: 2px solid #eee;
            cursor: pointer;
            user-select: none;
        }

        thead th a {
            color: inherit;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        thead th a:hover { color: #3498db; }

        thead th.sorted { color: #3498db; background: #edf5fc; }

        .sort-icon { font-size: 0.7rem; opacity: 0.6; }
        .sort-icon.active { opacity: 1; }

        tbody tr:hover { background: #f9fbfd; }

        tbody td {
            padding: 10px 14px;
            border-bottom: 1px solid #f0f0f0;
            color: #444;
            max-width: 280px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        tbody tr:last-child td { border-bottom: none; }

        .row-id { font-weight: 600; color: #999; font-size: 0.78rem; }

        .empty {
            text-align: center;
            padding: 48px;
            color: #bbb;
            font-size: 0.9rem;
        }

        /* ── Pagination ── */
        .pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .pagination-info { font-size: 0.82rem; color: #888; }

        .pagination {
            display: flex;
            gap: 4px;
            list-style: none;
            flex-wrap: wrap;
        }

        .pagination li a,
        .pagination li span {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 0.82rem;
            text-decoration: none;
            color: #555;
            background: #f0f0f0;
            transition: background .15s;
        }

        .pagination li a:hover { background: #dce8f5; color: #3498db; }

        .pagination li.active span {
            background: #3498db;
            color: #fff;
            font-weight: 700;
        }

        .pagination li.disabled span { opacity: 0.4; cursor: not-allowed; }
    </style>
</head>
<body>
<div class="container">

    {{-- Header --}}
    <div class="page-header">
        <h1>Records</h1>
        <a href="{{ route('uploads.index') }}" class="nav-link">&larr; Back to Uploads</a>
    </div>

    {{-- Filters --}}
    <div class="card">
        <form method="GET" action="{{ route('records.index') }}">
            {{-- Preserve current sort while filtering --}}
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
                <input type="hidden" name="direction" value="{{ request('direction', 'asc') }}">
            @endif

            <div class="filters">
                <div class="filter-group">
                    <label>Upload</label>
                    <select name="upload_id">
                        <option value="">All uploads</option>
                        @foreach ($uploads as $upload)
                            <option value="{{ $upload->id }}" {{ $uploadId == $upload->id ? 'selected' : '' }}>
                                #{{ $upload->id }} — {{ $upload->original_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search all fields…">
                </div>

                <div class="filter-group">
                    <label>Per page</label>
                    <select name="per_page">
                        @foreach ([10, 25, 50] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-filter">Apply</button>
                <a href="{{ route('records.index') }}" class="btn-reset">Reset</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="card">

        {{-- Meta info --}}
        <div class="meta-bar">
            <span>
                Showing {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }}
                of {{ number_format($records->total()) }} records
                @if($search) &nbsp;·&nbsp; filtered by <strong>"{{ $search }}"</strong> @endif
                @if($uploadId) &nbsp;·&nbsp; upload <strong>#{{ $uploadId }}</strong> @endif
            </span>
            <span>Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</span>
        </div>

        @if($records->isEmpty())
            <p class="empty">No records found. Try adjusting your filters.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            {{-- Fixed columns --}}
                            @foreach (['id' => '#', 'upload_id' => 'Upload', 'created_at' => 'Imported At'] as $col => $label)
                                @php
                                    $isActive  = $sortCol === $col;
                                    $nextDir   = ($isActive && $sortDir === 'asc') ? 'desc' : 'asc';
                                    $sortUrl   = request()->fullUrlWithQuery(['sort' => $col, 'direction' => $nextDir]);
                                @endphp
                                <th class="{{ $isActive ? 'sorted' : '' }}">
                                    <a href="{{ $sortUrl }}">
                                        {{ $label }}
                                        <span class="sort-icon {{ $isActive ? 'active' : '' }}">
                                            {{ $isActive ? ($sortDir === 'asc' ? '▲' : '▼') : '⇅' }}
                                        </span>
                                    </a>
                                </th>
                            @endforeach

                            {{-- Dynamic JSON columns --}}
                            @foreach ($columns as $col)
                                @php
                                    $isActive = $sortCol === $col;
                                    $nextDir  = ($isActive && $sortDir === 'asc') ? 'desc' : 'asc';
                                    $sortUrl  = request()->fullUrlWithQuery(['sort' => $col, 'direction' => $nextDir]);
                                @endphp
                                <th class="{{ $isActive ? 'sorted' : '' }}">
                                    <a href="{{ $sortUrl }}">
                                        {{ ucwords(str_replace('_', ' ', $col)) }}
                                        <span class="sort-icon {{ $isActive ? 'active' : '' }}">
                                            {{ $isActive ? ($sortDir === 'asc' ? '▲' : '▼') : '⇅' }}
                                        </span>
                                    </a>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr>
                                <td class="row-id">{{ $record->id }}</td>
                                <td>#{{ $record->upload_id }}</td>
                                <td>{{ $record->created_at->format('M d, Y H:i') }}</td>
                                @foreach ($columns as $col)
                                    <td title="{{ $record->data[$col] ?? '' }}">
                                        {{ $record->data[$col] ?? '—' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="pagination-wrap">
                <span class="pagination-info">
                    {{ number_format($records->total()) }} total records
                </span>
                {{ $records->links() }}
            </div>
        @endif
    </div>

</div>
</body>
</html>
