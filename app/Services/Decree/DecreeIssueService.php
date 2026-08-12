<?php

namespace App\Services\Decree;

use App\Models\Decree;
use App\Models\DecreeSignature;
use App\Models\Setting;
use App\Models\User;
use App\Services\Signing\CertificateManager;
use App\Services\Signing\SelfSignedPkcs12Signer;
use App\Support\QrCodePng;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Pipeline penerbitan SK (PRD §7.1 langkah 6): bekukan snapshot, render PDF
 * dengan QR, tandatangani kriptografis, hitung SHA-256, simpan artefak &
 * catatan tanda tangan. Dipakai SK tunggal maupun batch.
 */
class DecreeIssueService
{
    public function __construct(
        private readonly DecreeSnapshotBuilder $snapshotBuilder,
        private readonly PdfRenderer $pdfRenderer,
        private readonly CertificateManager $certificateManager,
        private readonly SelfSignedPkcs12Signer $signer,
    ) {}

    public function issue(Decree $decree, User $actor): Decree
    {
        // snapshot dibekukan bila belum
        if (! $decree->snapshot_data) {
            $decree = $this->snapshotBuilder->freeze($decree);
        }

        // QR verifikasi → data URI PNG
        $verificationUrl = rtrim(config('app.url'), '/').'/verifikasi/'.$decree->uuid;
        $qrDataUri = QrCodePng::dataUri($verificationUrl);

        // render PDF
        $pdf = $this->pdfRenderer->render($decree, qrDataUri: $qrDataUri);

        // tanda tangan kriptografis bila sertifikat aktif
        $certificate = $this->certificateManager->activeCertificate();

        if ($certificate) {
            $pdf = $this->signer->sign($this->writeTempPdf($pdf), [
                'p12_path' => Storage::disk('private')->path($certificate->p12_path),
                'certificate_password' => Crypt::decryptString($certificate->password_encrypted),
                'name' => (string) Setting::get('foundation.chairman_name', ''),
                'reason' => 'Pengesahan SK',
                'location' => (string) Setting::get('foundation.default_issued_place', 'Gondang'),
            ]);
        }

        $hash = hash('sha256', $pdf);

        $path = 'decrees/'.$decree->issued_date?->year.'/'.str_replace('/', '-', $decree->registration_number ?? $decree->uuid).'-'.$decree->employee->nigy.'.pdf';

        Storage::disk('private')->put($path, $pdf);

        $decree->update([
            'pdf_path' => $path,
            'pdf_hash' => $hash,
            'signed_by' => $actor->id,
            'signed_at' => now(),
        ]);

        DecreeSignature::create([
            'decree_id' => $decree->id,
            'certificate_id' => $certificate?->id,
            'signer_name' => (string) Setting::get('foundation.chairman_name', ''),
            'signed_at' => now(),
            'hash_sha256' => $hash,
            'signature_meta' => [
                'reason' => 'Pengesahan SK',
                'location' => (string) Setting::get('foundation.default_issued_place', 'Gondang'),
                'certificate_fingerprint' => $certificate?->fingerprint,
                'qr_url' => $verificationUrl,
            ],
        ]);

        return $decree->fresh();
    }

    protected function writeTempPdf(string $pdf): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sk-unsigned').'.pdf';
        file_put_contents($path, $pdf);

        return $path;
    }
}
