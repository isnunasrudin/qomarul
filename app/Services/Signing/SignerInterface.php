<?php

namespace App\Services\Signing;

/**
 * Antarmuka penanda tangan dokumen PDF — netral terhadap jenis dokumen
 * (PRD §9 poin 6) agar modul surat keluar dan PSrE tersertifikasi dapat
 * memakainya tanpa perubahan.
 */
interface SignerInterface
{
    /**
     * Tandatangani PDF secara kriptografis.
     *
     * @param  string  $pdfPath  path berkas PDF sumber
     * @param  array<string, mixed>  $metadata  penanda tangan: name, reason, location, certificate_password
     * @return string PDF hasil penandatanganan (binary)
     */
    public function sign(string $pdfPath, array $metadata): string;
}
