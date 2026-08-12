<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Pemroses foto profil: validasi MIME asli (finfo), maks 2 MB,
 * crop tengah rasio 3:4, kompres JPEG (PRD F3.3).
 */
class PhotoProcessor
{
    public const MAX_SIZE = 2 * 1024 * 1024;

    public const RATIO_WIDTH = 3;

    public const RATIO_HEIGHT = 4;

    public function process(UploadedFile $file, string $disk, string $directory): string
    {
        $this->assertValid($file);

        $image = imagecreatefromstring($file->getContent());

        if ($image === false) {
            throw new RuntimeException('File bukan gambar yang valid.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // crop tengah dengan rasio 3:4
        $targetRatio = self::RATIO_WIDTH / self::RATIO_HEIGHT;
        $sourceRatio = $width / $height;

        if ($sourceRatio > $targetRatio) {
            $cropWidth = (int) round($height * $targetRatio);
            $cropHeight = $height;
        } else {
            $cropWidth = $width;
            $cropHeight = (int) round($width / $targetRatio);
        }

        $x = (int) floor(($width - $cropWidth) / 2);
        $y = (int) floor(($height - $cropHeight) / 2);

        // ukuran maksimal 800px di sisi terpanjang agar ringan di batch PDF
        $maxSide = 800;
        $scale = min(1, $maxSide / max($cropWidth, $cropHeight));
        $outWidth = (int) round($cropWidth * $scale);
        $outHeight = (int) round($cropHeight * $scale);

        $cropped = imagecreatetruecolor($outWidth, $outHeight);
        imagecopyresampled($cropped, $image, 0, 0, $x, $y, $outWidth, $outHeight, $cropWidth, $cropHeight);

        ob_start();
        imagejpeg($cropped, null, 85);
        $data = ob_get_clean();

        imagedestroy($image);
        imagedestroy($cropped);

        $path = $directory.'/'.now()->format('Y/m').'/'.md5(uniqid((string) mt_rand(), true)).'.jpg';

        Storage::disk($disk)->put($path, $data);

        return $path;
    }

    protected function assertValid(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_SIZE) {
            throw new RuntimeException('Ukuran foto maksimal 2 MB.');
        }

        $mime = $file->getMimeType();

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Foto harus berupa berkas JPG, PNG, atau WEBP.');
        }

        // pastikan isi benar-benar gambar (bukan ekstensi palsu)
        if (@getimagesizefromstring($file->getContent()) === false) {
            throw new RuntimeException('Berkas bukan gambar yang valid.');
        }
    }
}
