<template>
    <AdminLayout>
        <Head :title="'Batch — ' + batch.name" />

        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">{{ batch.name }}</h2>
                <p class="text-sm text-gray-500">
                    {{ batch.decree_type?.name }} · {{ batch.academic_year }} ·
                    <span class="rounded-full px-2 py-0.5 text-xs" :class="statusClass(batch.status)">{{ statusLabel(batch.status) }}</span>
                    <span class="ml-2 text-gray-400">{{ batch.succeeded }} berhasil · {{ batch.failed }} gagal · {{ batch.total }} total</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button v-if="can.process" type="button" @click="processBatch"
                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Proses & Beri Nomor
                </button>
                <button v-if="can.sign" type="button" @click="confirmSign"
                        class="btn-primary">
                    Tanda Tangani Seluruh Batch
                </button>
                <button v-if="can.cancel" type="button" @click="cancelBatch"
                        class="btn-danger">
                    Batalkan Batch
                </button>
                <a v-if="hasIssued" :href="route('admin.batches.download-zip', batch.id)" target="_blank" rel="noopener"
                   class="btn-secondary">
                    Unduh ZIP
                </a>
                <a v-if="hasIssued" :href="route('admin.batches.download-combined', batch.id)" target="_blank" rel="noopener"
                   class="btn-secondary">
                    PDF Gabungan
                </a>
            </div>
        </div>

        <div v-if="batch.status === 'processing' || batch.status === 'signing'" class="mb-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
            {{ batch.status === 'processing' ? 'Batch sedang diproses (validasi + alokasi nomor).' : 'Batch sedang ditandatangani.' }}
            Halaman akan dimuat ulang otomatis.
        </div>

        <div class="table-wrap">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">GTK</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Satker</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Nomor SK</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="decree in batch.decrees" :key="decree.id">
                        <td class="px-4 py-3">
                            <p class="text-gray-700">{{ decree.employee?.name }}</p>
                            <p class="font-mono text-xs text-gray-400">{{ decree.employee?.nigy }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ decree.employee?.work_unit?.code }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ decree.decree_number || '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs" :class="statusClass(decree.status)">
                                {{ statusLabel(decree.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ decree.rejection_reason || '—' }}</td>
                    </tr>
                    <tr v-if="!batch.decrees.length">
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">Belum ada SK dalam batch</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');

const props = defineProps(['batch', 'can']);

const hasIssued = computed(() => props.batch.decrees.some((d) => d.status === 'issued'));

const statusLabels = {
    preparing: 'Disiapkan', processing: 'Diproses', awaiting_signature: 'Menunggu Tanda Tangan',
    signing: 'Ditandatangani', completed: 'Selesai', cancelled: 'Dibatalkan',
    draft: 'Draft', submitted: 'Diajukan', rejected: 'Ditolak',
    verified: 'Terverifikasi', issued: 'Diterbitkan',
};

function statusLabel(value) {
    return statusLabels[value] ?? value;
}

const classes = {
    preparing: 'bg-gray-100 text-gray-600',
    processing: 'bg-blue-50 text-blue-700',
    awaiting_signature: 'bg-amber-50 text-amber-700',
    signing: 'bg-purple-50 text-purple-700',
    completed: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-gray-200 text-gray-600',
    draft: 'bg-gray-100 text-gray-600',
    submitted: 'bg-blue-50 text-blue-700',
    rejected: 'bg-red-50 text-red-600',
    verified: 'bg-amber-50 text-amber-700',
    issued: 'bg-emerald-100 text-emerald-700',
};

function statusClass(value) {
    return classes[value] ?? 'bg-gray-100 text-gray-600';
}

function processBatch() {
    if (confirm('Proses batch ini? Draft SK akan divalidasi dan diberi nomor. GTK bermasalah ditandai gagal.')) {
        router.post(route('admin.batches.process', props.batch.id));
    }
}

function confirmSign() {
    if (confirm('Tanda tangani seluruh SK terverifikasi dalam batch ini sekali klik?')) {
        router.post(route('admin.batches.sign', props.batch.id));
    }
}

function cancelBatch() {
    if (confirm('Batalkan batch ini? Seluruh SK draft/terverifikasi akan ditandai batal.')) {
        router.post(route('admin.batches.cancel', props.batch.id));
    }
}

let timer = null;
onMounted(() => {
    if (props.batch.status === 'processing' || props.batch.status === 'signing') {
        timer = setInterval(() => {
            router.reload({ only: ['batch'], preserveScroll: true });
        }, 3000);
    }
});
onMounted(() => {
    // hentikan polling saat selesai
    timer && props.batch.status !== 'processing' && props.batch.status !== 'signing' && clearInterval(timer);
});
</script>
