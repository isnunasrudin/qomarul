<style>
    @font-face {
        font-family: 'Arial';
        src: url({{ public_path('fonts/arial.ttf') }}) format('truetype');
        font-weight: normal;
        font-style: normal;
    }

    @font-face {
        font-family: 'Arial';
        src: url({{ public_path('fonts/arialbd.ttf') }}) format('truetype');
        font-weight: bold;
        font-style: normal;
    }

    body {
        font-family: 'Arial', sans-serif;
        font-size: 13.5px;
        margin: .8cm 1.5cm .5cm 1.5cm;
        line-height: 1.1;
    }

    * {
        margin: 0;
        padding: 0;
    }

    td {
        vertical-align: top;
    }

    table {
        width: 100%;
    }

    .konsideran {
        text-align: justify;
    }

    .biodata td:last-child {
        font-weight: bold;
    }

    .watermark {
        position: fixed;
        top: 45%;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 48px;
        font-weight: bold;
        color: rgba(220, 38, 38, 0.18);
        z-index: 999;
    }
</style>

@if(! $is_signed)
    <div class="watermark">DRAFT — BUKAN DOKUMEN RESMI</div>
@endif

<table>
    <tr>
        <td style="width: 100px;">
            @if($foundation_logo)
                <img src="{{ public_path($foundation_logo) }}" style="width: 100px; height: 90px;">
            @endif
        </td>
        <td style="text-align: center; width: 100%; line-height: 1.4;">
            <p style="font-size: 21px; font-weight: bold;">{{ strtoupper($foundation_name) }}</p>
            <p style="font-size: 14px; font-weight: bold;">AKTE NOTARIS: {{ $notary_deed }}</p>
            <p style="font-size: 14px;">SK MENKUMHAM RI NOMOR: {{ $sk_menkumham }}</p>
        </td>
    </tr>
</table>

<div style="background: #029340; width: 100%; height: 8px; margin: 10px 0"></div>

<div style="text-align: center; margin-bottom: 15px; font-size: 15px;">
    <p style="font-weight: bold;">SURAT KEPUTUSAN</p>
    <p style="font-weight: bold;">{{ strtoupper($foundation_name) }}</p>
    <p style="font-weight: bold;">GONDANG TUGU TRENGGALEK</p>
    <p style="font-size: 13px; padding-top: 3px">Nomor : <b>{{ $decree_number }}</b></p>
</div>

<p style="margin-bottom: 5px;">{{ $foundation_name }} :</p>
<table class="konsideran">
    <tr>
        <td style="width: 100px;">Mengingat</td>
        <td style="width: 10px;">:</td>
        <td>{{ $consideration_recalling }}</td>
    </tr>
    @foreach($consideration_weighing as $index => $item)
        <tr>
            @if($index === 0)
                <td style="width: 100px;">Menimbang</td>
                <td style="width: 10px;">:</td>
            @else
                <td></td>
                <td></td>
            @endif
            <td style="width: 15px; text-align: right;">{{ $index + 1 }}.</td>
            <td style="width: 100%;">{{ $item }}</td>
        </tr>
    @endforeach
    <tr>
        <td style="width: 100px;">Memperhatikan</td>
        <td style="width: 10px;">:</td>
        <td>{{ $consideration_observing }}</td>
    </tr>
</table>

<p style="text-align: center; font-weight: bold;">MEMUTUSKAN</p>
<table>
    <tr>
        <td style="width: 100px;">Menetapkan</td>
        <td style="width: 10px;">:</td>
        <td></td>
    </tr>
    <tr>
        <td style="width: 100px;">Pertama</td>
        <td style="width: 10px;">:</td>
        <td>Terhitung Mulai Tanggal <b>{{ $effective_date }}</b> mengangkat :</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td>
            <table class="biodata">
                <tr>
                    <td style="width: 160px">Nama</td>
                    <td style="width: 10px;">:</td>
                    <td style="width: 100%;">{{ $name }}</td>
                </tr>
                <tr>
                    <td>NIGY</td>
                    <td>:</td>
                    <td>{{ $nigy }}</td>
                </tr>
                <tr>
                    <td>Tempat/Tanggal Lahir</td>
                    <td>:</td>
                    <td>{{ $birth_place . ($birth_place && $birth_date ? ', ' : '') . $birth_date }}</td>
                </tr>
                <tr>
                    <td>Pendidikan/Jurusan</td>
                    <td>:</td>
                    <td>{{ $education_level ?? '-' }}{{ $major ? '/'.$major : '' }}</td>
                </tr>
                <tr>
                    <td>Jabatan</td>
                    <td>:</td>
                    <td>{{ $position }}</td>
                </tr>
                <tr>
                    <td>Diangkat kembali sebagai</td>
                    <td>:</td>
                    <td>{{ $appointed_as }} <span style="font-weight: normal;">Satuan Kerja</span> {{ $work_unit }}</td>
                </tr>
                <tr>
                    <td>TMT Yayasan</td>
                    <td>:</td>
                    <td>{{ $foundation_start_date }}</td>
                </tr>
                <tr>
                    <td>TMT di Satuan Kerja</td>
                    <td>:</td>
                    <td>{{ $unit_start_date }}</td>
                </tr>
                <tr>
                    <td>Masa Kerja Keseluruhan</td>
                    <td>:</td>
                    <td>
                        <span style="padding-right: 10px;">{{ $service_years }}</span>
                        <span style="font-weight: normal;">Tahun</span>
                        <span style="padding-left: 10px; padding-right: 10px;">{{ $service_months }}</span>
                        <span style="font-weight: normal;">Bulan</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr class="konsideran">
        <td>Kedua</td>
        <td>:</td>
        <td>Yang bersangkutan diberikan honorarium sesuai ketentuan {{ $foundation_name }}.</td>
    </tr>
    <tr class="konsideran">
        <td>Ketiga</td>
        <td>:</td>
        <td>Surat Keputusan ini berlaku selama satu tahun pelajaran ({{ $academic_year }}) terhitung sejak tanggal ditetapkan, dan apabila dikemudian hari terdapat kekeliruan akan diadakan perbaikan sebagaimana mestinya.</td>
    </tr>
    <tr class="konsideran">
        <td>Keempat</td>
        <td>:</td>
        <td>Asli Surat Keputusan ini diberikan kepada yang bersangkutan untuk dipergunakan sebagaimana mestinya.</td>
    </tr>
</table>

<div style="padding-left: 10cm">
    <table style="line-height: .9; width: 100%;">
        <tr>
            <td>Ditetapkan di</td>
            <td>:</td>
            <td>{{ $issued_place }}</td>
        </tr>
        <tr>
            <td>Pada tanggal</td>
            <td>:</td>
            <td>{{ $issued_date }}</td>
        </tr>
        <tr>
            <td colspan="3">
                <p style="padding: 0;">{{ $chairman_position }}</p>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="padding: 0; height: 2cm; vertical-align: middle;">
                @if($is_signed && $signature_path)
                    <img src="{{ public_path($signature_path) }}" style="width: 250px;">
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <p style="padding: 0; font-weight: bold; text-decoration: underline;">{{ $chairman_name }}</p>
            </td>
        </tr>
    </table>
</div>

<p style="margin-top: 10px; font-size: 13px;">Tembusan disampaikan kepada :</p>
<ol style="margin-left: 20px; font-size: 13px">
    @foreach($cc_list as $cc)
        <li>{{ str_replace('{satker}', $work_unit, $cc) }}</li>
    @endforeach
</ol>

@if($registration_number)
    <div style="margin-top: 10px; margin-bottom: 0; position: fixed; bottom: .8cm; right: 1.5cm;">
        <b>Reg. </b><span style="font-family: Times New Roman;">{{ $registration_number }}</span>
    </div>
@endif
