<template>
    <AdminLayout>
        <Head :title="'Tugas Tambahan'" />

        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Penetapan Tugas Tambahan</h2>
            <div class="flex gap-2">
                <button type="button" @click="openMass"
                        class="rounded-md border border-emerald-300 px-3 py-2 text-sm text-emerald-700 hover:bg-emerald-50">
                    Penetapan Massal
                </button>
                <button type="button" @click="openSingle(null)"
                        class="rounded-md bg-emerald-700 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-800">
                    Tetapkan Tugas
                </button>
            </div>
        </div>

        <form class="mb-4 grid grid-cols-1 gap-3 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-3" @submit.prevent="applyFilters">
            <select v-model="filters.work_unit_id" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">Semua Satuan Kerja</option>
                <option v-for="unit in workUnits" :key="unit.id" :value="unit.id">{{ unit.code }} — {{ unit.name }}</option>
            </select>
            <select v-model="filters.academic_year" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">Semua Tahun Pelajaran</option>
                <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-md bg-emerald-700 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-800">Cari</button>
                <button type="button" @click="resetFilters" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">Reset</button>
            </div>
        </form>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
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
                            <button type="button" class="text-emerald-700 hover:underline" @click="openSingle(assignment)">Sunting</button>
                            <button type="button" class="text-red-600 hover:underline" @click="remove(assignment)">Hapus</button>
                        </td>
                    </tr>
                    <tr v-if="!assignments.data.length">
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada penetapan</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
            <span>Halaman {{ assignments.current_page }} / {{ assignments.last_page }} · {{ assignments.total }} penetapan</span>
            <div v-if="assignments.last_page > 1" class="flex gap-2">
                <button v-if="assignments.prev_page_url" type="button" @click="paginate(assignments.current_page - 1)" class="rounded border px-3 py-1 hover:bg-gray-100">←</button>
                <button v-if="assignments.next_page_url" type="button" @click="paginate(assignments.current_page + 1)" class="rounded border px-3 py-1 hover:bg-gray-100">→</button>
            </div>
        </div>

        <div v-if="modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-base font-semibold text-gray-800">{{ modal.title }}</h3>

                <form v-if="modal.kind === 'single'" @submit.prevent="saveSingle" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">GTK</label>
                        <select v-model="form.employee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Pilih GTK</option>
                            <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.nigy }} — {{ employee.name }}</option>
                        </select>
                        <p v-if="form.errors.employee_id" class="mt-1 text-xs text-red-600">{{ form.errors.employee_id }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tugas Tambahan</label>
                            <select v-model="form.additional_duty_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Pilih Tugas</option>
                                <option v-for="duty in duties" :key="duty.id" :value="duty.id">{{ duty.name }}</option>
                            </select>
                            <p v-if="form.errors.additional_duty_id" class="mt-1 text-xs text-red-600">{{ form.errors.additional_duty_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Satuan Kerja</label>
                            <select v-model="form.work_unit_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Pilih Satker</option>
                                <option v-for="unit in workUnits" :key="unit.id" :value="unit.id">{{ unit.code }} — {{ unit.name }}</option>
                            </select>
                            <p v-if="form.errors.work_unit_id" class="mt-1 text-xs text-red-600">{{ form.errors.work_unit_id }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tahun Pelajaran</label>
                            <input v-model="form.academic_year" type="text" placeholder="2026/2027" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                            <input v-model="form.notes" type="text" placeholder="mis. Wali Kelas VII-A" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">TMT Mulai</label>
                            <input v-model="form.start_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">TMT Selesai</label>
                            <input v-model="form.end_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">{{ form.errors.end_date }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-emerald-700 px-4 py-2 text-sm text-white hover:bg-emerald-800 disabled:opacity-50">Simpan</button>
                    </div>
                </form>

                <form v-else-if="modal.kind === 'mass'" @submit.prevent="saveMass" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tugas Tambahan</label>
                            <select v-model="massForm.additional_duty_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Pilih Tugas</option>
                                <option v-for="duty in duties" :key="duty.id" :value="duty.id">{{ duty.name }}</option>
                            </select>
                            <p v-if="massForm.errors.additional_duty_id" class="mt-1 text-xs text-red-600">{{ massForm.errors.additional_duty_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Satuan Kerja</label>
                            <select v-model="massForm.work_unit_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Pilih Satker</option>
                                <option v-for="unit in workUnits" :key="unit.id" :value="unit.id">{{ unit.code }} — {{ unit.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tahun Pelajaran</label>
                            <input v-model="massForm.academic_year" type="text" placeholder="2026/2027" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                            <input v-model="massForm.notes" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">TMT Mulai</label>
                            <input v-model="massForm.start_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">TMT Selesai</label>
                            <input v-model="massForm.end_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pilih GTK ({{ massForm.employee_ids.length }} terpilih)</label>
                        <div class="mt-1 max-h-48 overflow-y-auto rounded-md border border-gray-200">
                            <label v-for="employee in employees" :key="employee.id" class="flex items-center gap-2 border-b border-gray-100 px-3 py-1.5 text-sm hover:bg-gray-50">
                                <input v-model="massForm.employee_ids" type="checkbox" :value="employee.id" class="rounded border-gray-300 text-emerald-600">
                                <span class="text-gray-700">{{ employee.nigy }} — {{ employee.name }}</span>
                            </label>
                        </div>
                        <p v-if="massForm.errors.employee_ids" class="mt-1 text-xs text-red-600">{{ massForm.errors.employee_ids }}</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="massForm.processing" class="rounded-md bg-emerald-700 px-4 py-2 text-sm text-white hover:bg-emerald-800 disabled:opacity-50">
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

const route = inject('route');

const props = defineProps(['assignments', 'filters', 'workUnits', 'academicYears', 'duties', 'employees']);

const filters = reactive({
    work_unit_id: props.filters.work_unit_id ?? '',
    academic_year: props.filters.academic_year ?? '',
    employee_id: props.filters.employee_id ?? '',
});

const modal = ref(null);

function formatDate(value) {
    return value ? String(value).slice(0, 10) : '—';
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
