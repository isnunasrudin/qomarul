<template>
    <AdminLayout>
        <Head :title="'Notifikasi'" />

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-foreground">Notifikasi</h2>
                <p class="text-sm text-slate-500">{{ notifications.total }} notifikasi</p>
            </div>
            <button v-if="notifications.data.some((n) => !n.read_at)" type="button" @click="markAllRead" class="btn-secondary">
                Tandai Semua Dibaca
            </button>
        </div>

        <div class="card divide-y divide-gray-100">
            <div v-for="notification in notifications.data" :key="notification.id"
                 class="flex items-start justify-between gap-3 px-5 py-4"
                 :class="notification.read_at ? 'bg-surface' : 'bg-primary-50/50'">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-600">
                        <component :is="iconFor(notification.data)" :size="15" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-foreground">{{ messageFor(notification.data) }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ formatTanggalWaktu(notification.created_at) }}</p>
                    </div>
                </div>
                <button v-if="!notification.read_at" type="button" @click="markRead(notification)"
                        class="shrink-0 text-xs text-primary-600 hover:underline">
                    Tandai dibaca
                </button>
            </div>
            <div v-if="!notifications.data.length" class="px-5 py-12 text-center text-sm text-slate-400">
                Tidak ada notifikasi
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm text-slate-500">
            <span>Halaman {{ notifications.current_page }} / {{ notifications.last_page }}</span>
            <div v-if="notifications.last_page > 1" class="flex gap-2">
                <button v-if="notifications.prev_page_url" type="button" @click="paginate(notifications.current_page - 1)" class="btn-secondary px-3 py-1">←</button>
                <button v-if="notifications.next_page_url" type="button" @click="paginate(notifications.current_page + 1)" class="btn-secondary px-3 py-1">→</button>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { inject } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { FileCheck2, Send, XCircle } from 'lucide-vue-next';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { formatTanggalWaktu } from '../../../utils/date';

const route = inject('route');

defineProps(['notifications']);

const labels = {
    submitted: 'SK diajukan',
    verified: 'SK diverifikasi & bernomor',
    rejected: 'SK ditolak',
    issued: 'SK diterbitkan',
};

function iconFor(data) {
    if (data?.to_status === 'rejected') return XCircle;
    if (data?.to_status === 'issued') return FileCheck2;
    return Send;
}

function messageFor(data) {
    const employee = data?.employee_name ?? '';
    const status = labels[data?.to_status] ?? data?.to_status;
    const number = data?.decree_number ? ` (${data.decree_number})` : '';

    if (data?.to_status === 'issued' && !employee) {
        return 'SK Anda telah diterbitkan.';
    }

    return `${status}${number} — ${employee}`.trim();
}

function markAllRead() {
    router.post(route('admin.notifications.read-all'));
}

function markRead(notification) {
    router.post(route('admin.notifications.read', notification.id));
}

function paginate(page) {
    router.get(route('admin.notifications.index'), { page }, { preserveState: true, preserveScroll: true });
}
</script>
