<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\Signing\CertificateGenerator;
use App\Services\Signing\CertificateManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Manajemen sertifikat penandatanganan .p12 (PRD F7.1–F7.3, F7.12–F7.13).
 */
class CertificateController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Certificate::class);

        $certificates = Certificate::query()
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Admin/Certificates/Index', [
            'certificates' => $certificates,
            'activeCertificate' => app(CertificateManager::class)->activeCertificate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Certificate::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:4'],
            'file' => ['required', 'file', 'max:4096'],
        ]);

        try {
            $metadata = app(CertificateManager::class)->store(
                $data['name'],
                $request->file('file')->getContent(),
                $data['password'],
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Sertifikat disimpan: '.$metadata['subject'].' (valid s.d. '.$metadata['valid_until'].'). Sertifikat lama dinonaktifkan.');
    }

    /**
     * Bangkitkan sertifikat X.509 self-signed dari formulir, simpan sebagai .p12.
     */
    public function generate(Request $request): RedirectResponse
    {
        $this->authorize('create', Certificate::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'common_name' => ['required', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'organizational_unit' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'state' => ['nullable', 'string', 'max:255'],
            'locality' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'days' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'key_bits' => ['nullable', 'in:2048,3072,4096'],
            'digest' => ['nullable', 'in:sha256,sha384,sha512'],
            'password' => ['required', 'string', 'min:4'],
        ]);

        try {
            $result = app(CertificateGenerator::class)->generate($data);
        } catch (RuntimeException $e) {
            return back()->withErrors(['common_name' => $e->getMessage()])->withInput();
        }

        $path = 'certificates/'.now()->format('YmdHis').'-'.str_replace(' ', '-', $data['name']).'.p12';

        Storage::disk('private')->put($path, $result['p12']);

        Certificate::query()->update(['is_active' => false]);

        Certificate::create([
            'name' => $data['name'],
            'p12_path' => $path,
            'password_encrypted' => Crypt::encryptString($data['password']),
            'subject' => $result['metadata']['subject'],
            'issuer' => $result['metadata']['issuer'],
            'serial' => $result['metadata']['serial'],
            'valid_from' => $result['metadata']['valid_from'],
            'valid_until' => $result['metadata']['valid_until'],
            'fingerprint' => $result['metadata']['fingerprint'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Sertifikat berhasil dibuat dan diaktifkan: '.$result['metadata']['subject'].' (valid s.d. '.$result['metadata']['valid_until'].').');
    }

    /**
     * Detail lengkap sertifikat (parse .p12 dengan kata sandi tersimpan).
     *
     * @return array<string, mixed>
     */
    public function detail(Certificate $certificate): array
    {
        $this->authorize('view', $certificate);

        $password = Crypt::decryptString($certificate->password_encrypted);
        $p12 = Storage::disk('private')->get($certificate->p12_path);

        $certs = [];
        if ($p12 === null || ! openssl_pkcs12_read($p12, $certs, $password)) {
            return ['error' => 'Tidak dapat membaca berkas .p12 tersimpan (kata sandi tidak cocok).'];
        }

        $parsed = openssl_x509_parse($certs['cert']);
        openssl_x509_export($certs['cert'], $certPem);
        $details = openssl_x509_parse($certPem, true);

        return [
            'name' => $certificate->name,
            'is_active' => $certificate->is_active,
            'p12_path' => $certificate->p12_path,
            'subject' => $parsed['subject'] ?? [],
            'issuer' => $parsed['issuer'] ?? [],
            'serial' => $parsed['serialNumber'] ?? '',
            'valid_from' => $parsed['validFrom_time_t'] ?? null,
            'valid_until' => $parsed['validTo_time_t'] ?? null,
            'fingerprint' => $certificate->fingerprint,
            'key_bits' => $details['signatureTypeLN'] ?? '',
            'signature_algorithm' => $details['signatureTypeLN'] ?? '',
            'public_key' => $details['extensions']['subjectKeyIdentifier'] ?? '',
            'extensions' => $details['extensions'] ?? [],
            'pem_cert' => $certPem,
        ];
    }
}
