<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Big Data CSV Manager</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f4f6f9;
            color: #333;
            padding: 40px 20px;
        }

        .container { max-width: 820px; margin: 0 auto; }

        h1 {
            font-size: 1.6rem;
            margin-bottom: 4px;
            color: #1a1a2e;
        }

        .subtitle {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 32px;
        }

        .card {
            background: #fff;
            border-radius: 10px;
            padding: 28px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
            margin-bottom: 28px;
        }

        .card h2 { font-size: 1rem; margin-bottom: 18px; color: #444; }

        .alert {
            padding: 12px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-success { background: #e6f9f0; color: #1a7a4a; border-left: 4px solid #27ae60; }
        .alert-error   { background: #fdecea; color: #a93226; border-left: 4px solid #e74c3c; }

        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: #555;
        }

        input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border: 2px dashed #ccc;
            border-radius: 7px;
            font-size: 0.9rem;
            background: #fafafa;
            cursor: pointer;
            transition: border-color .2s;
        }

        input[type="file"]:hover { border-color: #3498db; }

        .hint { font-size: 0.78rem; color: #999; margin-top: 5px; }

        .btn {
            display: inline-block;
            padding: 10px 24px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
        }

        .btn:hover { background: #2176ae; }

        /* Uploads table */
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th { text-align: left; padding: 10px 12px; border-bottom: 2px solid #eee; color: #888; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; letter-spacing: .04em; }
        td { padding: 11px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-processing { background: #fff3cd; color: #856404; }
        .badge-completed  { background: #d1f2eb; color: #1a7a4a; }
        .badge-failed     { background: #fdecea; color: #a93226; }

        .empty { text-align: center; padding: 30px; color: #aaa; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="container">

    <div style="display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;gap:8px;">
        <div>
            <h1>Big Data CSV Manager</h1>
            <p class="subtitle">Upload large CSV files — rows are processed in chunks and stored as structured records.</p>
        </div>
        <a href="{{ route('records.index') }}" style="font-size:.85rem;color:#3498db;text-decoration:none;">
            View All Records &rarr;
        </a>
    </div>

    {{-- Success message --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-error">
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </div>
    @endif

    {{-- Upload form --}}
    <div class="card">
        <h2>Upload a CSV File</h2>
        <form action="{{ route('uploads.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="csv_file">Select CSV File</label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv">
                <p class="hint">Accepted format: .csv &nbsp;|&nbsp; Max size: 20MB</p>
            </div>
            <button type="submit" class="btn">Upload &amp; Process</button>
        </form>
    </div>

    {{-- Upload history --}}
    <div class="card">
        <h2>Upload History</h2>

        @if ($uploads->isEmpty())
            <p class="empty">No uploads yet. Upload a CSV file above to get started.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>File Name</th>
                        <th>Total Rows</th>
                        <th>Status</th>
                        <th>Uploaded At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($uploads as $upload)
                        <tr>
                            <td>{{ $upload->id }}</td>
                            <td>{{ $upload->original_name }}</td>
                            <td>{{ number_format($upload->total_rows) }}</td>
                            <td>
                                <span class="badge badge-{{ $upload->status }}">
                                    {{ ucfirst($upload->status) }}
                                </span>
                            </td>
                            <td>{{ $upload->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>
</body>
</html>
