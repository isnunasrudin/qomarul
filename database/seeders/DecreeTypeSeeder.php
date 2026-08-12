<?php

namespace Database\Seeders;

use App\Models\DecreeType;
use Illuminate\Database\Seeder;

class DecreeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $defaultFormat = '{nomor}/{kode_jenis}/{kode_satker}/YPP-QH/{bulan_romawi}/{tahun}';

        $types = [
            [
                'code' => 'SK-PPT',
                'name' => 'SK Pengangkatan',
                'template_view' => 'appointment',
                'consideration_recalling' => 'Bahwa untuk mencukupi Tenaga Pengajar / Tenaga Administrasi pada Yayasan Pondok Pesantren Qomarul Hidayah Tugu Trenggalek perlu mengangkat Guru dan tenaga Kependidikan.',
                'consideration_weighing' => [
                    'Undang - Undang Nomor 16 Tahun 2001 tentang Yayasan',
                    'Undang - Undang Nomor 17 Tahun 2013 tentang Organisasi Kemasyarakatan',
                    'Surat Keputusan Menteri Pendidikan dan Kebudayaan Republik Indonesia No.0374/U/1982 tanggal 22 Nopember 1982, tentang Pembinaan Sekolah Swasta',
                    'Undang-Undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional',
                ],
                'consideration_observing' => 'Peraturan Menteri Negara Pendayagunaan Aparatur Negara dan Reformasi Birokrasi Nomor PER/16/M.PAN-RB/11/2009 tanggal 10 November 2009 Tentang Jabatan Fungsional Guru dan Angka Kreditnya.',
            ],
            ['code' => 'SK-PPJ', 'name' => 'SK Perpanjangan', 'template_view' => 'appointment'],
            ['code' => 'SK-TT', 'name' => 'SK Tugas Tambahan', 'template_view' => 'additional_duty'],
            ['code' => 'SK-MUT', 'name' => 'SK Mutasi', 'template_view' => 'mutation'],
            ['code' => 'SK-BHT', 'name' => 'SK Pemberhentian', 'template_view' => 'termination'],
        ];

        foreach ($types as $type) {
            DecreeType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'template_view' => $type['template_view'] ?? null,
                    'number_format' => $defaultFormat,
                    'number_padding' => 3,
                    'consideration_recalling' => $type['consideration_recalling'] ?? null,
                    'consideration_weighing' => $type['consideration_weighing'] ?? null,
                    'consideration_observing' => $type['consideration_observing'] ?? null,
                    'requires_effective_date' => $type['code'] !== 'SK-BHT',
                    'is_active' => true,
                ],
            );
        }
    }
}
