<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Dokumen — SIMQOH</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 24px; }
        .card { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .sub { color: #6b7280; font-size: 13px; margin-bottom: 20px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .valid { background: #d1fae5; color: #047857; }
        .invalid { background: #fee2e2; color: #b91c1c; }
        .dl { display: grid; grid-template-columns: 160px 1fr; gap: 8px 12px; font-size: 14px; margin-top: 16px; }
        .dl dt { color: #9ca3af; }
        .dl dd { margin: 0; font-weight: 600; color: #111827; }
        .alert { margin-top: 16px; padding: 12px; border-radius: 8px; font-size: 14px; }
        .alert-danger { background: #fee2e2; color: #b91c1c; }
        .alert-success { background: #d1fae5; color: #047857; }
        .alert-neutral { background: #f3f4f6; color: #374151; }
        hr { border: none; border-top: 1px solid #e5e7eb; margin: 20px 0; }
        input[type="file"], input[type="text"] { width: 100%; box-sizing: border-box; padding: 8px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; margin-top: 4px; }
        button { margin-top: 10px; width: 100%; background: #047857; color: #fff; border: none; border-radius: 8px; padding: 10px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .error { color: #b91c1c; font-size: 13px; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Verifikasi Dokumen</h1>
        <p class="sub">SIMQOH · Yayasan Pondok Pesantren Qomarul Hidayah</p>

        @if($decree->status === \App\Enums\DecreeStatus::Cancelled)
            <span class="badge invalid">⛔ DIBATALKAN</span>
            <div class="alert alert-danger">
                Dokumen ini telah <b>dibatalkan</b> pada {{ optional($decree->updated_at)->translatedFormat('d F Y') }}.
                @if($decree->cancellation_reason)
                    Alasan: {{ $decree->cancellation_reason }}
                @endif
            </div>
        @elseif($decree->status === \App\Enums\DecreeStatus::Superseded)
            <span class="badge invalid">⛔ DIGANTI</span>
            <div class="alert alert-danger">
                Dokumen ini telah <b>digantikan</b>.
                @if($replacementNumber)
                    SK pengganti: {{ $replacementNumber }}
                @endif
            </div>
        @elseif($decree->status === \App\Enums\DecreeStatus::Issued)
            <span class="badge valid">✅ SK VALID</span>
        @else
            <span class="badge invalid">TIDAK BERLAKU</span>
            <div class="alert alert-neutral">Dokumen ini belum diterbitkan secara sah.</div>
        @endif

        <dl class="dl">
            <dt>Nomor SK</dt>
            <dd>{{ $decree->decree_number ?? '—' }}</dd>
            <dt>Nama</dt>
            <dd>{{ $snapshot['name'] ?? $decree->employee?->name ?? '—' }}</dd>
            <dt>NIGY</dt>
            <dd>{{ $snapshot['nigy'] ?? $decree->employee?->nigy ?? '—' }}</dd>
            <dt>Satuan Kerja</dt>
            <dd>{{ $snapshot['work_unit'] ?? '—' }}</dd>
            <dt>Jabatan</dt>
            <dd>{{ $snapshot['position'] ?? '—' }}</dd>
            <dt>Jenis SK</dt>
            <dd>{{ $decree->decreeType?->name ?? '—' }}</dd>
            <dt>Tanggal Terbit</dt>
            <dd>{{ optional($decree->signed_at)->translatedFormat('d F Y') ?? optional($decree->issued_date)->translatedFormat('d F Y') ?? '—' }}</dd>
            <dt>Penanda Tangan</dt>
            <dd>{{ $decree->signature?->signer_name ?? $snapshot['chairman_name'] ?? '—' }}</dd>
            <dt>Waktu Penandatanganan</dt>
            <dd>{{ optional($decree->signature?->signed_at)->translatedFormat('d F Y H:i') ?? '—' }}</dd>
        </dl>

        <hr>
        <h2 style="font-size:15px; margin:0 0 12px;">Verifikasi Mandiri Berkas PDF</h2>
        <form method="POST" action="{{ route('verification.file') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="uuid" value="{{ $decree->uuid }}">
            <label style="font-size:13px; color:#6b7280;">Unggah PDF yang Anda miliki untuk membandingkan keasliannya.</label>
            <input type="file" name="file" accept="application/pdf" required>
            @error('file') <p class="error">{{ $message }}</p> @enderror
            <button type="submit">Periksa Keaslian</button>
        </form>

        @if(session('result'))
            <div class="alert {{ session('result.matches') ? 'alert-success' : 'alert-danger' }}" style="margin-top:16px;">
                @if(session('result.matches'))
                    ✅ PDF <b>ASLI</b> — cocok dengan berkas resmi {{ session('result.decree_number') }}.
                @else
                    ⛔ PDF <b>TIDAK cocok</b> dengan berkas resmi {{ session('result.decree_number') }}. Berkas kemungkinan telah diubah.
                @endif
                <div style="font-family:monospace; font-size:11px; margin-top:6px;">
                    hash berkas: {{ session('result.provided') }}<br>
                    hash resmi: {{ session('result.expected') }}
                </div>
            </div>
        @endif
    </div>
</body>
</html>
