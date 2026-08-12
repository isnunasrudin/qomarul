<template>
    <AdminLayout>
        <Head :title="'Data GTK'" />

        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Data GTK</h2>
            <div class="flex gap-2">
                <a :href="route('admin.employees.export', { template: 1 })"
                   class="btn-secondary">
                    Template Impor
                </a>
                <a :href="route('admin.employees.export', filters)" target="_blank"
                   class="btn-secondary">
                    Ekspor Excel
                </a>
                <button type="button" @click="openImport"
                        class="rounded-md border border-primary-200 px-3 py-2 text-sm text-primary-600 hover:bg-primary-50">
                    Impor Excel
                </button>
                <Link :href="route('admin.employees.create')"
                      class="btn-primary">
                    {{ t('common.create') }}
                </Link>
            </div>
        </div>

        <form class="card mb-4 grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6" @submit.prevent="applyFilters">
            <input v-model="filters.q" type="search" placeholder="Cari NIGY, nama, NIK, NUPTK..."
                   class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 lg:col-span-2">
            <select v-model="filters.work_unit_id" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">Semua Satuan Kerja</option>
                <option v-for="unit in workUnits" :key="unit.id" :value="unit.id">{{ unit.code }} — {{ unit.name }}</option>
            </select>
            <select v-model="filters.position_id" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">Semua Jabatan</option>
                <option v-for="position in positions" :key="position.id" :value="position.id">{{ position.name }}</option>
            </select>
            <select v-model="filters.employment_status_id" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">Semua Status</option>
                <option v-for="status in employmentStatuses" :key="status.id" :value="status.id">{{ status.name }}</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 btn-primary">Cari</button>
                <button type="button" @click="resetFilters" class="btn-secondary">Reset</button>
            </div>
        </form>

        <div v-if="importModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="importModal = false">
            <div class="w-full max-w-md card p-6">
                <h3 class="mb-2 text-base font-semibold text-gray-800">Impor GTK dari Excel</h3>
                <p class="mb-4 text-sm text-gray-500">
                    Unduh <a :href="route('admin.employees.export', { template: 1 })" class="text-primary-600 hover:underline">template impor</a>,
                    isi sesuai contoh, lalu unggah. Berkas akan divalidasi baris per baris sebelum disimpan.
                </p>
                <form @submit.prevent="submitImport" class="space-y-4">
                    <input type="file" accept=".xlsx,.xls,.csv" @change="(e) => { importForm.file = e.target.files[0]; }"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                    <p v-if="importForm.errors.file" class="error-text" role="alert">{{ importForm.errors.file }}</p>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="importModal = false" class="btn-secondary">Batal</button>
                        <button type="submit" :disabled="importForm.processing" class="rounded-md bg-primary-600 px-4 py-2 text-sm text-white hover:bg-primary-700 disabled:opacity-50">Pratinjau</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-wrap">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">NIGY</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Nama</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Satuan Kerja</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Jabatan</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="employee in employees.data" :key="employee.id">
                        <td class="px-4 py-3 font-mono text-gray-700">{{ employee.nigy }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ employee.name }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ employee.work_unit?.name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ employee.position?.name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span :class="employee.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                  class="rounded-full px-2 py-0.5 text-xs">
                                {{ employee.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.employees.show', employee.id)" class="text-primary-600 hover:underline">Buka</Link>
                        </td>
                    </tr>
                    <tr v-if="!employees.data.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">Tidak ada data</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
            <span>Halaman {{ employees.current_page }} / {{ employees.last_page }} · {{ employees.total }} data</span>
            <div v-if="employees.last_page > 1" class="flex gap-2">
                <button v-if="employees.prev_page_url" type="button" @click="paginate(employees.current_page - 1)"
                        class="btn-secondary px-3 py-1">←</button>
                <button v-if="employees.next_page_url" type="button" @click="paginate(employees.current_page + 1)"
                        class="btn-secondary px-3 py-1">→</button>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { inject, reactive, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');
import { useTranslation } from '../../../helpers/translation';

const { t } = useTranslation();

const props = defineProps(['employees', 'filters', 'workUnits', 'positions', 'employmentStatuses']);

const filters = reactive({
    q: props.filters.q ?? '',
    work_unit_id: props.filters.work_unit_id ?? '',
    position_id: props.filters.position_id ?? '',
    employment_status_id: props.filters.employment_status_id ?? '',
    is_active: props.filters.is_active ?? '',
});

const importModal = ref(false);
const importForm = useForm({ file: null });

function openImport() {
    importForm.clearErrors();
    importForm.file = null;
    importModal.value = true;
}

function submitImport() {
    importForm.post(route('admin.employees.import.preview'), { onSuccess: () => { importModal.value = false; } });
}

function applyFilters() {
    router.get(route('admin.employees.index'), filters, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filters.q = '';
    filters.work_unit_id = '';
    filters.position_id = '';
    filters.employment_status_id = '';
    router.get(route('admin.employees.index'), {}, { preserveState: true, preserveScroll: true });
}

function paginate(page) {
    router.get(route('admin.employees.index'), { ...filters, page }, { preserveState: true, preserveScroll: true });
}
</script>
