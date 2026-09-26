<template>
    <AdminLayout>
        <Head :title="isEdit ? 'Ubah SK' : 'Buat SK'" />

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-800">{{ isEdit ? 'Ubah Draft SK' : 'Buat Surat Keputusan' }}</h2>
            <p class="text-sm text-gray-500">Field otomatis terisi dari data GTK dan dapat ditimpa manual.</p>
        </div>

        <form @submit.prevent="submit" class="card max-w-2xl space-y-6 p-6">
            <div>
                <label class="label">GTK</label>
                <select v-model="form.employee_id" class="input">
                    <option value="">Pilih GTK</option>
                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.nigy }} — {{ employee.name }}</option>
                </select>
                <p v-if="form.errors.employee_id" class="error-text" role="alert">{{ form.errors.employee_id }}</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Jenis SK</label>
                    <select v-model="form.decree_type_id" class="input">
                        <option value="">Pilih Jenis</option>
                        <option v-for="type in decreeTypes" :key="type.id" :value="type.id">{{ type.code }} — {{ type.name }}</option>
                    </select>
                    <p v-if="form.errors.decree_type_id" class="error-text" role="alert">{{ form.errors.decree_type_id }}</p>
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
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Tahun Pelajaran</label>
                    <input v-model="form.academic_year" type="text" class="input">
                </div>
                <div>
                    <label class="label">Tanggal Penetapan</label>
                    <input v-model="form.issued_date" type="date" class="input">
                </div>
            </div>
            <div>
                <label class="label">Tanggal Hijriah (tercetak di SK)</label>
                <input v-model="form.issued_date_hijri" type="text" class="input"
                       placeholder="25 Muharam 1448 H">
                <p class="mt-1 text-xs text-gray-500">
                    Terisi otomatis dari kalender MABIMS (Kemenag RI) mengikuti Tanggal Penetapan.
                    <template v-if="hijriLoading"> Menghitung…</template>
                    Koreksi bila sidang isbat menetapkan tanggal yang berbeda.
                </p>
                <p v-if="form.errors.issued_date_hijri" class="error-text" role="alert">{{ form.errors.issued_date_hijri }}</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">TMT (Terhitung Mulai Tanggal)</label>
                    <input v-model="form.effective_date" type="date" class="input">
                </div>
                <div>
                    <label class="label">Ditetapkan Di</label>
                    <input v-model="form.issued_place" type="text" class="input">
                </div>
            </div>
            <div>
                <label class="label">Diangkat Kembali Sebagai</label>
                <input v-model="form.appointed_as" type="text" placeholder="otomatis dari jabatan GTK"
                       class="input">
            </div>
            <div>
                <label class="label">Jabatan yang Tercetak (timpa manual bila beda dari jabatan saat ini)</label>
                <input v-model="form.position_snapshot" type="text" placeholder="otomatis dari jabatan GTK"
                       class="input">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button v-if="isEdit" type="button" @click="destroy" class="btn-danger mr-auto">Hapus Draft</button>
                <button type="button" @click="back" class="btn-secondary">Kembali</button>
                <button type="submit" :disabled="form.processing" class="btn-primary disabled:opacity-50">
                    {{ isEdit ? 'Simpan Perubahan' : 'Simpan Draft SK' }}
                </button>
            </div>
        </form>
    </AdminLayout>
</template>

<script setup>
import { computed, inject, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { hijriFromGregorian } from '../../../helpers/hijri';

const route = inject('route');

const props = defineProps(['employee', 'decree', 'decreeTypes', 'workUnits', 'employees']);

const isEdit = computed(() => Boolean(props.decree?.id));

const form = useForm({
    employee_id: props.decree?.employee_id ?? props.employee?.id ?? '',
    decree_type_id: props.decree?.decree_type_id ?? '',
    work_unit_id: props.decree?.work_unit_id ?? props.employee?.work_unit_id ?? '',
    academic_year: props.decree?.academic_year
        ?? `${new Date().getFullYear()}/${new Date().getFullYear() + 1}`,
    issued_date: props.decree?.issued_date ?? new Date().toISOString().slice(0, 10),
    issued_date_hijri: props.decree?.issued_date_hijri ?? '',
    effective_date: props.decree?.effective_date ?? '',
    issued_place: props.decree?.issued_place ?? '',
    appointed_as: props.decree?.appointed_as ?? props.employee?.position?.name ?? '',
    position_snapshot: props.decree?.position_snapshot ?? props.employee?.position?.name ?? '',
});

const hijriLoading = ref(false);

// Tanggal penetapan berubah → usulkan tanggal Hijriah dari kalender MABIMS.
// Operator tetap bebas menimpanya. Saat menyunting, nilai tersimpan tidak
// ditimpa otomatis — hanya dihitung ulang bila tanggalnya memang diubah.
watch(() => form.issued_date, async (date) => {
    hijriLoading.value = true;

    try {
        form.issued_date_hijri = await hijriFromGregorian(date);
    } finally {
        hijriLoading.value = false;
    }
}, { immediate: !isEdit.value });

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
    if (isEdit.value) {
        form.put(route('admin.decrees.update', props.decree.id));

        return;
    }

    form.post(route('admin.decrees.store'));
}

function destroy() {
    if (! window.confirm('Hapus draft SK ini? Tindakan ini tidak dapat dibatalkan.')) {
        return;
    }

    router.delete(route('admin.decrees.destroy', props.decree.id));
}

function back() {
    window.history.length > 1 ? window.history.back() : router.get(route('admin.decrees.index'));
}
</script>
