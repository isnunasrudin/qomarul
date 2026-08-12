<template>
    <AdminLayout>
        <Head :title="'Tugas Tambahan'" />

        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Penetapan Tugas Tambahan</h2>
            <div class="flex gap-2">
                <button type="button" @click="openMass"
                        class="rounded-md border border-primary-200 px-3 py-2 text-sm text-primary-600 hover:bg-primary-50">
                    Penetapan Massal
                </button>
                <button type="button" @click="openSingle(null)"
                        class="btn-primary">
                    Tetapkan Tugas
                </button>
            </div>
        </div>

        <form class="card mb-4 grid grid-cols-1 gap-3 p-4 sm:grid-cols-3" @submit.prevent="applyFilters">
            <select v-model="filters.work_unit_id" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">Semua Satuan Kerja</option>
                <option v-for="unit in workUnits" :key="unit.id" :value="unit.id">{{ unit.code }} — {{ unit.name }}</option>
            </select>
            <select v-model="filters.academic_year" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">Semua Tahun Pelajaran</option>
                <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 btn-primary">Cari</button>
                <button type="button" @click="resetFilters" class="btn-secondary">Reset</button>
            </div>
        </form>

        <div class="table-wrap">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">GTK</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Tugas</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Satker</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Periode</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">TP</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="assignment in assignments.data" :key="assignment.id">
                        <td class="px-4 py-3">
                            <p class="text-gray-700">{{ assignment.employee?.name }}</p>
                            <p class="font-mono text-xs text-gray-400">{{ assignment.employee?.nigy }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ assignment.additional_duty?.name }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ assignment.work_unit?.code }}</td>
                        <td class="px-4 py-3">
                            <span class="text-gray-700">{{ formatDate(assignment.start_date) }} – {{ formatDate(assignment.end_date) }}</span>
                            <span v-if="expiringSoon(assignment)"
                                  class="ml-2 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">
                                berakhir ≤ 30 hari
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ assignment.academic_year }}</td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <button type="button" class="text-primary-600 hover:underline" @click="openSingle(assignment)">Sunting</button>
                            <button type="button" class="text-red-600 hover:underline" @click="remove(assignment)">Hapus</button>
                        </td>
                    </tr>
                    <tr v-if="!assignments.data.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">Tidak ada penetapan</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
            <span>Halaman {{ assignments.current_page }} / {{ assignments.last_page }} · {{ assignments.total }} penetapan</span>
            <div v-if="assignments.last_page > 1" class="flex gap-2">
                <button v-if="assignments.prev_page_url" type="button" @click="paginate(assignments.current_page - 1)" class="btn-secondary px-3 py-1">←</button>
                <button v-if="assignments.next_page_url" type="button" @click="paginate(assignments.current_page + 1)" class="btn-secondary px-3 py-1">→</button>
            </div>
        </div>

        <div v-if="modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto card p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">{{ modal.title }}</h3>

                <form v-if="modal.kind === 'single'" @submit.prevent="saveSingle" class="space-y-4">
                    <div>
                        <label class="label">GTK</label>
                        <select v-model="form.employee_id" class="input">
                            <option value="">Pilih GTK</option>
                            <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.nigy }} — {{ employee.name }}</option>
                        </select>
                        <p v-if="form.errors.employee_id" class="error-text" role="alert">{{ form.errors.employee_id }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Tugas Tambahan</label>
                            <select v-model="form.additional_duty_id" class="input">
                                <option value="">Pilih Tugas</option>
                                <option v-for="duty in duties" :key="duty.id" :value="duty.id">{{ duty.name }}</option>
                            </select>
                            <p v-if="form.errors.additional_duty_id" class="error-text" role="alert">{{ form.errors.additional_duty_id }}</p>
                        </div>
                        <div>
                            <label class="label">Satuan Kerja</label>
                            <select v-model="form.work_unit_id" class="input">
                                <option value="">Pilih Satker</option>
                                <option v-for="unit in workUnits" :key="unit.id" :value="unit.id">{{ unit.code }} — {{ unit.name }}</option>
                            </select>
                            <p v-if="form.errors.work_unit_id" class="error-text" role="alert">{{ form.errors.work_unit_id }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Tahun Pelajaran</label>
                            <input v-model="form.academic_year" type="text" placeholder="2026/2027" class="input">
                        </div>
                        <div>
                            <label class="label">Keterangan</label>
                            <input v-model="form.notes" type="text" placeholder="mis. Wali Kelas VII-A" class="input">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">TMT Mulai</label>
                            <input v-model="form.start_date" type="date" class="input">
                            <p v-if="form.errors.start_date" class="error-text" role="alert">{{ form.errors.start_date }}</p>
                        </div>
                        <div>
                            <label class="label">TMT Selesai</label>
                            <input v-model="form.end_date" type="date" class="input">
                            <p v-if="form.errors.end_date" class="error-text" role="alert">{{ form.errors.end_date }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="btn-secondary">Batal</button>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-primary-600 px-4 py-2 text-sm text-white hover:bg-primary-700 disabled:opacity-50">Simpan</button>
                    </div>
                </form>

                <form v-else-if="modal.kind === 'mass'" @submit.prevent="saveMass" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Tugas Tambahan</label>
                            <select v-model="massForm.additional_duty_id" class="input">
                                <option value="">Pilih Tugas</option>
                                <option v-for="duty in duties" :key="duty.id" :value="duty.id">{{ duty.name }}</option>
                            </select>
                            <p v-if="massForm.errors.additional_duty_id" class="error-text" role="alert">{{ massForm.errors.additional_duty_id }}</p>
                        </div>
                        <div>
                            <label class="label">Satuan Kerja</label>
                            <select v-model="massForm.work_unit_id" class="input">
                                <option value="">Pilih Satker</option>
                                <option v-for="unit in workUnits" :key="unit.id" :value="unit.id">{{ unit.code }} — {{ unit.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Tahun Pelajaran</label>
                            <input v-model="massForm.academic_year" type="text" placeholder="2026/2027" class="input">
                        </div>
                        <div>
                            <label class="label">Keterangan</label>
                            <input v-model="massForm.notes" type="text" class="input">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">TMT Mulai</label>
                            <input v-model="massForm.start_date" type="date" class="input">
                        </div>
                        <div>
                            <label class="label">TMT Selesai</label>
                            <input v-model="massForm.end_date" type="date" class="input">
                        </div>
                    </div>
                    <div>
                        <label class="label">Pilih GTK ({{ massForm.employee_ids.length }} terpilih)</label>
                        <div class="mt-1 max-h-48 overflow-y-auto rounded-md border border-gray-200">
                            <label v-for="employee in employees" :key="employee.id" class="flex items-center gap-2 border-b border-gray-100 px-3 py-1.5 text-sm hover:bg-gray-50">
                                <input v-model="massForm.employee_ids" type="checkbox" :value="employee.id" class="checkbox">
                                <span class="text-gray-700">{{ employee.nigy }} — {{ employee.name }}</span>
                            </label>
                        </div>
                        <p v-if="massForm.errors.employee_ids" class="error-text" role="alert">{{ massForm.errors.employee_ids }}</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="btn-secondary">Batal</button>
                        <button type="submit" :disabled="massForm.processing" class="rounded-md bg-primary-600 px-4 py-2 text-sm text-white hover:bg-primary-700 disabled:opacity-50">
                            Tetapkan {{ massForm.employee_ids.length }} GTK
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { inject, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { formatTanggal } from '../../../utils/date';

const route = inject('route');

const props = defineProps(['assignments', 'filters', 'workUnits', 'academicYears', 'duties', 'employees']);

const filters = reactive({
    work_unit_id: props.filters.work_unit_id ?? '',
    academic_year: props.filters.academic_year ?? '',
    employee_id: props.filters.employee_id ?? '',
});

const modal = ref(null);

function formatDate(value) {
    return formatTanggal(value);
}

function expiringSoon(assignment) {
    const end = new Date(assignment.end_date);
    const limit = new Date();
    limit.setDate(limit.getDate() + 30);
    return end >= new Date() && end <= limit;
}

const form = useForm({
    employee_id: '', additional_duty_id: '', work_unit_id: '', academic_year: '',
    start_date: '', end_date: '', notes: '',
});

function openSingle(assignment) {
    form.clearErrors();
    if (assignment) {
        Object.assign(form, {
            employee_id: assignment.employee_id,
            additional_duty_id: assignment.additional_duty_id,
            work_unit_id: assignment.work_unit_id,
            academic_year: assignment.academic_year,
            start_date: assignment.start_date,
            end_date: assignment.end_date,
            notes: assignment.notes ?? '',
        });
        modal.value = { kind: 'single', title: 'Sunting Penetapan', id: assignment.id };
    } else {
        form.reset();
        form.academic_year = `${new Date().getFullYear()}/${new Date().getFullYear() + 1}`;
        modal.value = { kind: 'single', title: 'Tetapkan Tugas Tambahan' };
    }
}

function saveSingle() {
    if (modal.value.id) {
        form.put(route('admin.duties.update', modal.value.id), { preserveScroll: true, onSuccess: () => { modal.value = null; } });
    } else {
        form.post(route('admin.duties.store'), { preserveScroll: true, onSuccess: () => { modal.value = null; } });
    }
}

const massForm = useForm({
    employee_ids: [], additional_duty_id: '', work_unit_id: '', academic_year: '',
    start_date: '', end_date: '', notes: '',
});

function openMass() {
    massForm.clearErrors();
    massForm.reset();
    massForm.academic_year = `${new Date().getFullYear()}/${new Date().getFullYear() + 1}`;
    modal.value = { kind: 'mass', title: 'Penetapan Massal' };
}

function saveMass() {
    massForm.post(route('admin.duties.mass'), { preserveScroll: true, onSuccess: () => { modal.value = null; } });
}

function remove(assignment) {
    if (confirm('Hapus penetapan tugas ini?')) {
        router.delete(route('admin.duties.destroy', assignment.id));
    }
}

function applyFilters() {
    router.get(route('admin.duties.index'), filters, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filters.work_unit_id = '';
    filters.academic_year = '';
    router.get(route('admin.duties.index'), {}, { preserveState: true, preserveScroll: true });
}

function paginate(page) {
    router.get(route('admin.duties.index'), { ...filters, page }, { preserveState: true, preserveScroll: true });
}
</script>
