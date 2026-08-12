const BULAN = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

function parseTanggal(value) {
    if (!value) return null;
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)) {
        const [y, m, d] = value.slice(0, 10).split('-').map(Number);
        return new Date(y, m - 1, d);
    }
    if (typeof value === 'number') {
        return new Date(value < 1e12 ? value * 1000 : value);
    }
    const d = new Date(value);
    return isNaN(d.getTime()) ? null : d;
}

export function formatTanggal(value, fallback = '—') {
    const d = parseTanggal(value);
    if (!d) return fallback;
    return `${String(d.getDate()).padStart(2, '0')} ${BULAN[d.getMonth()]} ${d.getFullYear()}`;
}

export function formatWaktu(value) {
    const d = parseTanggal(value);
    if (!d) return '';
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

export function formatTanggalWaktu(value, fallback = '—') {
    const d = parseTanggal(value);
    if (!d) return fallback;
    return `${formatTanggal(value)} ${formatWaktu(value)}`;
}
