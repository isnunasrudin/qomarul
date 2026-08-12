<template>
    <AdminLayout>
        <Head :title="'Buat SK'" />

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Buat Surat Keputusan</h2>
            <p class="text-sm text-gray-500">Field otomatis terisi dari data GTK dan dapat ditimpa manual.</p>
        </div>

        <form @submit.prevent="submit" class="max-w-2xl space-y-4 rounded-lg bg-white p-6 shadow-sm">
            <div>
                <label class="block text-sm font-medium text-gray-700">GTK</label>
                <select v-model="form.employee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Pilih GTK</option>
                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.nigy }} — {{ employee.name }}</option>
                </select>
                <p v-if="form.errors.employee_id" class="mt-1 text-xs text-red-600">{{ form.errors.employee_id }}</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Jenis SK</label>
                    <select v-model="form.decree_type_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Pilih Jenis</option>
                        <option v-for="type in decreeTypes" :key="type.id" :value="type.id">{{ type.code }} — {{ type.name }}</option>
                    </select>
                    <p v-if="form.errors.decree_type_id" class="mt-1 text-xs text-red-600">{{ form.errors.decree_type_id }}</p>
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
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tahun Pelajaran</label>
                    <input v-model="form.academic_year" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tanggal Penetapan</label>
                    <input v-model="form.issued_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">TMT (Terhitung Mulai Tanggal)</label>
                    <input v-model="form.effective_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Ditetapkan Di</label>
                    <input v-model="form.issued_place" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Diangkat Kembali Sebagai</label>
                <input v-model="form.appointed_as" type="text" placeholder="otomatis dari jabatan GTK"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Jabatan yang Tercetak (timpa manual bila beda dari jabatan saat ini)</label>
                <input v-model="form.position_snapshot" type="text" placeholder="otomatis dari jabatan GTK"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="back" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Kembali</button>
                <button type="submit" :disabled="form.processing" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50">
                    Simpan Draft SK
                </button>
            </div>
        </form>
    </AdminLayout>
</template>

<script setup>
import { inject, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');

const props = defineProps(['employee', 'decreeTypes', 'workUnits', 'employees']);

const form = useForm({
    employee_id: props.employee?.id ?? '',
    decree_type_id: '',
    work_unit_id: props.employee?.work_unit_id ?? '',
    academic_year: `${new Date().getFullYear()}/${new Date().getFullYear() + 1}`,
    issued_date: new Date().toISOString().slice(0, 10),
    effective_date: '',
    issued_place: '',
    appointed_as: props.employee?.position?.name ?? '',
    position_snapshot: props.employee?.position?.name ?? '',
});

// pilih GTK → isi satker & jabatan otomatis
watch(() => form.employee_id, (id) => {
    const employee = (props.employees ?? []).find((e) => e.id === id);

    if (employee) {
        form.work_unit_id = employee.work_unit_id ?? '';
        form.appointed_as = employee.position?.name ?? '';
        form.position_snapshot = employee.position?.name ?? '';
    }
});

function submit() {
    form.post(route('admin.decrees.store'));
}

function back() {
    window.history.length > 1 ? window.history.back() : router.get(route('admin.decrees.index'));
}
</script>
