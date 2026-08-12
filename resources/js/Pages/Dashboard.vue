<template>
    <AdminLayout>
        <Head :title="t('common.dashboard')" />

        <div v-if="employee" class="card mb-6 flex flex-wrap items-center justify-between gap-4 p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600/10 text-primary-600">
                    <User :size="24" />
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-foreground">{{ employee.name }}</h2>
                    <p class="text-sm text-slate-500">
                        NIGY <span class="font-mono">{{ employee.nigy }}</span> · {{ employee.work_unit }} · {{ employee.position }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="employee" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div v-if="completeness" class="card p-6">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-foreground">Kelengkapan Profil</span>
                    <span class="font-semibold text-primary-600">{{ completeness.percentage }}%</span>
                </div>
                <div class="mt-2 h-3 rounded-full bg-muted">
                    <div class="h-3 rounded-full bg-primary-500 transition-all" :style="{ width: `${completeness.percentage}%` }"></div>
                </div>
                <p v-if="completeness.missing.length" class="mt-2 text-xs text-slate-500">
                    Kurang: {{ completeness.missing.join(', ') }}
                </p>
            </div>

            <div v-if="recentDecree" class="card p-6">
                <h3 class="mb-3 text-sm font-semibold text-foreground">SK Terbaru</h3>
                <ul class="divide-y divide-gray-100">
                    <li v-for="decree in recentDecrees" :key="decree.id" class="flex items-center justify-between py-2.5">
                        <div class="min-w-0">
                            <p class="truncate text-sm text-foreground">{{ decree.decree_number || 'SK (arsip)' }}</p>
                            <p class="text-xs text-slate-400">{{ decree.decree_type?.name }} · {{ formatTanggal(decree.effective_date) }}</p>
                        </div>
                        <a v-if="decree.download_url" :href="decree.download_url" target="_blank" rel="noopener"
                           class="text-xs text-primary-600 hover:underline">Unduh PDF</a>
                        <span v-else class="text-xs text-slate-400">Arsip</span>
                    </li>
                </ul>
            </div>

            <div v-if="activeDuties?.length" class="card p-6">
                <h3 class="mb-3 text-sm font-semibold text-foreground">Tugas Tambahan Berjalan</h3>
                <ul class="space-y-2">
                    <li v-for="duty in activeDuties" :key="duty.id" class="flex items-center justify-between text-sm">
                        <span class="text-foreground">{{ duty.additional_duty?.name }}</span>
                        <span class="text-xs text-slate-400">s.d. {{ formatTanggal(duty.end_date) }}</span>
                    </li>
                </ul>
            </div>

            <div class="card flex items-center justify-between gap-4 p-6">
                <div>
                    <h3 class="text-sm font-semibold text-foreground">Beranda GTK</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Kelola data pribadi, berkas, dan unggah arsip SK lama.</p>
                </div>
                <Link :href="'/portal'" class="btn-primary">Buka Beranda GTK</Link>
            </div>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="card in cards" :key="card.label" class="card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[13px] font-medium text-slate-500">{{ card.label }}</p>
                        <p class="mt-1.5 text-3xl font-semibold tracking-tight text-foreground">{{ card.value }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg" :class="card.tone">
                        <component :is="card.icon" :size="20" />
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!employee" class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div v-if="employeesByUnit.length" class="card p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-foreground">GTK Aktif per Satuan Kerja</h3>
                    <Users :size="16" class="text-slate-400" />
                </div>
                <div class="space-y-4">
                    <div v-for="row in employeesByUnit" :key="row.label">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="text-slate-600">{{ row.label }}</span>
                            <span class="font-semibold text-foreground">{{ row.total }}</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-muted">
                            <div class="h-full rounded-full bg-primary-600 transition-all duration-500"
                                 :style="{ width: `${maxTotal ? (row.total / maxTotal) * 100 : 0}%` }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="queueCards.length" class="card p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-foreground">Antrean Pekerjaan</h3>
                    <Inbox :size="16" class="text-slate-400" />
                </div>
                <div class="space-y-3">
                    <Link v-for="item in queueCards" :key="item.label" :href="item.href"
                          class="flex items-center justify-between rounded-lg border border-border px-4 py-3 transition-colors hover:bg-muted/60">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg" :class="item.tone">
                                <component :is="item.icon" :size="17" />
                            </span>
                            <span class="text-sm font-medium text-foreground">{{ item.label }}</span>
                        </div>
                        <span class="text-xl font-semibold text-foreground">{{ item.value }}</span>
                    </Link>
                    <p v-if="!queueCards.length" class="py-6 text-center text-sm text-slate-400">Tidak ada pekerjaan tertunda 🎉</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, Building2, CheckCircle2, ClipboardList, FileSignature, Inbox, Users, User } from 'lucide-vue-next';
import AdminLayout from '../Layouts/AdminLayout.vue';
import { useTranslation } from '../helpers/translation';
import { formatTanggal } from '../utils/date';

const { t } = useTranslation();

const props = defineProps({
    employee: Object,
    completeness: Object,
    recentDecree: Object,
    recentDecrees: Array,
    activeDuties: Array,
    totalEmployees: Number,
    activeEmployees: Number,
    workUnitCount: Number,
    pendingVerification: Number,
    pendingSignature: Number,
    employeesByUnit: Array,
});

const cards = computed(() => {
    if (props.employee) {
        return [];
    }

    const items = [
        { label: 'Total GTK', value: props.totalEmployees ?? 0, icon: Users, tone: 'bg-primary-50 text-primary-600' },
        { label: 'GTK Aktif', value: props.activeEmployees ?? 0, icon: User, tone: 'bg-emerald-50 text-emerald-600' },
        { label: 'Satuan Kerja Aktif', value: props.workUnitCount ?? 0, icon: Building2, tone: 'bg-amber-50 text-amber-600' },
    ];

    return items;
});

const queueCards = computed(() => {
    if (props.employee) {
        return [];
    }

    const items = [];

    if (props.pendingVerification !== undefined) {
        items.push({
            label: 'SK menunggu verifikasi',
            value: props.pendingVerification ?? 0,
            href: '/admin/decrees?status=submitted',
            icon: ClipboardList,
            tone: 'bg-amber-50 text-amber-600',
        });
    }

    if (props.pendingSignature !== undefined) {
        items.push({
            label: 'SK menunggu tanda tangan',
            value: props.pendingSignature ?? 0,
            href: '/admin/decrees?status=verified',
            icon: FileSignature,
            tone: 'bg-blue-50 text-blue-600',
        });
    }

    return items;
});

const maxTotal = computed(() => Math.max(1, ...(props.employeesByUnit ?? []).map((row) => row.total)));
</script>
