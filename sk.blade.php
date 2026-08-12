<style>
    @font-face {
        font-family: 'Arial';
        src: url('/public/fonts/arial.ttf') format("truetype");
        font-weight: normal;
        font-style: normal;
    }

    @font-face {
        font-family: 'Arial';
        src: url('/public/fonts/arialbd.ttf') format("truetype");
        font-weight: bold;
        font-style: normal;
    }

    body {
        font-family: 'Arial';
        font-size: 13.5px;
        margin: .8cm 1.5cm .5cm 1.5cm;
        line-height: 1.1;
    }

    tr td:last-child {
        padding-bottom: 4px;
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
</style>
<table>
    <tr>
        <td>
            <img src="/public/assets/img/qomarul.png" alt="" style="width: 100px; height: 90px;">
        </td>
        <td style="text-align: center; width: 100%; line-height: 1.4;">
            <p style="font-size: 21px; font-weight: bold;">YAYASAN PONDOK PESANTREN QOMARUL HIDAYAH</p>
            <P style="font-size: 14px; font-weight: bold;">AKTE NOTARIS: KAYUN WIDIHARSONO, S.H, M.Kn &nbsp; NOMOR: 09 TAHUN 2014 </P>
            <p style="font-size: 14px">SK MENKUMHAM RI NOMOR: C-598.HT.03.01-2014</p>
        </td>
    </tr>
</table>

<div style="background: #029340; width: 100%; height: 8px;margin: 10px 0"></div>

<div style="text-align: center; margin-bottom: 15px; font-size: 15px;">
    <p style="font-weight: bold;">SURAT KEPUTUSAN</p>
    <p style="font-weight: bold;">YAYASAN PONDOK PESANTREN QOMARUL HIDAYAH</p>
    <p style="font-weight: bold;">GONDANG TUGU TRENGGALEK</p>
    <p style="font-size: 13px; padding-top: 3px">Nomor : <b>{{ $nomor_sk }}</b></p>
</div>

<p style="margin-bottom: 5px;">Yayasan Pondok Pesantren Qomarul Hidayah :</p>
<table class="konsideran">
    <tr>
        <td style="width: 100px;">Mengingat</td>
        <td style="width: 10px;">:</td>
        <td colspan="2">Bahwa untuk mencukupi Tenaga Pengajar / Tenaga Administrasi pada Yayasan Pondok Pesantren Qomarul Hidayah Tugu Trenggalek perlu mengangkat Guru dan tenaga Kependidikan</td>
    </tr>
    <tr>
        <td>Menimbang</td>
        <td style="width: 10px;">:</td>
        <td>1.</td>
        <td style="width: 100%;">Undang - Undang Nomor 16 Tahun 2001 tentang Yayasan</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td>2.</td>
        <td>Undang - Undang Nomor 17 Tahun 2013 tentang Organisasi Kemasyarakatan</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td>3.</td>
        <td>Surat Keputusan Menteri Pendidikan dan Kebudayaan Republik Indonesia No.0374/U/1982 tanggal 22 Nopember 1982, tentang Pembinaan Sekolah Swasta</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td>4.</td>
        <td>Undang-Undang Nomor 20 Tahun 2003 tentang sistem Pendidikan Nasional</td>
    </tr>
    <tr>
        <td>Memperhatikan</td>
        <td>:</td>
        <td colspan="2">Peraturan Menteri Negara Pendayagunaan Aparatur Negara dan Reformasi Birokrasi Nomor PER/16/M.PAN-RB/11/2009 tanggal 10 November 2009 Tentang Jabatan Fungsional Guru dan Angka Kreditnya.</td>
    </tr>
</table>

<p style="text-align: center; font-weight: bold;">MEMUTUSKAN</p>
<table>
    <tr>
        <td style="width: 100px;">Menetapkan</td>
        <td style="width: 10px;">:</td>
        <td style="padding: 0;"></td>
    </tr>
    <tr>
        <td style="width: 100px;">Pertama</td>
        <td style="width: 10px;">:</td>
        <td style="padding: 0;">Terhitung Mulai Tanggal <b>{{ $tmt }}</b> mengangkat :</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td>
            <table class="biodata">
                <tr>
                    <td style="width: 160px">Nama</td>
                    <td style="width: 10px;">:</td>
                    <td style="width: 100%; padding: 0;">{{ $nama }}</td>
                </tr>
                <tr>
                    <td>NIGY</td>
                    <td>:</td>
                    <td style="padding: 0;">{{ $nigy }}</td>
                </tr>
                <tr>
                    <td>Tempat/Tanggal Lahir</td>
                    <td>:</td>
                    <td style="padding: 0;">{{ $tempat_lahir . ', ' . $tanggal_lahir }}</td>
                </tr>
                <tr>
                    <td>Pendidikan/Jurusan</td>
                    <td>:</td>
                    <td style="padding: 0;">
                        {{ $pendidikan ?? '-' }}
                        {{ $jurusan ? '/' . $jurusan : '' }}
                    </td>
                </tr>
                <tr>
                    <td>Jabatan</td>
                    <td>:</td>
                    <td style="padding: 0;">{{ $jabatan }}</td>
                </tr>
                <tr>
                    <td>Diangkat kembali sebagai</td>
                    <td>:</td>
                    <td style="padding: 0;">{{ $diangkat_sebagai }} <span style="font-weight: normal;">Satuan Kerja</span> {{ $satker }}</td>
                </tr>
                <tr>
                    <td>TMT Yayasan</td>
                    <td>:</td>
                    <td style="padding: 0;">{{ $tmt_yayasan }}</td>
                </tr>
                <tr>
                    <td>TMT di Satuan Kerja</td>
                    <td>:</td>
                    <td style="padding: 0;">{{ $tmt_satker }}</td>
                </tr>
                <tr>
                    <td>Masa Kerja Keseluruhan</td>
                    <td>:</td>
                    <td style="padding: 0;">
                        <span style="padding-right: 10px;">{{ $masa_kerja_tahun }}</span>
                        <span style="font-weight: normal;">Tahun</span>
                        <span style="padding-left: 10px; padding-right: 10px;">{{ $masa_kerja_bulan }}</span>
                        <span style="font-weight: normal;">Bulan</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr class="konsideran">
        <td>Kedua</td>
        <td>:</td>
        <td>Yang bersangkutan diberikan honorarium sesuai ketentuan Yayasan Pondok Pesantren Qomarul Hidayah.</td>
    </tr>
    <tr class="konsideran">
        <td>Ketiga</td>
        <td>:</td>
        <td>Surat Keputusan ini berlaku sejak tanggal ditetapkan sampai akhir tahun pelajaran berikutnya dan apabila dikemudian terdapat kekeliruan akan diadakan perbaikan sebagaimana mestinya.</td>
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
            <td style="padding: 0;">{{ $ditetapkan_di }}</td>
        </tr>
        <tr>
            <td>Pada tanggal</td>
            <td>:</td>
            <td style="padding: 0;">{{ $ditetapkan_tanggal }}</td>
        </tr>
        <tr>
            <td colspan="3">
                <p style="padding: 0;">Ketua Yayasan</p>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="padding: 0; height: 2cm; vertical-align: middle;">
                @if($sudah_ttd)
                <img src="/public/assets/img/signature-basah.png" style="width: 250px;">
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <p style="padding: 0; font-weight: bold; text-decoration: underline;">{{ $kepala_sekolah }}</p>
            </td>
        </tr>
    </table>
</div>

<p style="margin-top: 10px; font-size: 13px;">Tembusan disampaikan kepada :</p>
<ol style="margin-left: 20px; font-size: 13px">
    <li>Kepala Dinas Pendidikan Pemuda dan Olah Raga Kab. Trenggalek</li>
    <li>Sdr. Kepala Bidang Keuangan YPP. Qomarul Hidayah Tugu Trenggalek</li>
    <li>Sdr. Kepala Bidang Personalia dan SDM YPP. Qomarul Hidayah</li>
    <li>Sdr. Kepala Satuan Kerja {{ $satker }}</li>
    <li>Arsip</li>
</ol>

<div style="margin-top: 10px; margin-bottom: 0; position: fixed; bottom: .8cm; right: 1.5cm;">
    <b>Reg. </b><span style="font-family: Times New Roman;">E-{{ $nomor_reg }}</span>
</div>