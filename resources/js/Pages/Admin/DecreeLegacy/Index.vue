<template>
    <AdminLayout>
        <Head :title="'Verifikasi Arsip SK'" />

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Verifikasi Arsip SK Lama</h2>
            <p class="text-sm text-gray-500">
                Arsip yang diunggah GTK belum diakui sebagai riwayat resmi sebelum diverifikasi.
            </p>
        </div>

        <div class="table-wrap">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">GTK</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Nomor SK</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Diunggah</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="decree in decrees.data" :key="decree.id">
                        <td class="px-4 py-3">
                            <p class="text-gray-700">{{ decree.employee?.name }}</p>
                            <p class="text-xs text-gray-400 font-mono">{{ decree.employee?.nigy }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ decree.decree_number || '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ formatTanggalWaktu(decree.created_at) }}</td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <button type="button" @click="verify(decree)"
                                    class="rounded-md bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-700">
                                Verifikasi
                            </button>
                            <button type="button" @click="reject(decree)"
                                    class="btn-danger px-3 py-1.5 text-xs">
                                Tolak
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!decrees.data.length">
                        <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-400">Tidak ada arsip menunggu verifikasi</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
            <span>Halaman {{ decrees.current_page }} / {{ decrees.last_page }} · {{ decrees.total }} arsip</span>
            <div v-if="decrees.last_page > 1" class="flex gap-2">
                <button v-if="decrees.prev_page_url" type="button" @click="paginate(decrees.current_page - 1)" class="btn-secondary px-3 py-1">←</button>
                <button v-if="decrees.next_page_url" type="button" @click="paginate(decrees.current_page + 1)" class="btn-secondary px-3 py-1">→</button>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { inject } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { formatTanggalWaktu } from '../../../utils/date';

const route = inject('route');

defineProps(['decrees']);

function verify(decree) {
    if (confirm(`Verifikasi arsip SK ${decree.employee?.name}? Arsip akan masuk riwayat resmi.`)) {
        router.post(route('admin.decree-legacy.verify', decree.id));
    }
}

function reject(decree) {
    if (confirm(`Tolak dan hapus arsip SK ${decree.employee?.name}?`)) {
        router.delete(route('admin.decree-legacy.destroy', decree.id));
    }
}

function paginate(page) {
    router.get(route('admin.decree-legacy.index'), { page }, { preserveState: true, preserveScroll: true });
}
</script>
