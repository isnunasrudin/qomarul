<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder satuan kerja riil — diblokir (plan §Yang Masih Dibutuhkan).
 * Daftar satuan kerja beserta kode riil (SD1, SD2, SMP, SMK, TPQ1, ...)
 * belum diterima dari Yayasan. Isi berkas ini saat data tersedia.
 */
class WorkUnitSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('WorkUnitSeeder dilewati: daftar satuan kerja riil belum diterima dari Yayasan.');

    }
}
