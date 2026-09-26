import { convert } from 'mabims-hijri';

/**
 * Ejaan bulan Hijriah mengikuti penulisan resmi Indonesia (Kemenag), bukan
 * transliterasi yang dipakai paket ("Muharram", "Ramadhan", "Dzulhijjah"),
 * supaya cocok dengan dokumen yayasan yang sudah ada.
 */
const MONTHS_ID = {
    1: 'Muharam',
    2: 'Safar',
    3: 'Rabiulawal',
    4: 'Rabiulakhir',
    5: 'Jumadilawal',
    6: 'Jumadilakhir',
    7: 'Rajab',
    8: 'Syakban',
    9: 'Ramadan',
    10: 'Syawal',
    11: 'Zulkaidah',
    12: 'Zulhijah',
};

/**
 * Usulan tanggal Hijriah kalender MABIMS (Kemenag RI) untuk sebuah tanggal
 * Masehi, mis. "25 Muharam 1448 H".
 *
 * Data resmi 2023–2026 dibundel di dalam paket; di luar rentang itu dipakai
 * perhitungan Neo MABIMS (altitud bulan ≥ 3°, elongasi ≥ 6,4°) secara daring.
 * Nilainya hanya usulan — penetapan resmi lewat sidang isbat, jadi operator
 * tetap harus bisa mengoreksinya di form.
 *
 * @param {string} isoDate tanggal Masehi format YYYY-MM-DD
 * @returns {Promise<string>} teks siap cetak, atau '' bila konversi gagal
 */
export async function hijriFromGregorian(isoDate) {
    if (!isoDate) {
        return '';
    }

    try {
        const { output } = await convert(isoDate);

        if (!output?.day || !output?.year || !output?.month) {
            return '';
        }

        return `${output.day} ${MONTHS_ID[output.month] ?? output.month_name} ${output.year} H`;
    } catch (error) {
        console.warn('Konversi Hijriah gagal:', error);

        return '';
    }
}
