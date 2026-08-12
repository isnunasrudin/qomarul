<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Normalisasi gelar depan & belakang agar konsisten.
 *
 * Gelar belakang bisa lebih dari satu — dipisah koma — dan setiap butir
 * dinormalisasi ke bentuk baku (S.Kom., M.Pd., S.Pd.I., dll). Gelar yang
 * tidak dikenal di-*fallback*: huruf kapital bertitik + huruf kecil tetap.
 *
 * Contoh:
 *   "s.kom, Mpd"        → "S.Kom., M.Pd."
 *   "drs"               → "Drs."
 *   "s.pd. i."          → "S.Pd.I."
 */
class TitleFormatter
{
    /** @var array<string, string> Gelar belakang bentuk baku (kunci = tanpa titik/spasi, huruf besar). */
    private const SUFFIX_MAP = [
        'SKOM' => 'S.Kom.', 'MPD' => 'M.Pd.', 'SPD' => 'S.Pd.', 'SPDI' => 'S.Pd.I.',
        'ST' => 'S.T.', 'SE' => 'S.E.', 'SSI' => 'S.Si.', 'SH' => 'S.H.',
        'SAG' => 'S.Ag.', 'SSOS' => 'S.Sos.', 'SIP' => 'S.I.P.', 'SPI' => 'S.Pi.',
        'STHI' => 'S.Th.I.', 'SARS' => 'S.Ars.', 'SFARM' => 'S.Farm.',
        'SKEP' => 'S.Kep.', 'SGZ' => 'S.Gz.', 'SP' => 'S.P.', 'SOR' => 'S.Or.',
        'SKM' => 'S.K.M.', 'SKG' => 'S.K.G.', 'SKED' => 'S.Ked.', 'SKEPNS' => 'S.Kep.Ns.',
        'SOS' => 'S.Or.', 'MM' => 'M.M.', 'MSC' => 'M.Sc.', 'MSI' => 'M.Si.',
        'MKOM' => 'M.Kom.', 'MT' => 'M.T.', 'MH' => 'M.H.', 'MAG' => 'M.Ag.',
        'MENG' => 'M.Eng.', 'MA' => 'M.A.', 'MBA' => 'M.B.A.', 'MACC' => 'M.Acc.',
        'MSTAT' => 'M.Stat.', 'MTI' => 'M.T.I.', 'MKES' => 'M.Kes.', 'MPH' => 'M.P.H.',
        'MBIOMED' => 'M.Biomed.', 'DEA' => 'D.E.A.', 'PHD' => 'Ph.D.',
        'AMD' => 'A.Md.', 'AMDKEP' => 'A.Md.Kep.', 'SPDK' => 'S.Pd.K.',
        'STR' => 'S.Tr.', 'D3' => 'D-III', 'D4' => 'D-IV', 'S1' => 'S-1', 'S2' => 'S-2', 'S3' => 'S-3',
        'DR' => 'Dr.', 'DRS' => 'Drs.', 'IR' => 'Ir.', 'PROF' => 'Prof.',
        'SHI' => 'S.H.I.', 'SAK' => 'S.Ak.', 'MHUM' => 'M.Hum.', 'SPSI' => 'S.Psi.',
        'MPHIL' => 'M.Phil.', 'BA' => 'B.A.', 'MCOM' => 'M.Com.',
        'STRT' => 'S.Tr.T.', 'STRKOM' => 'S.Tr.Kom.', 'STRAK' => 'S.Tr.Ak.',
        'STRKES' => 'S.Tr.Kes.', 'STRKEB' => 'S.Tr.Keb.', 'SSN' => 'S.Sn.',
        'SMT' => 'S.Mt.', 'MTH' => 'M.Th.', 'SSS' => 'S.S.',
    ];

    /** @var array<string, string> Gelar depan bentuk baku (kunci = tanpa titik/spasi, huruf besar). */
    private const PREFIX_MAP = [
        'DRS' => 'Drs.', 'DR' => 'Dr.', 'IR' => 'Ir.', 'H' => 'H.', 'HJ' => 'Hj.',
        'PROF' => 'Prof.', 'KH' => 'KH.', 'HJR' => 'Hj. Dr.',
    ];

    public static function normalizePrefix(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $tokens = preg_split('/\s+/', trim($value)) ?: [];
        $parts = [];

        foreach ($tokens as $token) {
            $normalized = self::mapOrFallback($token, self::PREFIX_MAP);

            if ($normalized !== '') {
                $parts[] = $normalized;
            }
        }

        return implode(' ', $parts) ?: null;
    }

    public static function normalizeSuffix(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $tokens = preg_split('/[,\/]+/', $value) ?: [];
        $parts = [];

        foreach ($tokens as $token) {
            $normalized = self::mapOrFallback($token, self::SUFFIX_MAP);

            if ($normalized !== '') {
                $parts[] = $normalized;
            }
        }

        return implode(', ', $parts) ?: null;
    }

    /**
     * Normalisasi satu butir gelar: cari di peta bentuk baku;
     * fallback = kapital bertitik untuk singkatan huruf besar.
     *
     * @param  array<string, string>  $map
     */
    protected static function mapOrFallback(string $token, array $map): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $token) ?? '');

        if ($clean === '') {
            return '';
        }

        if (isset($map[$clean])) {
            return $map[$clean];
        }

        // Fallback: "MPd" → "M.Pd."; all-uppercase tak dikenal "MFIL" → "M.Fil."
        $firstLower = strcspn($clean, 'abcdefghijklmnopqrstuvwxyz', 1);

        if (preg_match('/[a-z]/', substr($clean, 1)) === 1
            && $firstLower > 0 && $firstLower < strlen($clean)) {
            $upper = substr($clean, 0, $firstLower);
            $rest = Str::lower(substr($clean, $firstLower));

            return implode('.', str_split($upper)).'.'.$rest.'.';
        }

        if (strlen($clean) === 2) {
            return implode('.', str_split($clean)).'.';
        }

        $head = substr($clean, 0, 1);

        return $head.'.'.Str::ucfirst(Str::lower(substr($clean, 1))).'.';
    }
}
