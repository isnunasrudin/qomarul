<?php

namespace App\Services\Numbering;

use App\Models\NumberCounter;
use Illuminate\Support\Facades\DB;

/**
 * Alokasi nomor urut atomik melalui tabel number_counters.
 *
 * Dipakai untuk NIGY (kunci `nigy:{kode_satker}:{tahun}`) dan nomor SK
 * (kunci `decree:{kode_jenis}:{kode_satker}:{tahun}`), serta kelak nomor
 * surat keluar (kunci `letter:...`) — layanan ini tidak boleh mengenal
 * jenis dokumen.
 */
class NumberAllocator
{
    /**
     * Alokasikan satu nomor urut berikutnya untuk (kunci, tahun) secara atomik.
     *
     * Baris number_counters dikunci dengan `lockForUpdate` di dalam transaksi
     * sehingga dua proses yang berjalan paralel tidak mungkin mendapat nomor
     * yang sama.
     */
    public function allocate(string $key, int $year): int
    {
        return DB::transaction(function () use ($key, $year): int {
            $counter = NumberCounter::query()
                ->where('key', $key)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = NumberCounter::create([
                    'key' => $key,
                    'year' => $year,
                    'value' => 1,
                ]);

                return $counter->value;
            }

            $counter->increment('value');

            return $counter->fresh()->value;
        });
    }
}
