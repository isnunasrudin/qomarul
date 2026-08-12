<template>
    <PortalLayout>
        <Head :title="'Beranda'" />

        <div v-if="employee" class="mb-5 rounded-xl bg-white p-5 shadow-sm">
            <p class="text-lg font-semibold text-gray-800">{{ employee.name }}</p>
            <p class="mt-1 text-sm text-gray-500">
                NIGY <span class="font-mono">{{ employee.nigy }}</span> ·
                {{ employee.work_unit?.name }} · {{ employee.position?.name }}
            </p>
            <div class="mt-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700">Kelengkapan Profil</span>
                    <span class="font-semibold text-primary-600">{{ completeness.percentage }}%</span>
                </div>
                <div class="mt-1 h-3 rounded-full bg-gray-100">
                    <div class="h-3 rounded-full bg-primary-500 transition-all" :style="{ width: `${completeness.percentage}%` }"></div>
                </div>
                <p v-if="completeness.missing.length" class="mt-2 text-xs text-gray-500">
                    Kurang: {{ completeness.missing.join(', ') }}
                </p>
            </div>
        </div>

        <div v-if="recentDecrees.length" class="mb-5 rounded-xl bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-gray-700">SK Terbaru</h2>
            <ul class="divide-y divide-gray-100">
                <li v-for="decree in recentDecrees" :key="decree.id" class="flex items-center justify-between py-2.5">
                    <div class="min-w-0">
                        <p class="truncate text-sm text-gray-700">{{ decree.decree_number || 'SK (arsip)' }}</p>
                        <p class="text-xs text-gray-400">{{ decree.decree_type?.name }} · {{ decree.effective_date }}</p>
                    </div>
                    <a v-if="!decree.is_legacy" :href="decree.download_url" target="_blank" rel="noopener" class="text-xs text-primary-600 hover:underline">Unduh PDF</a>
                    <span v-else class="text-xs text-gray-400">Arsip</span>
                </li>
            </ul>
        </div>

        <div v-if="activeDuties.length" class="mb-5 rounded-xl bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-gray-700">Tugas Tambahan Berjalan</h2>
            <ul class="space-y-2">
                <li v-for="duty in activeDuties" :key="duty.id" class="flex items-center justify-between text-sm">
                    <span class="text-gray-700">{{ duty.additional_duty?.name }}</span>
                    <span class="text-xs text-gray-400">s.d. {{ duty.end_date }}</span>
                </li>
            </ul>
        </div>

        <div class="space-y-5">
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-gray-700">Data Pribadi</h2>
                <form @submit.prevent="saveProfile" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div v-for="field in profileFields" :key="field.key">
                        <label class="label text-xs">{{ field.label }}</label>
                        <select v-if="field.type === 'select'" v-model="profileForm[field.key]"
                                class="input">
                            <option value="">{{ t('common.select') }}</option>
                            <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <textarea v-else-if="field.type === 'textarea'" v-model="profileForm[field.key]" rows="2"
                                  class="input"></textarea>
                        <input v-else v-model="profileForm[field.key]" :type="field.type ?? 'text'"
                               class="input">
                        <p v-if="profileForm.errors[field.key]" class="error-text" role="alert">{{ profileForm.errors[field.key] }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label text-xs">Pas Foto (JPG/PNG/WEBP, maks 2 MB, dipotong 3:4)</label>
                        <input type="file" accept="image/jpeg,image/png,image/webp" @change="(e) => { profileForm.photo = e.target.files[0]; }"
                               class="mt-0.5 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                        <p v-if="profileForm.errors.photo" class="error-text" role="alert">{{ profileForm.errors.photo }}</p>
                    </div>
                    <button type="submit" :disabled="profileForm.processing"
                            class="btn-primary disabled:opacity-50 sm:col-span-2">
                        Simpan Data Pribadi
                    </button>
                </form>
            </section>

            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-gray-700">Berkas Kepegawaian</h2>
                <form @submit.prevent="uploadDocument" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label text-xs">Kategori</label>
                        <select v-model="docForm.category"
                                class="input">
                            <option v-for="category in documentCategories" :key="category.value" :value="category.value">{{ category.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="label text-xs">Berkas (PDF/JPG/PNG, maks 5 MB)</label>
                        <input type="file" accept="application/pdf,image/jpeg,image/png" @change="(e) => { docForm.file = e.target.files[0]; }"
                               class="mt-0.5 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                        <p v-if="docForm.errors.file" class="error-text" role="alert">{{ docForm.errors.file }}</p>
                    </div>
                    <button type="submit" :disabled="docForm.processing"
                            class="btn-primary disabled:opacity-50 sm:col-span-2">
                        Unggah Berkas
                    </button>
                </form>
                <ul class="mt-4 divide-y divide-gray-100">
                    <li v-for="document in employee.documents" :key="document.id" class="flex items-center justify-between py-2 text-sm">
                        <div class="min-w-0">
                            <p class="truncate text-gray-700">{{ document.name }}</p>
                            <p class="text-xs text-gray-400">{{ categoryLabel(document.category) }}</p>
                        </div>
                        <a :href="document.signed_url" target="_blank" class="text-primary-600 hover:underline">Unduh</a>
                    </li>
                    <li v-if="!employee.documents.length" class="py-4 text-center text-sm text-slate-400">Belum ada berkas</li>
                </ul>
            </section>

            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="mb-1 text-sm font-semibold text-gray-700">Unggah Arsip SK Lama</h2>
                <p class="mb-3 text-xs text-gray-500">
                    Arsip berupa PDF pindaian SK yang pernah terbit. Setelah diunggah, admin akan memverifikasinya sebelum masuk riwayat resmi.
                </p>
                <form @submit.prevent="uploadLegacy" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label text-xs">Berkas PDF (maks 5 MB)</label>
                        <input type="file" accept="application/pdf" @change="(e) => { legacyForm.file = e.target.files[0]; }"
                               class="mt-0.5 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                        <p v-if="legacyForm.errors.file" class="error-text" role="alert">{{ legacyForm.errors.file }}</p>
                    </div>
                    <div>
                        <label class="label text-xs">Nomor SK (opsional)</label>
                        <input v-model="legacyForm.decree_number" type="text" class="input">
                    </div>
                    <div>
                        <label class="label text-xs">Tanggal Penetapan (opsional)</label>
                        <input v-model="legacyForm.issued_date" type="date" class="input">
                    </div>
                    <div>
                        <label class="label text-xs">Tahun Pelajaran (opsional)</label>
                        <input v-model="legacyForm.academic_year" type="text" placeholder="2025/2026" class="input">
                    </div>
                    <button type="submit" :disabled="legacyForm.processing"
                            class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50 sm:col-span-2">
                        Unggah Arsip SK
                    </button>
                </form>
            </section>
        </div>
    </PortalLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import PortalLayout from '../../Layouts/PortalLayout.vue';
import { useTranslation } from '../../helpers/translation';

const { t } = useTranslation();

const props = defineProps([
    'employee', 'completeness', 'recentDecrees', 'activeDuties', 'documentCategories',
]);

const categoryLabels = Object.fromEntries((props.documentCategories ?? []).map((c) => [c.value, c.label]));

function categoryLabel(value) {
    return categoryLabels[value] ?? value;
}

const profileForm = useForm({
    marital_status: props.employee?.marital_status ?? '',
    mother_name: props.employee?.mother_name ?? '',
    address: props.employee?.address ?? '',
    rt: props.employee?.rt ?? '',
    rw: props.employee?.rw ?? '',
    village: props.employee?.village ?? '',
    district: props.employee?.district ?? '',
    regency: props.employee?.regency ?? '',
    province: props.employee?.province ?? '',
    postal_code: props.employee?.postal_code ?? '',
    phone: props.employee?.phone ?? '',
    email: props.employee?.email ?? '',
    bank_name: props.employee?.bank_name ?? '',
    bank_account_number: props.employee?.bank_account_number ?? '',
    photo: null,
});

const profileFields = [
    { key: 'marital_status', label: 'Status Pernikahan', type: 'select', options: [
        { value: 'single', label: 'Belum Menikah' },
        { value: 'married', label: 'Menikah' },
        { value: 'widower', label: 'Duda' },
        { value: 'widow', label: 'Janda' },
    ] },
    { key: 'mother_name', label: 'Nama Ibu Kandung' },
    { key: 'phone', label: 'Nomor HP' },
    { key: 'email', label: 'Email' },
    { key: 'address', label: 'Alamat KTP', type: 'textarea', full: true },
    { key: 'rt', label: 'RT' },
    { key: 'rw', label: 'RW' },
    { key: 'village', label: 'Desa' },
    { key: 'district', label: 'Kecamatan' },
    { key: 'regency', label: 'Kabupaten' },
    { key: 'province', label: 'Provinsi' },
    { key: 'postal_code', label: 'Kode Pos' },
    { key: 'bank_name', label: 'Bank' },
    { key: 'bank_account_number', label: 'Nomor Rekening' },
];

function saveProfile() {
    profileForm.put('/portal/profile', { preserveScroll: true });
}

const docForm = useForm({ category: 'other', file: null });

function uploadDocument() {
    docForm.post('/portal/documents', { preserveScroll: true, onSuccess: () => { docForm.reset(); } });
}

const legacyForm = useForm({ file: null, decree_number: '', issued_date: '', academic_year: '' });

function uploadLegacy() {
    legacyForm.post('/portal/decrees/legacy', { preserveScroll: true, onSuccess: () => { legacyForm.reset(); } });
}
</script>
