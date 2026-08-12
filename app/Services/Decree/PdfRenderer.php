<?php

namespace App\Services\Decree;

use App\Models\Decree;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

/**
 * Render PDF SK (PRD §5.6): template Blade per jenis, kertas F4/Folio,
 * watermark draft, kop & tembusan dari settings.
 */
class PdfRenderer
{
    /** F4/Folio 215 × 330 mm dalam pt */
    private const PAPER_F4 = [0, 0, 609.45, 935.43];

    public function render(Decree $decree, bool $draftPreview = false, ?string $qrDataUri = null): string
    {
        $snapshot = $decree->snapshot_data
            ?? app(DecreeSnapshotBuilder::class)->build($decree);

        $view = $decree->decreeType->template_view ?: 'appointment';

        if (! View::exists("decrees.{$view}")) {
            $view = 'appointment';
        }

        $payload = array_merge($snapshot, [
            'foundation_name' => $snapshot['foundation']['name'] ?? '',
            'notary_deed' => $snapshot['foundation']['notary_deed'] ?? '',
            'sk_menkumham' => $snapshot['foundation']['sk_menkumham'] ?? '',
            'is_signed' => ! $draftPreview && $decree->status->value === 'issued',
            'foundation_logo' => Setting::get('foundation.logo_path'),
            'signature_path' => Setting::get('foundation.signature_path'),
            'qr_data_uri' => $qrDataUri,
        ]);

        $pdf = Pdf::loadView("decrees.{$view}", $payload);

        $pdf->setPaper(self::PAPER_F4, 'portrait');
        $pdf->setOptions([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'Arial',
        ]);

        return $pdf->output();
    }
}
