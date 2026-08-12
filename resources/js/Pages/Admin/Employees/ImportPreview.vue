<template>
    <AdminLayout>
        <Head :title="'Pratinjau Impor GTK'" />

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Pratinjau Impor GTK</h2>
            <p class="text-sm text-gray-500">
                {{ preview.valid.length }} baris valid · {{ Object.keys(preview.errors).length }} baris bermasalah.
                Tidak ada data yang tersimpan sebelum Anda mengonfirmasi.
            </p>
        </div>

        <div v-if="Object.keys(preview.errors).length" class="mb-4 rounded-lg bg-red-50 p-5">
            <h3 class="mb-2 text-sm font-semibold text-red-700">Baris Bermasalah (tidak akan diimpor)</h3>
            <div v-for="(messages, line) in preview.errors" :key="line" class="mb-2">
                <p class="text-xs font-medium text-red-600">Baris {{ line }}:</p>
                <ul class="ml-4 list-disc text-xs text-red-500">
                    <li v-for="message in messages" :key="message">{{ message }}</li>
                </ul>
            </div>
        </div>

        <div class="rounded-lg bg-white p-5 shadow-sm">
            <h3 class="mb-2 text-sm font-semibold text-gray-700">Ringkasan Data Valid</h3>
            <p class="mb-4 text-sm text-gray-600">
                NIGY akan dihasilkan otomatis sesuai format pada Pengaturan Yayasan.
            </p>
            <div class="max-h-72 overflow-y-auto rounded-md border border-gray-100">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="sticky top-0 bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Nama</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Satker</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Jabatan</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">TMT Yayasan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in preview.valid" :key="row.name + row.foundation_start_date">
                            <td class="px-3 py-2 text-gray-700">{{ row.name }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ row.work_unit_id }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ row.position_id }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ row.foundation_start_date }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 flex justify-between">
            <Link :href="route('admin.employees.index')" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                Kembali
            </Link>
            <div v-if="preview.valid.length" class="flex gap-2">
                <button type="button" @click="router.get(route('admin.employees.index'))"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    Batal
                </button>
                <button type="button" :disabled="importing"
                        class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50"
                        @click="confirmImport">
                    Simpan {{ preview.valid.length }} GTK
                </button>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { inject, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');

defineProps(['preview']);

const importing = ref(false);

function confirmImport() {
    if (!confirm('Simpan seluruh baris valid ke sistem?')) {
        return;
    }

    importing.value = true;
    router.post(route('admin.employees.import'));
}
</script>
