<template>
    <AdminLayout>
        <Head :title="employee.full_name" />

        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">{{ employee.full_name }}</h2>
                <p class="text-sm text-gray-500">
                    NIGY <span class="font-mono">{{ employee.nigy }}</span> ·
                    {{ employee.work_unit?.name }} · {{ employee.position?.name }} ·
                    {{ employee.employment_status?.name }}
                </p>
            </div>
            <div class="flex gap-2">
                <Link v-if="can.update" :href="route('admin.employees.edit', employee.id)"
                      class="btn-secondary">
                    {{ t('common.edit') }}
                </Link>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-700">Kelengkapan Profil</p>
                    <p class="text-sm font-semibold text-gray-800">{{ completeness.percentage }}%</p>
                </div>
                <div class="mt-2 h-3 rounded bg-gray-100">
                    <div class="h-3 rounded bg-primary-500 transition-all" :style="{ width: `${completeness.percentage}%` }"></div>
                </div>
                <p v-if="completeness.missing.length" class="mt-2 text-xs text-gray-500">
                    Kurang: {{ completeness.missing.join(', ') }}
                </p>
            </div>

            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-700">Akun Pengguna</p>
                    <button v-if="!employee.user && can.createUser" type="button" @click="openCreateUser"
                            class="rounded-md bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-700">
                        Buat Akun
                    </button>
                </div>
                <p v-if="employee.user" class="mt-2 text-sm text-gray-800">
                    <span class="font-mono">{{ employee.user.username }}</span>
                    <span class="text-xs text-gray-500"> · {{ employee.user.email }}</span>
                </p>
                <p v-else class="mt-2 text-sm text-gray-500">
                    {{ can.createUser ? 'Belum ada akun — buat agar GTK bisa masuk ke portal.' : 'Belum ada akun pengguna.' }}
                </p>
            </div>
        </div>

        <div class="card">
            <div class="border-b border-gray-200">
                <nav class="flex gap-1 px-4 pt-3">
                    <button v-for="tab in tabs" :key="tab.key" type="button" @click="activeTab = tab.key"
                            class="rounded-t-md px-4 py-2 text-sm font-medium"
                            :class="activeTab === tab.key ? 'bg-primary-50 text-primary-700' : 'text-gray-500 hover:text-gray-700'">
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <div v-if="activeTab === 'info'" class="grid grid-cols-1 gap-x-8 gap-y-3 p-6 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="item in infoItems" :key="item.label">
                    <p class="text-xs text-gray-400">{{ item.label }}</p>
                    <p class="text-sm text-gray-700">{{ item.value || '—' }}</p>
                </div>
            </div>

            <div v-if="activeTab === 'pendidikan'" class="p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Riwayat Pendidikan</h3>
                    <button type="button" @click="openEducation()" class="btn-primary px-3 py-1.5">
                        Tambah
                    </button>
                </div>
                <div class="overflow-hidden rounded-lg border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Jenjang</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Institusi</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Jurusan</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Tahun</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600">Tertinggi</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="education in employee.educations" :key="education.id">
                                <td class="px-3 py-2 text-gray-700">{{ education.level }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ education.institution }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ education.major || '—' }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ education.start_year }}–{{ education.end_year }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span v-if="education.is_highest" class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-primary-600">Ya</span>
                                </td>
                                <td class="px-3 py-2 text-right space-x-3">
                                    <button type="button" class="text-primary-600 hover:underline" @click="openEducation(education)">Sunting</button>
                                    <button type="button" class="text-red-600 hover:underline" @click="deleteEducation(education)">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="!employee.educations.length">
                                <td colspan="6" class="px-3 py-10 text-center text-sm text-slate-400">Belum ada riwayat pendidikan</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="activeTab === 'berkas'" class="p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Berkas Kepegawaian</h3>
                    <button type="button" @click="openDocument()" class="btn-primary px-3 py-1.5">
                        Unggah Berkas
                    </button>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="document in employee.documents" :key="document.id"
                         class="flex items-center justify-between rounded-lg border border-gray-100 p-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-700">{{ document.name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ categoryLabel(document.category) }} · {{ (document.size / 1024).toFixed(0) }} KB
                                <span v-if="document.uploaded_by_employee"
                                      class="ml-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">
                                    diubah oleh yang bersangkutan
                                </span>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <a :href="document.signed_url" target="_blank" class="text-primary-600 hover:underline">Unduh</a>
                            <button type="button" class="text-red-600 hover:underline" @click="deleteDocument(document)">Hapus</button>
                        </div>
                    </div>
                    <div v-if="!employee.documents.length" class="col-span-full py-10 text-center text-sm text-slate-400">Belum ada berkas</div>
                </div>
            </div>
        </div>

        <div v-if="modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
            <div class="w-full max-w-md card p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">{{ modal.title }}</h3>

                <form v-if="modal.kind === 'education'" @submit.prevent="saveEducation" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Jenjang</label>
                            <select v-model="edForm.level" class="input">
                                <option v-for="level in educationLevels" :key="level.value" :value="level.value">{{ level.label }}</option>
                            </select>
                            <p v-if="edForm.errors.level" class="error-text" role="alert">{{ edForm.errors.level }}</p>
                        </div>
                        <div>
                            <label class="label">Tahun Masuk</label>
                            <input v-model="edForm.start_year" type="number" class="input">
                        </div>
                    </div>
                    <div>
                        <label class="label">Institusi</label>
                        <input v-model="edForm.institution" type="text" class="input">
                        <p v-if="edForm.errors.institution" class="error-text" role="alert">{{ edForm.errors.institution }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Jurusan</label>
                            <input v-model="edForm.major" type="text" class="input">
                        </div>
                        <div>
                            <label class="label">Tahun Lulus</label>
                            <input v-model="edForm.end_year" type="number" class="input">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Nomor Ijazah</label>
                            <input v-model="edForm.certificate_number" type="text" class="input">
                        </div>
                        <div>
                            <label class="label">Tanggal Ijazah</label>
                            <input v-model="edForm.certificate_date" type="date" class="input">
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input v-model="edForm.is_highest" type="checkbox" class="checkbox">
                        Pendidikan tertinggi
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="btn-secondary">Batal</button>
                        <button type="submit" :disabled="edForm.processing" class="rounded-md bg-primary-600 px-4 py-2 text-sm text-white hover:bg-primary-700 disabled:opacity-50">Simpan</button>
                    </div>
                </form>

                <form v-else-if="modal.kind === 'document'" @submit.prevent="uploadDocument" class="space-y-4">
                    <div>
                        <label class="label">Kategori</label>
                        <select v-model="docForm.category" class="input">
                            <option v-for="category in documentCategories" :key="category.value" :value="category.value">{{ category.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Nama Berkas (opsional)</label>
                        <input v-model="docForm.name" type="text" class="input">
                    </div>
                    <div>
                        <label class="label">Berkas (PDF/JPG/PNG, maks 5 MB)</label>
                        <input type="file" accept="application/pdf,image/jpeg,image/png" @change="(e) => { docForm.file = e.target.files[0]; }"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                        <p v-if="docForm.errors.file" class="error-text" role="alert">{{ docForm.errors.file }}</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="btn-secondary">Batal</button>
                        <button type="submit" :disabled="docForm.processing" class="rounded-md bg-primary-600 px-4 py-2 text-sm text-white hover:bg-primary-700 disabled:opacity-50">Unggah</button>
                    </div>
                </form>

                <form v-else-if="modal.kind === 'user'" @submit.prevent="createUser" class="space-y-4">
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <label class="label">Nama Pengguna</label>
                            <input v-model="userForm.username" type="text" class="input" autocomplete="off">
                            <p v-if="userForm.errors.username" class="error-text" role="alert">{{ userForm.errors.username }}</p>
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input v-model="userForm.email" type="email" class="input">
                            <p v-if="userForm.errors.email" class="error-text" role="alert">{{ userForm.errors.email }}</p>
                        </div>
                        <div>
                            <label class="label">Kata Sandi (opsional)</label>
                            <input v-model="userForm.password" type="text" class="input" placeholder="Kosongkan untuk otomatis">
                            <p class="mt-1 text-xs text-gray-500">
                                Jika dikosongkan, sandi acak dibuat dan hanya ditampilkan sekali.
                            </p>
                            <p v-if="userForm.errors.password" class="error-text" role="alert">{{ userForm.errors.password }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="btn-secondary">Batal</button>
                        <button type="submit" :disabled="userForm.processing" class="rounded-md bg-primary-600 px-4 py-2 text-sm text-white hover:bg-primary-700 disabled:opacity-50">Buat Akun</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, inject, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');
import { useTranslation } from '../../../helpers/translation';
import { formatTanggal } from '../../../utils/date';

const { t } = useTranslation();

const props = defineProps(['employee', 'completeness', 'can', 'documentCategories', 'educationLevels']);

const activeTab = ref('info');
const modal = ref(null);

const userForm = useForm({
    username: props.employee?.nigy ?? '',
    email: props.employee?.email ?? '',
    password: '',
});

function openCreateUser() {
    userForm.reset();
    userForm.username = props.employee?.nigy ?? '';
    userForm.email = props.employee?.email ?? '';
    userForm.clearErrors();
    modal.value = { kind: 'user', title: 'Buat Akun Pengguna' };
}

function createUser() {
    userForm.post(`/admin/employees/${props.employee.id}/user`, {
        preserveScroll: true,
        onSuccess: () => { modal.value = null; },
    });
}

const tabs = [
    { key: 'info', label: 'Informasi' },
    { key: 'pendidikan', label: 'Pendidikan' },
    { key: 'berkas', label: 'Berkas' },
];

const categoryLabels = Object.fromEntries((props.documentCategories ?? []).map((c) => [c.value, c.label]));

function categoryLabel(value) {
    return categoryLabels[value] ?? value;
}

const infoItems = computed(() => {
    const e = props.employee;
    return [
        { label: 'NIK', value: e.nik },
        { label: 'NUPTK', value: e.nuptk },
        { label: 'NIP', value: e.nip },
        { label: 'Tempat/Tanggal Lahir', value: e.birth_place ? `${e.birth_place}, ${formatTanggal(e.birth_date)}` : formatTanggal(e.birth_date) },
        { label: 'Agama', value: e.religion },
        { label: 'Status Pernikahan', value: e.marital_status },
        { label: 'Ibu Kandung', value: e.mother_name },
        { label: 'Alamat', value: e.address },
        { label: 'Kontak', value: e.phone },
        { label: 'Email', value: e.email },
        { label: 'TMT Yayasan', value: formatTanggal(e.foundation_start_date) },
        { label: 'TMT Satuan Kerja', value: formatTanggal(e.unit_start_date) },
        { label: 'Mata Pelajaran', value: e.subject },
        { label: 'Bank', value: e.bank_name },
        { label: 'Nomor Rekening', value: e.bank_account_number },
        { label: 'Status', value: e.is_active ? 'Aktif' : 'Nonaktif' },
    ];
});

const edForm = useForm({
    level: 'S1', institution: '', major: '', start_year: '', end_year: '',
    certificate_number: '', certificate_date: '', is_highest: false,
});

function openEducation(education = null) {
    edForm.clearErrors();
    if (education) {
        Object.assign(edForm, {
            level: education.level, institution: education.institution, major: education.major,
            start_year: education.start_year, end_year: education.end_year,
            certificate_number: education.certificate_number, certificate_date: education.certificate_date,
            is_highest: education.is_highest,
        });
        modal.value = { kind: 'education', title: 'Sunting Pendidikan', id: education.id };
    } else {
        edForm.reset();
        edForm.is_highest = props.employee.educations.length === 0;
        modal.value = { kind: 'education', title: 'Tambah Pendidikan' };
    }
}

function saveEducation() {
    if (modal.value.id) {
        edForm.put(route('admin.employees.educations.update', [props.employee.id, modal.value.id]), { preserveScroll: true, onSuccess: () => { modal.value = null; } });
    } else {
        edForm.post(route('admin.employees.educations.store', props.employee.id), { preserveScroll: true, onSuccess: () => { modal.value = null; } });
    }
}

function deleteEducation(education) {
    if (confirm('Hapus riwayat pendidikan ini?')) {
        router.delete(route('admin.employees.educations.destroy', [props.employee.id, education.id]));
    }
}

const docForm = useForm({ category: 'other', name: '', file: null });

function openDocument() {
    docForm.clearErrors();
    docForm.category = 'other';
    docForm.name = '';
    docForm.file = null;
    modal.value = { kind: 'document', title: 'Unggah Berkas' };
}

function uploadDocument() {
    docForm.post(route('admin.employees.documents.store', props.employee.id), { preserveScroll: true, onSuccess: () => { modal.value = null; } });
}

function deleteDocument(document) {
    if (confirm('Hapus berkas ini?')) {
        router.delete(route('admin.documents.destroy', document.id));
    }
}
</script>
