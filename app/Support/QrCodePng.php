<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use RuntimeException;

/**
 * Render QR code menjadi PNG (data URI) memakai GD + matrix BaconQrCode —
 * tanpa ketergantungan imagick.
 */
class QrCodePng
{
    public static function dataUri(string $content, int $scale = 5, int $margin = 4): string
    {
        $qr = Encoder::encode($content, ErrorCorrectionLevel::M());

        $matrix = $qr->getMatrix();
        $size = $matrix->getWidth();

        $dimension = ($size + ($margin * 2)) * $scale;

        $image = imagecreatetruecolor($dimension, $dimension);

        if ($image === false) {
            throw new RuntimeException('Tidak dapat membuat gambar QR (GD).');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($matrix->get($x, $y)) {
                    imagefilledrectangle(
                        $image,
                        ($x + $margin) * $scale,
                        ($y + $margin) * $scale,
                        (($x + $margin + 1) * $scale) - 1,
                        (($y + $margin + 1) * $scale) - 1,
                        $black,
                    );
                }
            }
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();

        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode((string) $png);
    }
}
