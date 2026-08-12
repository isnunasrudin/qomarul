<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .meta { color: #6b7280; font-size: 11px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #eef2f6; text-align: left; padding: 6px 8px; font-size: 11px; text-transform: uppercase; }
        td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">SIMQOH — YPP Qomarul Hidayah · dibuat {{ $generated_at }}</p>
    <table>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $column)
                        <td>{{ data_get($row, $column['key']) ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}" style="text-align:center;color:#9ca3af;">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
