<template>
    <AdminLayout>
        <Head :title="'Beranda GTK'" />

        <div v-if="employee" class="card mb-6 flex flex-wrap items-center justify-between gap-4 p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600/10 text-primary-600">
                    <User :size="24" />
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-foreground">{{ employee.full_name }}</h2>
                    <p class="text-sm text-slate-500">
                        NIGY <span class="font-mono">{{ employee.nigy }}</span> ·
                        {{ employee.work_unit?.name }} · {{ employee.position?.name }}
                    </p>
                </div>
            </div>
            <span class="badge" :class="completeness.complete ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                Profil {{ completeness.percentage }}%
            </span>
        </div>

        <div v-if="!completeness.complete" class="card mb-6 border-amber-200 bg-amber-50/60 p-5">
            <div class="flex items-start gap-3">
                <AlertCircle :size="18" class="mt-0.5 shrink-0 text-amber-600" />
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-amber-800">Data masih belum lengkap</p>
                    <p class="mt-0.5 text-xs text-amber-700">
                        Lengkapi data berikut agar profil Anda terverifikasi penuh:
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <Link v-for="key in completeness.missing" :key="key" :href="sectionFor(key)"
                              class="rounded-full border border-amber-300 bg-white px-2.5 py-1 text-xs text-amber-800 hover:bg-amber-100">
                            {{ missingLabel(key) }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link v-for="menu in menus" :key="menu.href" :href="menu.href"
                  class="card group p-5 transition-shadow hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[13px] font-medium text-slate-500">{{ menu.label }}</p>
                        <p class="mt-1.5 text-sm font-semibold text-foreground group-hover:text-primary-600">{{ menu.title }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg" :class="menu.tone">
                        <component :is="menu.icon" :size="20" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ menu.desc }}</p>
            </Link>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="card p-6">
                <h3 class="mb-3 text-sm font-semibold text-foreground">SK Terbaru</h3>
                <ul v-if="recentDecrees.length" class="divide-y divide-gray-100">
                    <li v-for="decree in recentDecrees" :key="decree.id" class="flex items-center justify-between py-2.5">
                        <div class="min-w-0">
                            <p class="truncate text-sm text-foreground">{{ decree.decree_number || 'SK (arsip)' }}</p>
                            <p class="text-xs text-slate-400">{{ decree.decree_type?.name }} · {{ formatTanggal(decree.effective_date) }}</p>
                        </div>
                        <a v-if="!decree.is_legacy" :href="decree.download_url" target="_blank" rel="noopener"
                           class="text-xs text-primary-600 hover:underline">Unduh PDF</a>
                        <span v-else class="text-xs text-slate-400">Arsip</span>
                    </li>
                </ul>
                <p v-else class="py-4 text-center text-sm text-slate-400">Belum ada SK</p>
            </div>

            <div class="card p-6">
                <h3 class="mb-3 text-sm font-semibold text-foreground">Tugas Tambahan Berjalan</h3>
                <ul v-if="activeDuties.length" class="space-y-2">
                    <li v-for="duty in activeDuties" :key="duty.id" class="flex items-center justify-between text-sm">
                        <span class="text-foreground">{{ duty.additional_duty?.name }}</span>
                        <span class="text-xs text-slate-400">s.d. {{ formatTanggal(duty.end_date) }}</span>
                    </li>
                </ul>
                <p v-else class="py-4 text-center text-sm text-slate-400">Tidak ada tugas tambahan</p>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { AlertCircle, Archive, Bell, FolderOpen, User } from 'lucide-vue-next';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import { formatTanggal } from '../../utils/date';
import { missingLabel, sectionFor } from '../../helpers/completeness';

const props = defineProps(['employee', 'completeness', 'recentDecrees', 'activeDuties']);

const menus = computed(() => [
    {
        label: 'Profil Anda', title: 'Data Pribadi', href: '/portal/profile', icon: User,
        desc: 'Perbarui data pribadi & kontak', tone: 'bg-primary-50 text-primary-600',
    },
    {
        label: 'Dokumen', title: 'Berkas Kepegawaian', href: '/portal/documents', icon: FolderOpen,
        desc: 'Unggah & unduh berkas', tone: 'bg-emerald-50 text-emerald-600',
    },
    {
        label: 'SK Lama', title: 'Arsip SK Lama', href: '/portal/legacy', icon: Archive,
        desc: 'Unggah pindaian SK lama', tone: 'bg-amber-50 text-amber-600',
    },
    {
        label: 'Info', title: 'Notifikasi', href: '/admin/notifications', icon: Bell,
        desc: 'Pantau status SK Anda', tone: 'bg-blue-50 text-blue-600',
    },
]);
</script>
