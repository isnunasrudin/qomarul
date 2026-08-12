<template>
    <AdminLayout>
        <Head :title="'Batch SK'" />

        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Batch Generate SK</h2>
            <Link :href="route('admin.batches.create')"
                  class="btn-primary">
                Batch Baru
            </Link>
        </div>

        <div class="table-wrap">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Nama</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Jenis</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">TP</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Hasil</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="batch in batches.data" :key="batch.id">
                        <td class="px-4 py-3 text-gray-700">{{ batch.name }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ batch.decree_type?.name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ batch.academic_year }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ batch.succeeded }} berhasil
                            <span v-if="batch.failed" class="text-red-600">/ {{ batch.failed }} gagal</span>
                            <span class="text-gray-400">dari {{ batch.total }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs" :class="statusClass(batch.status)">
                                {{ statusLabel(batch.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.batches.show', batch.id)" class="text-primary-600 hover:underline">Buka</Link>
                        </td>
                    </tr>
                    <tr v-if="!batches.data.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">Belum ada batch</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

<script setup>
import { inject } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');

defineProps(['batches']);

const labels = {
    preparing: 'Disiapkan',
    processing: 'Diproses',
    awaiting_signature: 'Menunggu Tanda Tangan',
    signing: 'Ditandatangani',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
};

function statusLabel(value) {
    return labels[value] ?? value;
}

const classes = {
    preparing: 'bg-gray-100 text-gray-600',
    processing: 'bg-blue-50 text-blue-700',
    awaiting_signature: 'bg-amber-50 text-amber-700',
    signing: 'bg-purple-50 text-purple-700',
    completed: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-gray-200 text-gray-600',
};

function statusClass(value) {
    return classes[value] ?? 'bg-gray-100 text-gray-600';
}
</script>
