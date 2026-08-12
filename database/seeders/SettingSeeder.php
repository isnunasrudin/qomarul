<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'foundation.name' => ['value' => 'Yayasan Pondok Pesantren Qomarul Hidayah', 'group' => 'foundation'],
            'foundation.address' => ['value' => 'Gondang, Tugu, Trenggalek, Jawa Timur', 'group' => 'foundation'],
            'foundation.notary_deed' => ['value' => 'KAYUN WIDIHARSONO, S.H, M.Kn — Nomor: 09 Tahun 2014', 'group' => 'foundation'],
            'foundation.sk_menkumham' => ['value' => 'C-598.HT.03.01-2014', 'group' => 'foundation'],
            'foundation.chairman_name' => ['value' => '', 'group' => 'foundation'],
            'foundation.chairman_position' => ['value' => 'Ketua Yayasan', 'group' => 'foundation'],
            'foundation.default_issued_place' => ['value' => 'Gondang', 'group' => 'foundation'],
            'foundation.logo_path' => ['value' => null, 'group' => 'foundation'],
            'letterhead.cc_list' => ['value' => [
                'Kepala Dinas Pendidikan Pemuda dan Olah Raga Kab. Trenggalek',
                'Sdr. Kepala Bidang Keuangan YPP. Qomarul Hidayah Tugu Trenggalek',
                'Sdr. Kepala Bidang Personalia dan SDM YPP. Qomarul Hidayah',
                'Sdr. Kepala Satuan Kerja {satker}',
                'Arsip',
            ], 'group' => 'letterhead'],
            'nigy.format' => ['value' => '{tahun_masuk}{kode_satker}{urut}', 'group' => 'nigy'],
            'nigy.padding' => ['value' => 3, 'group' => 'nigy'],
        ];

        foreach ($settings as $key => $setting) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $setting['value'], 'group' => $setting['group']],
            );
        }

        if (blank(Setting::get('foundation.chairman_name'))) {
            $message = 'PERHATIAN: nama Ketua Yayasan belum diisi (foundation.chairman_name). Isi di Pengaturan Yayasan sebelum menerbitkan SK.';
            Log::warning($message);
            $this->command?->warn($message);
        }
    }
}
