<?php

namespace App\Services\Nigy;

use App\Services\Numbering\NumberAllocator;
use Illuminate\Support\Carbon;

/**
 * Pembangkit NIGY sesuai format yang dikonfigurasi di settings.
 *
 * Format default: {tahun_masuk}{kode_satker}{urut} → 2026SMK001.
 * Nomor urut direset per tahun per satuan kerja lewat NumberAllocator.
 */
class NigyGenerator
{
    /**
     * Token yang dikenali template NIGY. Dipakai juga untuk memvalidasi format
     * di Pengaturan agar token salah ketik tidak diam-diam tercetak apa adanya.
     *
     * @var array<int, string>
     */
    public const TOKENS = [
        '{tahun_masuk}',
        '{bulan_masuk}',
        '{kode_satker}',
        '{kode_jenjang}',
        '{urut}',
        '{tahun_lahir}',
        '{bulan_lahir}',
        '{hari_lahir}',
        '{tanggal_lahir_tahun}',
        '{tanggal_lahir_bulan}',
        '{tanggal_lahir_hari}',
        '{tipe_kepegawaian}',
        '{kode_tipe_kepegawaian}',
    ];

    public function __construct(private readonly NumberAllocator $allocator) {}

    /**
     * Alokasikan nomor urut berikutnya untuk satuan kerja pada tahun masuk.
     */
    public function nextSequence(string $workUnitCode, int $year): int
    {
        return $this->allocator->allocate("nigy:{$workUnitCode}:{$year}", $year);
    }

    /**
     * Tahun yang dipakai untuk kunci penghitung sekaligus token {tahun_masuk}.
     *
     * Dipakai bersama pemanggil supaya kunci penghitung dan angka yang tercetak
     * tidak berbeda ketika TMT belum diisi.
     */
    public function yearOf(?Carbon $foundationStartDate): int
    {
        return $foundationStartDate ? (int) $foundationStartDate->year : (int) now()->year;
    }

    /**
     * Render NIGY dari format yang dikonfigurasi.
     *
     * Tanggal lahir kosong menghasilkan 0000/00/00 supaya format tetap utuh —
     * tidak menyisakan token maupun titik ganda.
     */
    public function render(
        string $format,
        int $padding,
        string $workUnitCode,
        string $workUnitLevel,
        ?Carbon $foundationStartDate,
        int $sequence,
        ?Carbon $birthDate = null,
        ?int $employmentStatusId = null,
        ?string $employmentStatusCode = null,
    ): string {
        $year = $this->yearOf($foundationStartDate);
        $month = $foundationStartDate ? $foundationStartDate->format('m') : now()->format('m');

        $birthYear = $birthDate ? $birthDate->format('Y') : '0000';
        $birthMonth = $birthDate ? $birthDate->format('m') : '00';
        $birthDay = $birthDate ? $birthDate->format('d') : '00';

        // strtr() mengganti kunci terpanjang lebih dulu dan tidak mengulang hasil
        // penggantian, jadi token tidak saling menimpa.
        return strtr($format, [
            '{tahun_masuk}' => (string) $year,
            '{bulan_masuk}' => $month,
            '{kode_satker}' => $workUnitCode,
            '{kode_jenjang}' => $workUnitLevel,
            '{urut}' => str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT),
            '{tahun_lahir}' => $birthYear,
            '{bulan_lahir}' => $birthMonth,
            '{hari_lahir}' => $birthDay,
            '{tanggal_lahir_tahun}' => $birthYear,
            '{tanggal_lahir_bulan}' => $birthMonth,
            '{tanggal_lahir_hari}' => $birthDay,
            '{tipe_kepegawaian}' => $employmentStatusId !== null ? str_pad((string) $employmentStatusId, 2, '0', STR_PAD_LEFT) : '00',
            '{kode_tipe_kepegawaian}' => $employmentStatusCode ?? '',
        ]);
    }
}
