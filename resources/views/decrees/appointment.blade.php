@php
    $configuredLogo = $foundation_logo ? public_path($foundation_logo) : null;
    $letterheadLogo = $configuredLogo && is_file($configuredLogo)
        ? $configuredLogo
        : resource_path('images/kop-yayasan-logo.png');
    $letterheadLogoData = 'data:image/'.pathinfo($letterheadLogo, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($letterheadLogo));
    $arialFontData = base64_encode(file_get_contents(public_path('fonts/arial.ttf')));
    $arialBoldFontData = base64_encode(file_get_contents(public_path('fonts/arialbd.ttf')));
    $arabicHeaderData = base64_encode(file_get_contents(resource_path('images/Yayasan.svg')));
    $basmalaData = base64_encode(file_get_contents(resource_path('images/Bismillah.svg')));
    // Tanggal penetapan: baris Masehi selalu ada, baris Hijriah bila tersedia.
    // `issued_date` snapshot berbentuk "10 Juli 2026"; snapshot lama bisa memuat
    // "10 Juli 2026 M / 25 Muharam 1448 H", jadi tetap dipecah sebagai fallback.
    $issuedDateLines = preg_split('/\s*\/\s*/', (string) $issued_date, 2);
    $issuedDateGregorian = trim((string) ($issuedDateLines[0] ?? ''));
    if ($issuedDateGregorian !== '' && ! str_ends_with($issuedDateGregorian, 'M')) {
        $issuedDateGregorian .= ' M';
    }
    $issuedDateHijri = trim((string) ($issued_date_hijri ?? ($issuedDateLines[1] ?? '')));
    $academicYearParts = preg_split('/\s*\/\s*/', (string) $academic_year, 2);
    $validUntil = isset($academicYearParts[1]) && preg_match('/^\d{4}$/', $academicYearParts[1])
        ? '30 Juni '.$academicYearParts[1]
        : 'akhir tahun pelajaran '.$academic_year;

    // Seluruh gaya di berkas ini memakai inline style; struktur elemen tidak diubah.
    // Hanya dua at-rule di bawah yang tidak punya padanan inline:
    // @font-face (menanam Arial) dan @page (margin kertas F4).
    $base = "color: #000; font-family: 'Arial SK', Arial, sans-serif; font-size: 11pt; line-height: 1.08;";
@endphp

<style>
    @font-face {
        font-family: 'Arial SK';
        font-style: normal;
        font-weight: normal;
        src: url('data:font/truetype;charset=utf-8;base64,{{ $arialFontData }}') format('truetype');
    }

    @font-face {
        font-family: 'Arial SK';
        font-style: normal;
        font-weight: bold;
        src: url('data:font/truetype;charset=utf-8;base64,{{ $arialBoldFontData }}') format('truetype');
    }

    @page {
        margin: 0.8cm 1.5cm 0.8cm;
    }
</style>

@if(! $is_signed)
    <div style="{{ $base }} color: rgba(128, 0, 0, .15); font-size: 42px; font-weight: bold; left: 0; position: fixed; right: 0; text-align: center; top: 45%; transform: rotate(-25deg);">DRAFT - BUKAN DOKUMEN RESMI</div>
@endif

<div style="{{ $base }} text-align: center;">
    <div>
        <img src="{{ $letterheadLogoData }}" alt="Logo Yayasan Pondok Pesantren Qomarul Hidayah" style="height: 17.9mm;">
    </div>
    <div>
        <img src="data:image/svg;base64,{{ $arabicHeaderData }}" alt="مؤسسة المعهد قمر الهداية الإسلامي" style="width: 15px; display: block; margin-left: -350px; margin-bottom: 35px; margin-top: 5px;">
    </div>
    <div style="font-size: 14pt; font-weight: bolder; line-height: 1; text-align: center; text-transform: uppercase; color: #007e39;">{{ $foundation_name }}</div>
    <div style="font-size: 11pt; line-height: 1; margin-top: 2px; text-align: center; color: #007e39;">
        {{ $foundation['address'] ?? 'Gondang - Tugu - Trenggalek - Jawa Timur' }}
    </div>
</div>

<div style="{{ $base }} text-align: center;">
    <img src="data:image/svg;base64,{{ $basmalaData }}" alt="بِسْمِ اللهِ الرَّحْمَنِ الرَّحِيْمِ" style="width: 7px; display: block; margin-left: -180px; margin-bottom: 45px; margin-top: 15px;">
</div>

<div style="{{ $base }} margin: 0 0 9px; text-align: center;">
    <div style="font-size: 12pt; font-weight: bold; text-decoration: underline;">SURAT KEPUTUSAN</div>
    <div>Nomor : {{ $decree_number }}</div>
</div>

<p style="{{ $base }} margin: 0 0 3px;">Pimpinan {{ $foundation_name }} :</p>

<table style="{{ $base }} border-collapse: collapse; border-spacing: 0; width: 100%; text-align: justify;">
    <tr>
        <td style="width: 18.9%; padding: 0; vertical-align: top;">Mengingat</td>
        <td style="width: 2.7%; padding: 0; vertical-align: top;">:</td>
        <td style="padding: 0; vertical-align: top;">{{ $consideration_recalling }}</td>
    </tr>
    @foreach($consideration_weighing as $index => $item)
        <tr>
            @if($index === 0)
                <td style="width: 18.9%; padding: 0; vertical-align: top;">Menimbang</td>
                <td style="width: 2.7%; padding: 0; vertical-align: top;">:</td>
            @else
                <td style="padding: 0; vertical-align: top;"></td>
                <td style="padding: 0; vertical-align: top;"></td>
            @endif
            <td style="padding: 0; vertical-align: top;">
                <table style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <tr>
                        <td style="width: 6mm; padding: 0; vertical-align: top;">{{ $index + 1 }}.</td>
                        <td style="padding: 0; vertical-align: top;">{{ $item }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    @endforeach
    <tr>
        <td style="width: 18.9%; padding: 0; vertical-align: top;">Memperhatikan</td>
        <td style="width: 2.7%; padding: 0; vertical-align: top;">:</td>
        <td style="padding: 0; vertical-align: top;">{{ $consideration_observing }}</td>
    </tr>
</table>

<div style="{{ $base }} font-weight: bold; margin: 3px 0; text-align: center;">MEMUTUSKAN</div>

<table style="{{ $base }} border-collapse: collapse; border-spacing: 0; width: 100%; text-align: justify;">
    <tr>
        <td style="width: 18.9%; padding: 0; vertical-align: top;">Menetapkan</td>
        <td style="width: 2.7%; padding: 0; vertical-align: top;">:</td>
        <td style="padding: 0; vertical-align: top;"></td>
    </tr>
    <tr>
        <td style="width: 18.9%; padding: 0; vertical-align: top;">Pertama</td>
        <td style="width: 2.7%; padding: 0; vertical-align: top;">:</td>
        <td style="padding: 0; vertical-align: top;">Terhitung Mulai Tanggal <b>{{ $effective_date }}</b> mengangkat:</td>
    </tr>
    <tr>
        <td style="padding: 0; vertical-align: top;"></td>
        <td style="padding: 0; vertical-align: top;"></td>
        <td style="padding: 0; vertical-align: top;">
            <table style="border-collapse: collapse; border-spacing: 0; margin: 1px 0 2px; width: 100%;">
                <tr>
                    <td style="width: 43mm; padding: 0; vertical-align: top;">Nama</td>
                    <td style="width: 5mm; padding: 0; vertical-align: top;">:</td>
                    <td style="font-weight: bold; padding: 0; vertical-align: top;">{{ $name }}</td>
                </tr>
                <tr>
                    <td style="width: 43mm; padding: 0; vertical-align: top;">NIGY</td>
                    <td style="width: 5mm; padding: 0; vertical-align: top;">:</td>
                    <td style="font-weight: bold; padding: 0; vertical-align: top;">{{ $nigy ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="width: 43mm; padding: 0; vertical-align: top;">Tempat, Tanggal Lahir</td>
                    <td style="width: 5mm; padding: 0; vertical-align: top;">:</td>
                    <td style="font-weight: bold; padding: 0; vertical-align: top;">{{ $birth_place . ($birth_place && $birth_date ? ', ' : '') . $birth_date }}</td>
                </tr>
                <tr>
                    <td style="width: 43mm; padding: 0; vertical-align: top;">Pendidikan/Jurusan</td>
                    <td style="width: 5mm; padding: 0; vertical-align: top;">:</td>
                    <td style="font-weight: bold; padding: 0; vertical-align: top;">{{ $education_level ?? '-' }}{{ $major ? ' / '.$major : '' }}</td>
                </tr>
                <tr>
                    <td style="width: 43mm; padding: 0; vertical-align: top;">Jabatan</td>
                    <td style="width: 5mm; padding: 0; vertical-align: top;">:</td>
                    <td style="font-weight: bold; padding: 0; vertical-align: top;">{{ $position }}</td>
                </tr>
                <tr>
                    <td style="width: 43mm; padding: 0; vertical-align: top;">Diangkat kembali sebagai</td>
                    <td style="width: 5mm; padding: 0; vertical-align: top;">:</td>
                    <td style="font-weight: bold; padding: 0; vertical-align: top;">{{ $appointed_as }}</td>
                </tr>
                <tr>
                    <td style="width: 43mm; padding: 0; vertical-align: top;">Satuan Kerja</td>
                    <td style="width: 5mm; padding: 0; vertical-align: top;">:</td>
                    <td style="font-weight: bold; padding: 0; vertical-align: top;">{{ $work_unit }}</td>
                </tr>
                <tr>
                    <td style="width: 43mm; padding: 0; vertical-align: top;">TMT Yayasan</td>
                    <td style="width: 5mm; padding: 0; vertical-align: top;">:</td>
                    <td style="font-weight: bold; padding: 0; vertical-align: top;">{{ $foundation_start_date }}</td>
                </tr>
                <tr>
                    <td style="width: 43mm; padding: 0; vertical-align: top;">Masa Kerja Keseluruhan</td>
                    <td style="width: 5mm; padding: 0; vertical-align: top;">:</td>
                    <td style="font-weight: bold; padding: 0; vertical-align: top;">{{ $service_years ?? 0 }} Tahun{{ ($service_months ?? 0) > 0 ? ' '.($service_months ?? 0).' Bulan' : '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="width: 18.9%; padding: 0; vertical-align: top;">Kedua</td>
        <td style="width: 2.7%; padding: 0; vertical-align: top;">:</td>
        <td style="padding: 0; vertical-align: top;">Kepada Saudara yang tersebut di atas memiliki tugas dan tanggung jawab memajukan dan meningkatkan Pendidikan dan Pengajaran di {{ $work_unit }};</td>
    </tr>
    <tr>
        <td style="width: 18.9%; padding: 0; vertical-align: top;">Ketiga</td>
        <td style="width: 2.7%; padding: 0; vertical-align: top;">:</td>
        <td style="padding: 0; vertical-align: top;">Surat Keputusan ini berlaku mulai tanggal {{ $effective_date }} sampai dengan tanggal {{ $validUntil }}, dan apabila dikemudian terdapat kekeliruan akan diadakan perbaikan sebagaimana mestinya;</td>
    </tr>
    <tr>
        <td style="width: 18.9%; padding: 0; vertical-align: top;">Keempat</td>
        <td style="width: 2.7%; padding: 0; vertical-align: top;">:</td>
        <td style="padding: 0; vertical-align: top;">Asli Surat Keputusan ini diberikan kepada yang bersangkutan untuk dipergunakan sebagaimana mestinya.</td>
    </tr>
</table>

<table style="{{ $base }} border-collapse: collapse; border-spacing: 0; line-height: 1; margin: 4px 0 3mm 110mm; width: auto;">
    <tr>
        <td style="width: 26.5mm; padding: 1px 0; vertical-align: top;">Ditetapkan di</td>
        <td style="width: 1.9mm; padding: 1px 0; vertical-align: top;">:</td>
        <td style="padding: 1px 0; vertical-align: top;">{{ $issued_place }}</td>
    </tr>
    <tr style="line-height: 1;">
        <td style="padding: 1px 0; vertical-align: top;">Pada tanggal</td>
        <td style="width: 1.9mm; padding: 0 0; vertical-align: top;">:</td>
        <td style="padding: 1px 0; vertical-align: top; border-bottom: 0.5pt solid #000;">{{ $issuedDateGregorian }}</td>
    </tr>
    @if($issuedDateHijri !== '')
        <tr style="line-height: 1;">
            <td style="vertical-align: top;"></td>
            <td style="vertical-align: top;"></td>
            <td style="vertical-align: top;">{{ $issuedDateHijri }}</td>
        </tr>
    @endif
</table>

{{-- Tanda tangan elektronik: QR verifikasi + pernyataan penandatangan --}}
<table style="{{ $base }} border: 0.5pt solid #000; border-collapse: collapse; border-spacing: 0; margin: 1.3mm 0 0 87mm; width: 87mm;">
    <tr>
        <td style="padding: 2mm; padding-right: 0">
            @if(! empty($qr_data_uri))
                <img src="{{ $qr_data_uri }}" alt="QR verifikasi keaslian dokumen" style="display: block; height: 20mm; width: 20mm;">
            @endif
        </td>
        <td style="border: 0; padding: 2mm; padding-left: 0; vertical-align: top; line-height: 1">
            <div style="font-size: 8.7pt;">Ditandatangani secara elektronik oleh:</div>
            <div style="font-size: 9.6pt; font-weight: bold;">{{ $chairman_position }}</div>
            <div style="font-size: 9.6pt; font-weight: bold; margin-top: 7.3mm;">{{ $chairman_name }}</div>
        </td>
    </tr>
</table>

<div style="{{ $base }} font-size: 11pt; margin-top: 4.7mm;">
    <div>Tembusan disampaikan kepada:</div>
    <ol style="margin: 0 0 0 6.25mm; padding: 0;">
        @foreach($cc_list ?? [] as $cc)
            <li>{{ str_replace('{satker}', $work_unit, $cc) }}</li>
        @endforeach
    </ol>
</div>

@if(! empty($registration_number))
    <div style="{{ $base }} bottom: 7mm; font-size: 9pt; position: fixed; right: 15mm;"><b>Reg.</b> {{ $registration_number }}</div>
@endif

<div style="{{ $base }} bottom: 0; color: #007e39; font-family: 'Times New Roman'; font-size: 10pt; font-style: italic; left: 0; padding: 1px 0; position: fixed; right: 0; text-align: center;">Berilmu Amaliyah, Beramal Ilmiyah</div>
