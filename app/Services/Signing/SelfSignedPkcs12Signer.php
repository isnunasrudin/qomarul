<?php

namespace App\Services\Signing;

use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Implementasi tanda tangan PKCS#12 dengan TCPDF setSignature()
 * (sertifikat self-signed).
 */
class SelfSignedPkcs12Signer implements SignerInterface
{
    /**
     * @param  array<string, mixed>  $metadata  key: name, reason, location, p12_path, certificate_password
     */
    public function sign(string $pdfPath, array $metadata): string
    {
        $p12Path = $metadata['p12_path'] ?? null;
        $password = $metadata['certificate_password'] ?? null;

        if (! $p12Path || ! is_file($p12Path)) {
            throw new RuntimeException('Berkas sertifikat .p12 tidak ditemukan.');
        }

        $certs = [];
        if (! openssl_pkcs12_read(file_get_contents($p12Path), $certs, (string) $password)) {
            throw new RuntimeException('Kata sandi sertifikat .p12 salah.');
        }

        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($pdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        $pdf->setSignature(
            $certs['cert'],
            $certs['pkey'],
            '', // private_key_password — kunci dari p12 sudah terbaca
            $certs['extracerts'] ?? '',
            2, // cert_type
            [
                'Name' => (string) ($metadata['name'] ?? ''),
                'Reason' => (string) ($metadata['reason'] ?? 'Pengesahan'),
                'Location' => (string) ($metadata['location'] ?? ''),
                'DateInfo' => date('YmdHis'),
            ],
        );

        return $pdf->Output('', 'S');
    }
}
