<template>
    <AdminLayout>
        <Head :title="isEdit ? 'Sunting GTK' : 'Tambah GTK'" />

        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">
                {{ isEdit ? `Sunting — ${employee.name}` : 'Tambah GTK' }}
            </h2>
            <div v-if="isEdit" class="flex gap-2">
                <button type="button" @click="toggleNigyEdit"
                        :disabled="!can.updateNigy"
                        class="rounded-md border border-amber-300 px-3 py-2 text-sm text-amber-700 hover:bg-amber-50 disabled:opacity-40"
                        :title="!can.updateNigy ? 'Hanya Admin Yayasan yang dapat menimpa NIGY' : ''">
                    {{ showNigyField ? 'Sembunyikan NIGY' : 'Ubah NIGY' }}
                </button>
                <button type="button" @click="confirmDelete"
                        class="btn-danger disabled:opacity-40"
                        :disabled="!can.delete">
                    Hapus
                </button>
            </div>
        </div>

        <form @submit.prevent="submit" enctype="multipart/form-data" class="card">
            <div class="border-b border-gray-200">
                <nav class="flex gap-1 px-4 pt-3">
                    <button v-for="tab in tabs" :key="tab.key" type="button" @click="activeTab = tab.key"
                            class="rounded-t-md px-4 py-2 text-sm font-medium"
                            :class="activeTab === tab.key ? 'bg-primary-50 text-primary-700' : 'text-gray-500 hover:text-gray-700'">
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <div v-if="activeTab === 'pribadi'" class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="field in personalFields" :key="field.key" :class="{ 'sm:col-span-2 lg:col-span-3': field.full }">
                    <label class="label">{{ field.label }}</label>
                    <select v-if="field.type === 'select'" v-model="form[field.key]"
                            class="input">
                        <option value="">{{ t('common.select') }}</option>
                        <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <textarea v-else-if="field.type === 'textarea'" v-model="form[field.key]" rows="2"
                              class="input"></textarea>
                    <input v-else v-model="form[field.key]" :type="field.type ?? 'text'"
                           class="input">
                    <p v-if="form.errors[field.key]" class="error-text" role="alert">{{ form.errors[field.key] }}</p>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="label">Pas Foto (JPG/PNG/WEBP, maks 2 MB, otomatis dipotong 3:4)</label>
                    <input type="file" accept="image/jpeg,image/png,image/webp" @change="(e) => { form.photo = e.target.files[0]; }"
                           class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                    <p v-if="form.errors.photo" class="error-text" role="alert">{{ form.errors.photo }}</p>
                </div>
            </div>

            <div v-if="activeTab === 'kepegawaian'" class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
                <div v-if="isEdit" class="sm:col-span-2 lg:col-span-3 rounded-md bg-gray-50 p-3">
                    <label class="label">NIGY</label>
                    <p class="mt-1 font-mono text-base font-semibold text-gray-800">
                        {{ form.nigy }}
                        <span v-if="nigyLocked" class="ml-2 text-xs font-normal text-red-600">terkunci — sudah tercetak pada SK terbit</span>
                    </p>
                    <div v-if="showNigyField && can.updateNigy" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label text-xs">NIGY baru (timpa manual)</label>
                            <input v-model="form.nigy" type="text"
                                   class="input">
                            <p v-if="form.errors.nigy" class="error-text" role="alert">{{ form.errors.nigy }}</p>
                        </div>
                        <div>
                            <label class="label text-xs">Alasan perubahan</label>
                            <input v-model="form.nigy_reason" type="text"
                                   class="input">
                        </div>
                    </div>
                </div>
                <div v-for="field in employmentFields" :key="field.key" :class="{ 'sm:col-span-2 lg:col-span-3': field.full }">
                    <label class="label">{{ field.label }}</label>
                    <select v-if="field.type === 'select'" v-model="form[field.key]"
                            class="input">
                        <option v-if="field.placeholder !== false" value="">{{ field.placeholder ?? t('common.select') }}</option>
                        <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <input v-else v-model="form[field.key]" :type="field.type ?? 'text'"
                           class="input">
                    <p v-if="form.errors[field.key]" class="error-text" role="alert">{{ form.errors[field.key] }}</p>
                </div>
            </div>

            <div v-if="activeTab === 'pendidikan' || activeTab === 'berkas'" class="p-6 text-center text-sm text-gray-500">
                <p class="mb-2">{{ activeTab === 'pendidikan' ? 'Pendidikan' : 'Berkas' }} dikelola di halaman profil GTK.</p>
                <Link v-if="isEdit" :href="route('admin.employees.show', employee.id)" class="text-primary-600 hover:underline">
                    Buka halaman {{ employee.name }} →
                </Link>
            </div>

            <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4">
                <button type="button" @click="back" class="btn-secondary">Kembali</button>
                <button type="submit" :disabled="form.processing"
                        class="btn-primary disabled:opacity-50">
                    {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                </button>
            </div>
        </form>
    </AdminLayout>
</template>

<script setup>
import { computed, inject, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');
import { useTranslation } from '../../../helpers/translation';

const { t } = useTranslation();

const props = defineProps([
    'employee', 'workUnits', 'positions', 'employmentStatuses',
    'genders', 'religions', 'maritalStatuses', 'documentCategories', 'nigyLocked',
    'can',
]);

const isEdit = computed(() => Boolean(props.employee));
const can = computed(() => props.can ?? {});

const form = useForm({
    nigy: props.employee?.nigy ?? '',
    nigy_reason: '',
    title_prefix: props.employee?.title_prefix ?? '',
    name: props.employee?.name ?? '',
    title_suffix: props.employee?.title_suffix ?? '',
    gender: props.employee?.gender ?? '',
    birth_place: props.employee?.birth_place ?? '',
    birth_date: props.employee?.birth_date ?? '',
    religion: props.employee?.religion ?? '',
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
    npwp: props.employee?.npwp ?? '',
    bank_account_number: props.employee?.bank_account_number ?? '',
    bank_name: props.employee?.bank_name ?? '',
    blood_type: props.employee?.blood_type ?? '',
    work_unit_id: props.employee?.work_unit_id ?? '',
    position_id: props.employee?.position_id ?? '',
    employment_status_id: props.employee?.employment_status_id ?? '',
    foundation_start_date: props.employee?.foundation_start_date ?? '',
    unit_start_date: props.employee?.unit_start_date ?? '',
    subject: props.employee?.subject ?? '',
    is_active: props.employee?.is_active ?? true,
    photo: null,
});

const activeTab = ref('pribadi');
const showNigyField = ref(false);

function toggleNigyEdit() {
    showNigyField.value = !showNigyField.value;

    if (showNigyField.value) {
        activeTab.value = 'kepegawaian';
    }
}

const tabs = [
    { key: 'pribadi', label: 'Pribadi' },
    { key: 'kepegawaian', label: 'Kepegawaian' },
    { key: 'pendidikan', label: 'Pendidikan' },
    { key: 'berkas', label: 'Berkas' },
];

const personalFields = computed(() => [
    { key: 'title_prefix', label: 'Gelar Depan' },
    { key: 'name', label: 'Nama Lengkap' },
    { key: 'title_suffix', label: 'Gelar Belakang' },
    { key: 'gender', label: 'Jenis Kelamin', type: 'select', options: props.genders ?? [] },
    { key: 'birth_place', label: 'Tempat Lahir' },
    { key: 'birth_date', label: 'Tanggal Lahir', type: 'date' },
    { key: 'religion', label: 'Agama', type: 'select', options: props.religions ?? [] },
    { key: 'marital_status', label: 'Status Pernikahan', type: 'select', options: props.maritalStatuses ?? [] },
    { key: 'mother_name', label: 'Nama Ibu Kandung' },
    { key: 'nik', label: 'NIK' },
    { key: 'nuptk', label: 'NUPTK' },
    { key: 'nip', label: 'NIP' },
    { key: 'address', label: 'Alamat KTP', type: 'textarea', full: true },
    { key: 'rt', label: 'RT' },
    { key: 'rw', label: 'RW' },
    { key: 'village', label: 'Desa' },
    { key: 'district', label: 'Kecamatan' },
    { key: 'regency', label: 'Kabupaten' },
    { key: 'province', label: 'Provinsi' },
    { key: 'postal_code', label: 'Kode Pos' },
    { key: 'phone', label: 'Nomor HP' },
    { key: 'email', label: 'Email' },
    { key: 'npwp', label: 'NPWP' },
    { key: 'bank_name', label: 'Bank' },
    { key: 'bank_account_number', label: 'Nomor Rekening' },
    { key: 'blood_type', label: 'Golongan Darah' },
]);

const employmentFields = computed(() => [
    { key: 'work_unit_id', label: 'Satuan Kerja', type: 'select', options: props.workUnits.map((u) => ({ value: u.id, label: `${u.code} — ${u.name}` })) },
    { key: 'position_id', label: 'Jabatan', type: 'select', options: props.positions.map((p) => ({ value: p.id, label: p.name })) },
    { key: 'employment_status_id', label: 'Status Kepegawaian', type: 'select', options: props.employmentStatuses.map((s) => ({ value: s.id, label: s.name })) },
    { key: 'foundation_start_date', label: 'TMT Yayasan', type: 'date' },
    { key: 'unit_start_date', label: 'TMT Satuan Kerja', type: 'date' },
    { key: 'subject', label: 'Mata Pelajaran Diampu' },
    { key: 'is_active', label: 'Status Aktif', type: 'checkbox', placeholder: false },
]);

function submit() {
    if (isEdit.value) {
        form.put(route('admin.employees.update', props.employee.id));
    } else {
        form.post(route('admin.employees.store'));
    }
}

function back() {
    window.history.length > 1 ? window.history.back() : router.get(route('admin.employees.index'));
}

function confirmDelete() {
    if (confirm('Yakin ingin menghapus GTK ini? Tindakan tidak dapat dibatalkan.')) {
        router.delete(route('admin.employees.destroy', props.employee.id));
    }
}
</script>
