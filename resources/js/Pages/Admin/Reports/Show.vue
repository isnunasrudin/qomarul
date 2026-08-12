<template>
    <AdminLayout>
        <Head :title="title" />

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-foreground">{{ title }}</h2>
                <p class="text-sm text-slate-500">{{ rows.total }} baris</p>
            </div>
            <div v-if="exportUrl" class="flex gap-2">
                <a :href="exportUrl" class="btn-secondary">
                    <FileSpreadsheet :size="15" /> Excel
                </a>
                <a :href="exportPdfUrl" target="_blank" rel="noopener" class="btn-secondary">
                    <FileText :size="15" /> PDF
                </a>
            </div>
        </div>

        <div v-if="summary && summary.length" class="card mb-4 grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 md:grid-cols-4">
            <div v-for="row in summary" :key="row.label" class="rounded-lg bg-muted/60 px-3 py-2.5">
                <p class="text-xs text-slate-500">{{ row.label }}</p>
                <p class="mt-0.5 text-lg font-semibold text-foreground">{{ row.total }}</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th v-for="column in columns" :key="column.key">{{ column.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in rows.data" :key="index">
                        <td v-for="column in columns" :key="column.key">
                            <span v-if="column.key === 'percentage'" class="font-semibold" :class="row.percentage >= 100 ? 'text-emerald-600' : 'text-amber-600'">
                                {{ row.percentage }}%
                            </span>
                            <span v-else-if="column.key === 'is_active'">
                                <span :class="row.is_active ? 'badge bg-emerald-100 text-emerald-700' : 'badge bg-slate-100 text-slate-500'">
                                    {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </span>
                            <span v-else class="text-slate-700">{{ formatValue(row, column.key) }}</span>
                        </td>
                    </tr>
                    <tr v-if="!rows.data.length">
                        <td :colspan="columns.length" class="px-4 py-10 text-center text-sm text-slate-400">Tidak ada data</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm text-slate-500">
            <span>Halaman {{ rows.current_page }} / {{ rows.last_page }}</span>
            <div v-if="rows.last_page > 1" class="flex gap-2">
                <button v-if="rows.prev_page_url" type="button" @click="paginate(rows.current_page - 1)" class="btn-secondary px-3 py-1">←</button>
                <button v-if="rows.next_page_url" type="button" @click="paginate(rows.current_page + 1)" class="btn-secondary px-3 py-1">→</button>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, inject } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { FileSpreadsheet, FileText } from 'lucide-vue-next';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');

const props = defineProps(['title', 'columns', 'rows', 'summary', 'filters', 'exportQuery', 'reportKey']);

const exportUrl = computed(() => (props.exportQuery !== undefined && props.reportKey)
    ? route(`admin.reports.export`, { report: props.reportKey, ...(props.exportQuery ? JSON.parse(atob(props.exportQuery)) : {}) })
    : null);

const exportPdfUrl = computed(() => (props.reportKey
    ? route(`admin.reports.export-pdf`, { report: props.reportKey, ...(props.exportQuery ? JSON.parse(atob(props.exportQuery)) : {}) })
    : null));

function formatValue(row, key) {
    const value = key.split('.').reduce((acc, part) => acc?.[part], row);
    if (value === null || value === undefined || value === '') return '—';
    if (key.includes('date') && typeof value === 'string') return value.slice(0, 10);
    return value;
}

function paginate(page) {
    router.get(window.location.pathname, { ...props.filters, page }, { preserveState: true, preserveScroll: true });
}
</script>
