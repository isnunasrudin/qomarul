<?php

namespace App\Services\Signing;

use App\Models\Certificate;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pengelolaan sertifikat .p12 (PRD F7.1–F7.3): unggah, baca metadata,
 * rotasi. Berkas disimpan di storage/app/private/certificates/.
 */
class CertificateManager
{
    /**
     * Simpan sertifikat baru; sertifikat lama dinonaktifkan (rotasi).
     *
     * @return array<string, mixed> metadata terbaca
     */
    public function store(string $name, string $p12Content, string $password): array
    {
        $certs = [];
        if (! openssl_pkcs12_read($p12Content, $certs, $password)) {
            throw new RuntimeException('Tidak dapat membaca berkas .p12 dengan kata sandi tersebut.');
        }

        $path = 'certificates/'.Str::slug($name).'-'.now()->format('YmdHis').'.p12';

        Storage::disk('private')->put($path, $p12Content);

        $metadata = $this->extractMetadata($certs['cert']);

        Certificate::query()->update(['is_active' => false]);

        Certificate::create([
            'name' => $name,
            'p12_path' => $path,
            'password_encrypted' => Crypt::encryptString($password),
            'subject' => $metadata['subject'],
            'issuer' => $metadata['issuer'],
            'serial' => $metadata['serial'],
            'valid_from' => $metadata['valid_from'],
            'valid_until' => $metadata['valid_until'],
            'fingerprint' => $metadata['fingerprint'],
            'is_active' => true,
        ]);

        return $metadata;
    }

    /**
     * Sertifikat aktif untuk penandatanganan.
     */
    public function activeCertificate(): ?Certificate
    {
        return Certificate::query()->where('is_active', true)->latest('id')->first();
    }

    /**
     * Sertifikat yang dipakai saat SK diterbitkan (rotasi aman, F7.12).
     */
    public function certificateFor(?int $certificateId): ?Certificate
    {
        if (! $certificateId) {
            return $this->activeCertificate();
        }

        return Certificate::find($certificateId);
    }

    /**
     * @return array{subject: string, issuer: string, serial: string, valid_from: string, valid_until: string, fingerprint: string}
     */
    protected function extractMetadata(string $certPem): array
    {
        $parsed = openssl_x509_parse($certPem);

        if (! $parsed) {
            throw new RuntimeException('Tidak dapat membaca metadata sertifikat.');
        }

        $fingerprint = openssl_x509_fingerprint($certPem, 'sha256');

        return [
            'subject' => (string) ($parsed['subject']['CN'] ?? ''),
            'issuer' => (string) ($parsed['issuer']['CN'] ?? ''),
            'serial' => (string) ($parsed['serialNumber'] ?? ''),
            'valid_from' => date('Y-m-d H:i:s', (int) $parsed['validFrom_time_t']),
            'valid_until' => date('Y-m-d H:i:s', (int) $parsed['validTo_time_t']),
            'fingerprint' => $fingerprint ?: '',
        ];
    }
}
