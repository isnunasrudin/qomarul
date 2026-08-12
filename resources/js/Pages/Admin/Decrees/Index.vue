<template>
    <AdminLayout>
        <Head :title="'Surat Keputusan'" />

        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Surat Keputusan</h2>
            <Link :href="route('admin.decrees.create')"
                  class="btn-primary">
                Buat SK
            </Link>
        </div>

        <form class="card mb-4 grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6" @submit.prevent="applyFilters">
            <input v-model="filters.q" type="search" placeholder="Cari nomor SK, nama, NIGY..."
                   class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 lg:col-span-2">
            <select v-model="filters.work_unit_id" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">Semua Satker</option>
                <option v-for="unit in workUnits" :key="unit.id" :value="unit.id">{{ unit.code }} — {{ unit.name }}</option>
            </select>
            <select v-model="filters.decree_type_id" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">Semua Jenis</option>
                <option v-for="type in decreeTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
            </select>
            <select v-model="filters.status" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">Semua Status</option>
                <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 btn-primary">Cari</button>
                <button type="button" @click="resetFilters" class="btn-secondary">Reset</button>
            </div>
        </form>

        <div class="table-wrap">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Nomor SK</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">GTK</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Jenis</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Satker</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="decree in decrees.data" :key="decree.id">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ decree.decree_number || '—' }}</td>
                        <td class="px-4 py-3">
                            <p class="text-gray-700">{{ decree.employee?.name }}</p>
                            <p class="font-mono text-xs text-gray-400">{{ decree.employee?.nigy }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ decree.decree_type?.name }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ decree.work_unit?.code }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs" :class="statusClass(decree.status)">
                                {{ statusLabel(decree.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.decrees.show', decree.id)" class="text-primary-600 hover:underline">Buka</Link>
                        </td>
                    </tr>
                    <tr v-if="!decrees.data.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">Tidak ada SK</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
            <span>Halaman {{ decrees.current_page }} / {{ decrees.last_page }} · {{ decrees.total }} SK</span>
            <div v-if="decrees.last_page > 1" class="flex gap-2">
                <button v-if="decrees.prev_page_url" type="button" @click="paginate(decrees.current_page - 1)" class="btn-secondary px-3 py-1">←</button>
                <button v-if="decrees.next_page_url" type="button" @click="paginate(decrees.current_page + 1)" class="btn-secondary px-3 py-1">→</button>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { inject, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');

const props = defineProps(['decrees', 'filters', 'workUnits', 'decreeTypes', 'statuses']);

const filters = reactive({
    q: props.filters.q ?? '',
    work_unit_id: props.filters.work_unit_id ?? '',
    decree_type_id: props.filters.decree_type_id ?? '',
    status: props.filters.status ?? '',
    academic_year: props.filters.academic_year ?? '',
    is_legacy: props.filters.is_legacy ?? '',
});

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

function applyFilters() {
    router.get(route('admin.decrees.index'), filters, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    Object.keys(filters).forEach((k) => { filters[k] = ''; });
    router.get(route('admin.decrees.index'), {}, { preserveState: true, preserveScroll: true });
}

function paginate(page) {
    router.get(route('admin.decrees.index'), { ...filters, page }, { preserveState: true, preserveScroll: true });
}
</script>
