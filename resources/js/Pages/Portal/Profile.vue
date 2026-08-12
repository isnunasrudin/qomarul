<template>
    <AdminLayout>
        <Head :title="'Data Pribadi'" />

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-foreground">Data Pribadi</h2>
                <p class="text-sm text-slate-500">Lengkapi data berikut agar profil terverifikasi penuh.</p>
            </div>
            <span class="badge" :class="completeness.complete ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                Profil {{ completeness.percentage }}%
            </span>
        </div>

        <div v-if="missingPribadi.length" class="card mb-6 border-amber-200 bg-amber-50/60 p-5">
            <div class="flex items-start gap-3">
                <AlertCircle :size="18" class="mt-0.5 shrink-0 text-amber-600" />
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-amber-800">Belum diisi</p>
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <span v-for="key in missingPribadi" :key="key"
                              class="rounded-full border border-amber-300 bg-white px-2.5 py-1 text-xs text-amber-800">
                            {{ missingLabel(key) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <form @submit.prevent="saveProfile" enctype="multipart/form-data" class="card p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="field in profileFields" :key="field.key" :class="{ 'sm:col-span-2 lg:col-span-3': field.full }">
                    <label class="label text-xs" :class="isMissing(field.key) ? 'font-semibold text-amber-700' : ''">
                        {{ field.label }}
                        <span v-if="isMissing(field.key)" class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold">kosong</span>
                    </label>
                    <select v-if="field.type === 'select'" v-model="profileForm[field.key]"
                            class="input" :class="isMissing(field.key) ? 'border-amber-400 bg-amber-50/40' : ''">
                        <option value="">{{ t('common.select') }}</option>
                        <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <textarea v-else-if="field.type === 'textarea'" v-model="profileForm[field.key]" rows="2"
                              class="input" :class="isMissing(field.key) ? 'border-amber-400 bg-amber-50/40' : ''"></textarea>
                    <input v-else v-model="profileForm[field.key]" :type="field.type ?? 'text'"
                           class="input" :class="isMissing(field.key) ? 'border-amber-400 bg-amber-50/40' : ''">
                    <p v-if="profileForm.errors[field.key]" class="error-text" role="alert">{{ profileForm.errors[field.key] }}</p>
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="label text-xs" :class="isMissing('pribadi.photo_path') ? 'font-semibold text-amber-700' : ''">
                        Pas Foto (JPG/PNG/WEBP, maks 2 MB, dipotong 3:4)
                        <span v-if="isMissing('pribadi.photo_path')" class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold">kosong</span>
                    </label>
                    <input type="file" accept="image/jpeg,image/png,image/webp" @change="(e) => { profileForm.photo = e.target.files[0]; }"
                           class="mt-0.5 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                    <p v-if="profileForm.errors.photo" class="error-text" role="alert">{{ profileForm.errors.photo }}</p>
                </div>

                <button type="submit" :disabled="profileForm.processing"
                        class="btn-primary disabled:opacity-50 sm:col-span-2 lg:col-span-3">
                    Simpan Data Pribadi
                </button>
            </div>
        </form>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { AlertCircle } from 'lucide-vue-next';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import { useTranslation } from '../../helpers/translation';
import { isPribadi, missingLabel } from '../../helpers/completeness';

const { t } = useTranslation();

const props = defineProps(['employee', 'completeness']);

const missingKeys = computed(() => new Set((props.completeness?.missing ?? []).filter(isPribadi)));
const missingPribadi = computed(() => (props.completeness?.missing ?? []).filter(isPribadi));

function isMissing(key) {
    return missingKeys.value.has(key);
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
</script>
