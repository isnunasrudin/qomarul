<template>
    <AdminLayout>
        <Head :title="'Audit Log'" />

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-foreground">Audit Log</h2>
                <p class="text-sm text-slate-500">Append-only — tidak dapat diubah atau dihapus dari antarmuka.</p>
            </div>
            <a :href="route('admin.audit-logs.export', filters)" target="_blank" rel="noopener" class="btn-secondary">
                <FileSpreadsheet :size="15" /> Ekspor Excel
            </a>
        </div>

        <form class="card mb-4 grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6" @submit.prevent="applyFilters">
            <select v-model="filters.user_id" class="input">
                <option value="">Semua Pengguna</option>
                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
            </select>
            <select v-model="filters.action" class="input">
                <option value="">Semua Aksi</option>
                <option v-for="action in actions" :key="action" :value="action">{{ action }}</option>
            </select>
            <select v-model="filters.auditable_type" class="input">
                <option value="">Semua Entitas</option>
                <option v-for="type in entityTypes" :key="type" :value="type">{{ type }}</option>
            </select>
            <input v-model="filters.from" type="date" class="input">
            <input v-model="filters.to" type="date" class="input">
            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1">Cari</button>
                <button type="button" @click="resetFilters" class="btn-secondary">Reset</button>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>Entitas</th>
                        <th>Perubahan</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs.data" :key="log.id">
                        <td class="whitespace-nowrap text-slate-500">{{ formatTanggalWaktu(log.created_at) }}</td>
                        <td class="text-slate-700">{{ log.user?.name ?? 'Sistem' }}</td>
                        <td><span class="badge bg-primary-50 text-primary-600">{{ log.action }}</span></td>
                        <td class="text-xs text-slate-500">{{ shortType(log.auditable_type) }} #{{ log.auditable_id }}</td>
                        <td class="max-w-xs">
                            <p v-if="log.old_values" class="truncate text-xs text-slate-500" :title="JSON.stringify(log.old_values)">
                                <span class="text-red-600">−</span> {{ summarize(log.old_values) }}
                            </p>
                            <p v-if="log.new_values" class="truncate text-xs text-slate-600" :title="JSON.stringify(log.new_values)">
                                <span class="text-emerald-600">+</span> {{ summarize(log.new_values) }}
                            </p>
                        </td>
                        <td class="font-mono text-xs text-slate-400">{{ log.ip || '—' }}</td>
                    </tr>
                    <tr v-if="!logs.data.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">Tidak ada log</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm text-slate-500">
            <span>Halaman {{ logs.current_page }} / {{ logs.last_page }}</span>
            <div v-if="logs.last_page > 1" class="flex gap-2">
                <button v-if="logs.prev_page_url" type="button" @click="paginate(logs.current_page - 1)" class="btn-secondary px-3 py-1">←</button>
                <button v-if="logs.next_page_url" type="button" @click="paginate(logs.current_page + 1)" class="btn-secondary px-3 py-1">→</button>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { inject, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { FileSpreadsheet } from 'lucide-vue-next';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { formatTanggalWaktu } from '../../../utils/date';

const route = inject('route');

const props = defineProps(['logs', 'filters', 'users', 'actions', 'entityTypes']);

const filters = reactive({
    user_id: props.filters.user_id ?? '',
    action: props.filters.action ?? '',
    auditable_type: props.filters.auditable_type ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function shortType(type) {
    return type ? type.split('\\').pop() : '—';
}

function summarize(values) {
    const entries = Object.entries(values ?? {}).slice(0, 2);
    return entries.map(([key, value]) => `${key}: ${String(value).slice(0, 40)}`).join(', ') + (Object.keys(values ?? {}).length > 2 ? ' …' : '');
}

function applyFilters() {
    router.get(route('admin.audit-logs.index'), filters, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    Object.keys(filters).forEach((k) => { filters[k] = ''; });
    router.get(route('admin.audit-logs.index'), {}, { preserveState: true, preserveScroll: true });
}

function paginate(page) {
    router.get(route('admin.audit-logs.index'), { ...filters, page }, { preserveState: true, preserveScroll: true });
}
</script>
