<template>
    <AdminLayout>
        <Head :title="'Batch Baru'" />

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Batch Baru</h2>
            <p class="text-sm text-gray-500">Pilih jenis SK, periode, lalu seleksi GTK penerima. Maksimal {{ maxBatchSize }} SK per batch.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <section class="card p-6">
                <h3 class="mb-4 text-sm font-semibold text-gray-700">1. Jenis SK &amp; Periode</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Nama Batch</label>
                        <input v-model="form.name" type="text" placeholder="Perpanjangan TP 2026/2027"
                               class="input">
                        <p v-if="form.errors.name" class="error-text" role="alert">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="label">Jenis SK</label>
                        <select v-model="form.decree_type_id" class="input">
                            <option value="">Pilih Jenis</option>
                            <option v-for="type in decreeTypes" :key="type.id" :value="type.id">{{ type.code }} — {{ type.name }}</option>
                        </select>
                        <p v-if="form.errors.decree_type_id" class="error-text" role="alert">{{ form.errors.decree_type_id }}</p>
                    </div>
                    <div>
                        <label class="label">Tahun Pelajaran</label>
                        <input v-model="form.academic_year" type="text" placeholder="2026/2027"
                               class="input">
                    </div>
                    <div>
                        <label class="label">Diangkat Kembali Sebagai</label>
                        <input v-model="form.appointed_as" type="text" placeholder="kosong = jabatan GTK"
                               class="input">
                    </div>
                    <div>
                        <label class="label">TMT</label>
                        <input v-model="form.effective_date" type="date"
                               class="input">
                    </div>
                    <div>
                        <label class="label">Tanggal Penetapan</label>
                        <input v-model="form.issued_date" type="date"
                               class="input">
                    </div>
                </div>
            </section>

            <section class="card p-6">
                <h3 class="mb-4 text-sm font-semibold text-gray-700">2. Seleksi Penerima</h3>
                <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
                    <input v-model="filters.q" type="search" placeholder="Cari nama/NIGY..."
                           class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <select v-model="filters.work_unit_id" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Semua Satker</option>
                        <option v-for="unit in workUnits" :key="unit.id" :value="unit.id">{{ unit.code }} — {{ unit.name }}</option>
                    </select>
                    <select v-model="filters.position_id" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Semua Jabatan</option>
                        <option v-for="position in positions" :key="position.id" :value="position.id">{{ position.name }}</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="button" @click="applyFilters" class="flex-1 rounded-md bg-primary-600 px-3 py-2 text-sm text-white hover:bg-primary-700">Filter</button>
                        <button type="button" @click="selectAll" class="btn-secondary">Pilih Semua</button>
                    </div>
                </div>

                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        <b class="text-primary-600">{{ form.employee_ids.length }}</b> terpilih dari {{ employees.length }} GTK aktif
                    </p>
                    <button type="button" @click="form.employee_ids = []" class="text-xs text-gray-500 hover:underline">Kosongkan pilihan</button>
                </div>

                <div class="max-h-96 overflow-y-auto rounded-md border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="sticky top-0 bg-gray-50">
                            <tr>
                                <th class="w-10 px-3 py-2"></th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">NIGY</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Nama</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Satker</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Jabatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="employee in employees" :key="employee.id">
                                <td class="px-3 py-2">
                                    <input v-model="form.employee_ids" type="checkbox" :value="employee.id"
                                           class="checkbox">
                                </td>
                                <td class="px-3 py-2 font-mono text-xs text-gray-600">{{ employee.nigy }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ employee.name }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ employee.work_unit?.code }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ employee.position?.name }}</td>
                            </tr>
                            <tr v-if="!employees.length">
                                <td colspan="5" class="px-3 py-10 text-center text-sm text-slate-400">Tidak ada GTK</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="form.errors.employee_ids" class="mt-2 text-xs text-red-600">{{ form.errors.employee_ids }}</p>
            </section>

            <div class="flex items-center justify-between">
                <button type="button" @click="back" class="btn-secondary">Kembali</button>
                <button type="submit" :disabled="form.processing"
                        class="btn-primary disabled:opacity-50">
                    Buat {{ form.employee_ids.length }} Draft SK
                </button>
            </div>
        </form>
    </AdminLayout>
</template>

<script setup>
import { inject, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');

const props = defineProps(['employees', 'filters', 'decreeTypes', 'workUnits', 'positions', 'employmentStatuses', 'maxBatchSize']);

const filters = reactive({
    q: props.filters.q ?? '',
    work_unit_id: props.filters.work_unit_id ?? '',
    position_id: props.filters.position_id ?? '',
    employment_status_id: props.filters.employment_status_id ?? '',
});

const form = useForm({
    name: '',
    decree_type_id: '',
    academic_year: `${new Date().getFullYear()}/${new Date().getFullYear() + 1}`,
    effective_date: '',
    issued_date: new Date().toISOString().slice(0, 10),
    issued_place: '',
    appointed_as: '',
    employee_ids: [],
});

function applyFilters() {
    router.get(route('admin.batches.create'), filters, { preserveState: true, preserveScroll: true });
}

function selectAll() {
    form.employee_ids = props.employees.map((e) => e.id);
}

function submit() {
    if (form.employee_ids.length > props.maxBatchSize) {
        alert(`Maksimal ${props.maxBatchSize} SK per batch.`);
        return;
    }
    form.post(route('admin.batches.store'));
}

function back() {
    window.history.length > 1 ? window.history.back() : router.get(route('admin.batches.index'));
}
</script>
