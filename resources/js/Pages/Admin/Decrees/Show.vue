<template>
    <AdminLayout>
        <Head :title="'Detail SK'" />

        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">
                    {{ decree.decree_number || 'Draft SK' }}
                </h2>
                <p class="text-sm text-gray-500">
                    {{ decree.employee?.name }} · {{ decree.decree_type?.name }} ·
                    <span class="rounded-full px-2 py-0.5 text-xs" :class="statusClass(decree.status)">{{ statusLabel(decree.status) }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a :href="route('admin.decrees.preview-pdf', decree.id)" target="_blank"
                   class="btn-secondary">
                    Pratinjau PDF
                </a>
                <a v-if="downloadUrl" :href="downloadUrl" target="_blank" rel="noopener"
                   class="btn-primary">
                    Unduh PDF
                </a>
                <button v-if="can.submit" type="button" @click="action('submit')"
                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Ajukan
                </button>
                <button v-if="can.verify" type="button" @click="action('verify')"
                        class="rounded-md bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700">
                    Verifikasi & Beri Nomor
                </button>
                <button v-if="can.reject" type="button" @click="openReject"
                        class="btn-danger">
                    Tolak
                </button>
                <button v-if="can.sign" type="button" @click="confirmIssue"
                        class="btn-primary">
                    Setujui & Tanda Tangani
                </button>
                <button v-if="can.cancel" type="button" @click="openCancel"
                        class="btn-danger">
                    Batalkan
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="card p-5 lg:col-span-2">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Isi SK</h3>
                <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-gray-400">Nama</p>
                        <p class="text-sm text-gray-800">{{ decree.snapshot_data?.name ?? decree.employee?.name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">NIGY</p>
                        <p class="text-sm font-mono text-gray-800">{{ decree.snapshot_data?.nigy ?? decree.employee?.nigy }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Jabatan Tercetak</p>
                        <p class="text-sm text-gray-800">{{ decree.position_snapshot || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Diangkat sebagai</p>
                        <p class="text-sm text-gray-800">{{ decree.appointed_as || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">TMT</p>
                        <p class="text-sm text-gray-800">{{ decree.effective_date || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Tanggal Penetapan</p>
                        <p class="text-sm text-gray-800">{{ decree.issued_date || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Tahun Pelajaran</p>
                        <p class="text-sm text-gray-800">{{ decree.academic_year || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Registrasi</p>
                        <p class="text-sm text-gray-800">{{ decree.registration_number || '—' }}</p>
                    </div>
                </div>
                <p v-if="decree.rejection_reason" class="mt-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                    Alasan penolakan: {{ decree.rejection_reason }}
                </p>
                <p v-if="decree.cancellation_reason" class="mt-4 rounded-md bg-gray-100 px-3 py-2 text-sm text-gray-700">
                    Alasan pembatalan: {{ decree.cancellation_reason }}
                </p>
                <p v-if="decree.replacement" class="mt-4 rounded-md bg-purple-50 px-3 py-2 text-sm text-purple-700">
                    Digantikan oleh: {{ decree.replacement.decree_number }}
                </p>
            </div>

            <div class="card p-5">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Riwayat Alur</h3>
                <ol class="space-y-3">
                    <li v-for="log in decree.workflow_logs" :key="log.id" class="border-l-2 border-emerald-200 pl-3">
                        <p class="text-sm text-gray-800">
                            <span class="font-medium">{{ statusLabel(log.to_status) }}</span>
                            <span v-if="log.from_status" class="text-gray-400">(dari {{ statusLabel(log.from_status) }})</span>
                        </p>
                        <p class="text-xs text-gray-400">{{ log.user?.name }} · {{ log.created_at }}</p>
                        <p v-if="log.notes" class="text-xs text-gray-500">{{ log.notes }}</p>
                    </li>
                    <li v-if="!decree.workflow_logs.length" class="text-sm text-gray-400">Belum ada transisi.</li>
                </ol>
            </div>
        </div>

        <div v-if="modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
            <div class="w-full max-w-md card p-6">
                <h3 class="mb-2 text-base font-semibold text-gray-800">{{ modal.title }}</h3>
                <p class="mb-3 text-sm text-gray-500">{{ modal.hint }}</p>
                <form @submit.prevent="submitModal" class="space-y-4">
                    <textarea v-model="modal.notes" rows="3" required placeholder="Alasan (wajib)"
                              class="input"></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="modal = null" class="btn-secondary">Batal</button>
                        <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { inject, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');

const props = defineProps(['decree', 'can', 'downloadUrl', 'statuses']);

const modal = ref(null);

const labels = Object.fromEntries((props.statuses ?? []).map((s) => [s.value, s.label]));

function statusLabel(value) {
    return labels[value] ?? value;
}

const classes = {
    draft: 'bg-gray-100 text-gray-600',
    submitted: 'bg-blue-50 text-blue-700',
    rejected: 'bg-red-50 text-red-600',
    verified: 'bg-amber-50 text-amber-700',
    issued: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-gray-200 text-gray-600',
    superseded: 'bg-purple-50 text-purple-700',
};

function statusClass(value) {
    return classes[value] ?? 'bg-gray-100 text-gray-600';
}

const form = useForm({});

function action(name) {
    router.post(route(`admin.decrees.${name}`, props.decree.id));
}

function confirmIssue() {
    if (confirm('Setujui dan tandatangani SK ini? Data akan dibekukan dan PDF final dibuat.')) {
        action('issue');
    }
}

function openReject() {
    modal.value = { kind: 'reject', title: 'Tolak SK', hint: 'Penolakan wajib menyertakan alasan.', notes: '' };
}

function openCancel() {
    modal.value = { kind: 'cancel', title: 'Batalkan SK', hint: 'Pembatalan wajib menyertakan alasan.', notes: '' };
}

function submitModal() {
    router.post(route(`admin.decrees.${modal.value.kind}`, props.decree.id), { notes: modal.value.notes });
}
</script>
