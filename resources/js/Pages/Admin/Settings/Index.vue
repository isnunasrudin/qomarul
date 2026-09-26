<template>
    <AdminLayout>
        <Head :title="'Pengaturan Yayasan'" />

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Pengaturan Yayasan</h2>
            <p class="text-sm text-gray-500">Identitas yayasan, kop surat, dan format NIGY. Seluruh teks kop dan tembusan pada PDF diambil dari sini.</p>
        </div>

        <form @submit.prevent="save" class="space-y-6">
            <section v-for="group in groups" :key="group.key" class="card p-6">
                <h3 class="mb-4 text-sm font-semibold text-gray-700">{{ group.title }}</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div v-for="field in group.fields" :key="field.name" :class="{ 'sm:col-span-2': field.full }">
                        <label class="label">{{ field.label }}</label>
                        <textarea v-if="field.type === 'textarea'" v-model="form[group.key][field.name]" rows="2"
                                  class="input"></textarea>
                        <input v-else v-model="form[group.key][field.name]" :type="field.type ?? 'text'"
                               class="input">
                        <p v-if="fieldError(group.key, field.name)" class="error-text" role="alert">{{ fieldError(group.key, field.name) }}</p>
                    </div>
                </div>

                <!-- Pratinjau langsung format NIGY memakai contoh data GTK. -->
                <div v-if="group.key === 'nigy' && hasNigyTokens" class="mt-5 rounded-md border border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Pratinjau NIGY</p>
                    <p class="mt-1 break-all font-mono text-lg font-semibold text-gray-800">{{ nigyPreview || '—' }}</p>
                    <p class="mt-2 text-xs text-gray-500">
                        Contoh: lahir 8 Juli 2001 · TMT 24 Juni 2020 · satker SD1 (SD) ·
                        tipe Kontrak (02) · urut {{ sampleSequence }}
                    </p>
                    <p v-if="unknownNigyTokens.length" class="mt-2 text-xs font-medium text-red-600" role="alert">
                        Token tidak dikenal: {{ unknownNigyTokens.join(', ') }} — penyimpanan akan ditolak.
                    </p>
                </div>
            </section>

            <section class="card p-6">
                <h3 class="mb-4 text-sm font-semibold text-foreground">Logo Yayasan (Kop Surat)</h3>
                <p class="mb-3 text-xs text-slate-500">PNG/JPG, maks 2 MB. Tampil di kop surat SK.</p>
                <form @submit.prevent="uploadLogo" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <input type="file" accept="image/png,image/jpeg" @change="(e) => { logoForm.file = e.target.files[0]; }"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                        <p v-if="logoForm.errors.file" class="error-text" role="alert">{{ logoForm.errors.file }}</p>
                    </div>
                    <button type="submit" :disabled="logoForm.processing" class="btn-secondary sm:justify-self-end">
                        Unggah Logo
                    </button>
                </form>
            </section>

            <section class="card p-6">
                <h3 class="mb-4 text-sm font-semibold text-foreground">Gambar Tanda Tangan Basah (Khusus Admin Yayasan)</h3>
                <p class="mb-3 text-xs text-gray-500">
                    Disimpan di luar document root (izin 0400), hanya dibaca proses penandatanganan,
                    tidak pernah tampil di pratinjau draft. Konfirmasi kata sandi wajib.
                </p>
                <form @submit.prevent="uploadSignature" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Kata Sandi Anda</label>
                        <input v-model="signatureForm.current_password" type="password" autocomplete="current-password"
                               class="input">
                        <p v-if="signatureForm.errors.current_password" class="error-text" role="alert">{{ signatureForm.errors.current_password }}</p>
                    </div>
                    <div>
                        <label class="label">Gambar PNG/JPG (maks 2 MB)</label>
                        <input type="file" accept="image/png,image/jpeg" @change="(e) => { signatureForm.file = e.target.files[0]; }"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                        <p v-if="signatureForm.errors.file" class="error-text" role="alert">{{ signatureForm.errors.file }}</p>
                    </div>
                    <button type="submit" :disabled="signatureForm.processing"
                            class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50 sm:col-span-2">
                        Ganti Tanda Tangan
                    </button>
                </form>
            </section>

            <button type="submit" :disabled="form.processing"
                    class="btn-primary disabled:opacity-50">
                Simpan Pengaturan
            </button>
        </form>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps(['settings', 'schema', 'nigyTokens']);

/**
 * Struktur form WAJIB bersarang sejak form dibuat.
 *
 * `useForm().data()` hanya mengumpulkan kunci yang ada di `defaults` saat form
 * dibuat (`Object.keys(defaults)`), sehingga `useForm({})` yang diisi kunci
 * belakangan selalu mengirim payload kosong — server lalu melaporkan semua
 * field wajib "kosong" meski UI terisi.
 */
function initialData() {
    const data = {};

    Object.entries(props.settings ?? {}).forEach(([group, values]) => {
        data[group] = {};

        Object.entries(values).forEach(([key, value]) => {
            data[group][key] = Array.isArray(value) ? value.join('\n') : (value ?? '');
        });
    });

    return data;
}

const form = useForm(initialData());

/**
 * Pratinjau NIGY dihitung di sisi klien supaya berubah seketika saat format
 * atau padding disunting. Daftar token tetap diambil dari server
 * (`NigyGenerator::TOKENS`) agar peringatan token asing tidak melenceng.
 *
 * Nilai contoh sengaja tetap: satu GTK dengan data lengkap.
 */
const NIGY_SAMPLE = {
    '{tahun_masuk}': '2020',
    '{bulan_masuk}': '06',
    '{kode_satker}': 'SD1',
    '{kode_jenjang}': 'SD',
    '{tahun_lahir}': '2001',
    '{bulan_lahir}': '07',
    '{hari_lahir}': '08',
    '{tanggal_lahir_tahun}': '2001',
    '{tanggal_lahir_bulan}': '07',
    '{tanggal_lahir_hari}': '08',
    '{tipe_kepegawaian}': '02',
    '{kode_tipe_kepegawaian}': 'KONTRAK',
};

const SAMPLE_SEQUENCE = 10;

const hasNigyTokens = computed(() => (props.nigyTokens ?? []).length > 0);

const sampleSequence = computed(() => {
    const padding = Math.min(Math.max(Number(form?.nigy?.padding) || 1, 1), 10);

    return String(SAMPLE_SEQUENCE).padStart(padding, '0');
});

const nigyPreview = computed(() => {
    const format = String(form?.nigy?.format ?? '');

    const replacements = { ...NIGY_SAMPLE, '{urut}': sampleSequence.value };

    return format.replace(/\{[a-z_]+\}/g, (token) => replacements[token] ?? token);
});

const unknownNigyTokens = computed(() => {
    if (! hasNigyTokens.value) {
        return [];
    }

    const known = new Set(props.nigyTokens);
    const found = String(form?.nigy?.format ?? '').match(/\{[a-z_]+\}/g) ?? [];

    return [...new Set(found)].filter((token) => ! known.has(token));
});

const groups = computed(() => [
    {
        key: 'foundation',
        title: 'Identitas Yayasan',
        fields: [
            { name: 'name', label: 'Nama Yayasan' },
            { name: 'address', label: 'Alamat' },
            { name: 'notary_deed', label: 'Akta Notaris' },
            { name: 'sk_menkumham', label: 'Nomor SK Menkumham' },
            { name: 'chairman_name', label: 'Nama Ketua Yayasan' },
            { name: 'chairman_position', label: 'Jabatan Penanda Tangan' },
            { name: 'default_issued_place', label: 'Tempat Penetapan Default' },
        ],
    },
    {
        key: 'letterhead',
        title: 'Kop Surat & Tembusan',
        fields: [
            { name: 'cc_list', label: 'Daftar Tembusan Default (satu per baris, gunakan {satker} untuk nama satuan kerja)', type: 'textarea', full: true },
        ],
    },
    {
        key: 'nigy',
        title: 'Format NIGY',
        fields: [
            { name: 'format', label: 'Format — token: {tahun_masuk} {bulan_masuk} {kode_satker} {kode_jenjang} {urut} {tahun_lahir} {bulan_lahir} {hari_lahir} {tipe_kepegawaian} {kode_tipe_kepegawaian}' },
            { name: 'padding', label: 'Panjang Padding Nomor Urut' },
        ],
    },
]);

// Error dari server berkunci datar ("foundation.name"), bukan bersarang.
function fieldError(group, field) {
    return form.errors[`${group}.${field}`];
}

// Field yang divalidasi server sebagai `array` (mis. `letterhead.cc_list`).
// Diturunkan dari skema server agar textarea kosong tetap terkirim sebagai
// larik kosong, bukan string kosong yang gagal validasi.
const arrayFields = computed(() => new Set(
    Object.entries(props.schema ?? {})
        .filter(([key, field]) => !key.includes('*') && (field.rules ?? []).includes('array'))
        .map(([key]) => key),
));

function buildPayload(data) {
    const payload = {};

    Object.entries(data).forEach(([group, values]) => {
        payload[group] = { ...values };
    });

    arrayFields.value.forEach((path) => {
        const [group, field] = path.split('.');

        if (! payload[group]) {
            return;
        }

        payload[group][field] = String(payload[group][field] ?? '')
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean);
    });

    return payload;
}

function save() {
    form.transform(buildPayload).post('/admin/settings', { preserveScroll: true });
}

const signatureForm = useForm({ current_password: '', file: null });
const logoForm = useForm({ file: null });

function uploadLogo() {
    logoForm.post('/admin/settings/logo', { preserveScroll: true, onSuccess: () => logoForm.reset() });
}

function uploadSignature() {
    signatureForm.post('/admin/settings/signature', { preserveScroll: true, onSuccess: () => signatureForm.reset() });
}
</script>
