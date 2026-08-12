<?php

namespace App\Models\Concerns;

use DateTimeInterface;

/**
 * Serialisasi tanggal tanpa pergeseran zona waktu (UTC/ISO).
 *
 * Laravel default men-serialize Carbon ke UTC (toJSON) sehingga tanggal
 * "2026-08-07" di Asia/Jakarta berubah menjadi "2026-08-06T17:00:00Z"
 * dan tampil mundur sehari di frontend. Dengan format lokal ini,
 * frontend menerima "2026-08-07 00:00:00" apa adanya.
 */
trait FormatsDatesLocally
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
