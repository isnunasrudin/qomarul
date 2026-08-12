const MISSING_LABELS = {
    'pribadi.nik': 'NIK',
    'pribadi.birth_place': 'Tempat Lahir',
    'pribadi.birth_date': 'Tanggal Lahir',
    'pribadi.religion': 'Agama',
    'pribadi.marital_status': 'Status Pernikahan',
    'pribadi.mother_name': 'Ibu Kandung',
    'pribadi.address': 'Alamat',
    'pribadi.phone': 'Nomor HP',
    'pribadi.email': 'Email',
    'pribadi.photo_path': 'Pas Foto',
    'kepegawaian.position_id': 'Jabatan',
    'kepegawaian.employment_status_id': 'Status Kepegawaian',
    'kepegawaian.foundation_start_date': 'TMT Yayasan',
    'kepegawaian.unit_start_date': 'TMT Satuan Kerja',
    'kepegawaian.subject': 'Mata Pelajaran',
    'pendidikan.tertinggi': 'Pendidikan Tertinggi',
    'berkas.ktp': 'Berkas KTP',
    'berkas.diploma': 'Berkas Ijazah',
};

export function missingLabel(key) {
    return MISSING_LABELS[key] ?? key;
}

export function sectionFor(key) {
    return key.startsWith('berkas.') ? '/portal/documents' : '/portal/profile';
}

export function isPribadi(key) {
    return key.startsWith('pribadi.') || key.startsWith('kepegawaian.') || key.startsWith('pendidikan.');
}

export function isBerkas(key) {
    return key.startsWith('berkas.');
}
