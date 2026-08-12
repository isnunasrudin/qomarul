<?php

namespace App\Services\Signing;

use RuntimeException;

/**
 * Generator sertifikat X.509 self-signed → ekspor PKCS#12 (.p12).
 * Seluruh field dapat diisi (CN, O, OU, C, ST, L, email, masa berlaku,
 * ukuran kunci, algoritma digest, kata sandi .p12).
 */
class CertificateGenerator
{
    /**
     * @param  array<string, mixed>  $fields  common_name, organization, organizational_unit,
     *                                        country, state, locality, email, days, key_bits, digest, password
     * @return array{p12: string, metadata: array<string, string>}
     */
    public function generate(array $fields): array
    {
        $config = [
            'digest_alg' => $fields['digest'] ?? 'sha256',
            'private_key_bits' => (int) ($fields['key_bits'] ?? 4096),
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $key = openssl_pkey_new($config);

        if ($key === false) {
            throw new RuntimeException('Tidak dapat membuat pasangan kunci RSA: '.openssl_error_string());
        }

        $dn = [
            'commonName' => $fields['common_name'],
            'organizationName' => $fields['organization'] ?? 'YPP Qomarul Hidayah',
            'organizationalUnitName' => $fields['organizational_unit'] ?? '',
            'countryName' => $fields['country'] ?? 'ID',
            'stateOrProvinceName' => $fields['state'] ?? '',
            'localityName' => $fields['locality'] ?? '',
        ];

        if (! empty($fields['email'])) {
            $dn['emailAddress'] = $fields['email'];
        }

        $dn = array_filter($dn, fn ($value) => $value !== '');

        $csr = openssl_csr_new($dn, $key, $config);

        if ($csr === false) {
            throw new RuntimeException('Tidak dapat membuat CSR: '.openssl_error_string());
        }

        $days = (int) ($fields['days'] ?? 3650);

        $cert = openssl_csr_sign($csr, null, $key, $days, $config, (int) ($fields['serial'] ?? time()));

        if ($cert === false) {
            throw new RuntimeException('Tidak dapat menandatangani sertifikat: '.openssl_error_string());
        }

        $p12 = null;
        $password = (string) ($fields['password'] ?? '');

        if (! openssl_pkcs12_export($cert, $p12, $key, $password)) {
            throw new RuntimeException('Tidak dapat mengekspor PKCS#12: '.openssl_error_string());
        }

        openssl_x509_export($cert, $certPem);

        return [
            'p12' => $p12,
            'metadata' => $this->extractMetadata($certPem, $cert),
        ];
    }

    /**
     * @return array{subject: string, issuer: string, serial: string, valid_from: string, valid_until: string, fingerprint: string}
     */
    public function extractMetadata(string $certPem, mixed $cert = null): array
    {
        $parsed = openssl_x509_parse($certPem);

        if (! $parsed) {
            throw new RuntimeException('Tidak dapat membaca metadata sertifikat.');
        }

        $fingerprint = openssl_x509_fingerprint($cert ?: $certPem, 'sha256');

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
