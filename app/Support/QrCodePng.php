<?php

namespace App\Support;

use App\Models\Setting;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use GdImage;
use RuntimeException;

/**
 * Render QR code menjadi PNG (data URI) memakai GD + matrix BaconQrCode —
 * tanpa ketergantungan imagick.
 */
class QrCodePng
{
    /** Porsi sisi QR yang ditempati logo di tengah. */
    private const LOGO_RATIO = 0.238;

    /**
     * Lebar gambar QR yang ditargetkan (piksel). QR dicetak 20 mm, jadi 1000 px
     * setara ~1270 DPI. Nilai ini otomatis diturunkan bila logo akan ikut
     * diperbesar — upsaling justru membuat logonya kabur.
     */
    private const TARGET_PIXELS = 1000;

    /**
     * @param  int  $scale  Ukuran satu modul dalam piksel. 0 = otomatis mengikuti
     *                      TARGET_PIXELS sehingga ketajaman tidak bergantung pada
     *                      jumlah modul (versi QR) kontennya.
     * @param  bool  $withLogo  Sematkan logo yayasan di tengah QR. Koreksi galat
     *                          otomatis naik ke level H (~30%) agar tetap terbaca.
     */
    public static function dataUri(string $content, int $scale = 0, int $margin = 0, bool $withLogo = false): string
    {
        $logo = $withLogo ? self::logoImage() : null;

        // Logo menutupi sebagian modul, jadi koreksi galat dinaikkan ke H (30%).
        $qr = Encoder::encode($content, $logo instanceof GdImage ? ErrorCorrectionLevel::H() : ErrorCorrectionLevel::M());

        $matrix = $qr->getMatrix();
        $size = $matrix->getWidth();

        if ($scale < 1) {
            $target = self::TARGET_PIXELS;

            // Logo tidak boleh ikut diperbesar: kotak logo dibatasi agar tidak
            // melebihi lebar asli berkas logo.
            if ($logo instanceof GdImage) {
                $target = min($target, (int) floor(imagesx($logo) / self::LOGO_RATIO));
            }

            $scale = max(4, (int) ceil($target / $size));
        }

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

        if ($logo instanceof GdImage) {
            self::drawLogo($image, $logo, $dimension, $white, $scale);
            imagedestroy($logo);
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();

        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode((string) $png);
    }

    /**
     * Logo yayasan untuk tengah QR: pakai logo terunggah bila ada, jika tidak
     * logo bawaan aplikasi — sama seperti pemilihan logo kop surat.
     */
    private static function logoImage(): ?GdImage
    {
        $configured = Setting::get('foundation.logo_path');

        $candidates = array_filter([
            is_string($configured) && $configured !== '' ? public_path($configured) : null,
            resource_path('images/logo_full.png'),
        ]);

        foreach ($candidates as $path) {
            if (! is_file($path)) {
                continue;
            }

            $image = @imagecreatefromstring((string) file_get_contents($path));

            if ($image instanceof GdImage) {
                return $image;
            }
        }

        return null;
    }

    /**
     * Gambar logo di tengah QR, diskalakan proporsional di dalam kotak
     * LOGO_RATIO dan diberi bingkai putih selebar satu modul agar tidak
     * menempel ke modul QR.
     */
    private static function drawLogo(GdImage $image, GdImage $logo, int $dimension, int $white, int $scale): void
    {
        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);

        $box = max(1, (int) round($dimension * self::LOGO_RATIO));
        $ratio = min($box / $logoWidth, $box / $logoHeight);

        $targetWidth = max(1, (int) round($logoWidth * $ratio));
        $targetHeight = max(1, (int) round($logoHeight * $ratio));

        $x = (int) round(($dimension - $targetWidth) / 2);
        $y = (int) round(($dimension - $targetHeight) / 2);

        $pad = max(2, (int) round($scale / 4));

        imagefilledrectangle(
            $image,
            $x - $pad,
            $y - $pad,
            $x + $targetWidth + $pad - 1,
            $y + $targetHeight + $pad - 1,
            $white,
        );

        imagealphablending($image, true);
        imagecopyresampled($image, $logo, $x, $y, 0, 0, $targetWidth, $targetHeight, $logoWidth, $logoHeight);
    }
}
