<template>
    <AdminLayout>
        <Head :title="t('common.dashboard')" />

        <div v-if="employee" class="mb-6 rounded-lg bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-gray-800">{{ employee.name }}</h2>
            <p class="mt-1 text-sm text-gray-500">
                NIGY {{ employee.nigy }} · {{ employee.work_unit }} · {{ employee.position }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="card in cards" :key="card.label" class="rounded-lg bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">{{ card.label }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-800">{{ card.value }}</p>
            </div>
        </div>

        <div v-if="employeesByUnit.length" class="mt-6 rounded-lg bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold text-gray-700">GTK Aktif per Satuan Kerja</h3>
            <div class="space-y-3">
                <div v-for="row in employeesByUnit" :key="row.label" class="flex items-center gap-3">
                    <span class="w-56 truncate text-sm text-gray-600">{{ row.label }}</span>
                    <div class="h-3 flex-1 rounded bg-gray-100">
                        <div class="h-3 rounded bg-emerald-500"
                             :style="{ width: `${maxTotal ? (row.total / maxTotal) * 100 : 0}%` }"></div>
                    </div>
                    <span class="w-10 text-right text-sm font-semibold">{{ row.total }}</span>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../Layouts/AdminLayout.vue';
import { useTranslation } from '../helpers/translation';

const { t } = useTranslation();

const props = defineProps({
    employee: Object,
    totalEmployees: Number,
    activeEmployees: Number,
    workUnitCount: Number,
    pendingVerification: Number,
    pendingSignature: Number,
    employeesByUnit: Array,
});

const cards = computed(() => {
    const items = [];

    if (props.employee) {
        return [];
    }

    items.push({ label: 'Total GTK', value: props.totalEmployees ?? 0 });
    items.push({ label: 'GTK Aktif', value: props.activeEmployees ?? 0 });
    items.push({ label: 'Satuan Kerja Aktif', value: props.workUnitCount ?? 0 });

    if (props.pendingVerification !== undefined) {
        items.push({ label: 'Menunggu Verifikasi', value: props.pendingVerification ?? 0 });
    }
    if (props.pendingSignature !== undefined) {
        items.push({ label: 'Menunggu Tanda Tangan', value: props.pendingSignature ?? 0 });
    }

    return items;
});

const maxTotal = computed(() => Math.max(1, ...(props.employeesByUnit ?? []).map((row) => row.total)));
</script>
