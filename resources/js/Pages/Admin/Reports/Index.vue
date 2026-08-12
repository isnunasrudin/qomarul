<template>
    <AdminLayout>
        <Head :title="'Laporan'" />

        <div class="mb-6">
            <h2 class="text-lg font-semibold text-foreground">Laporan</h2>
            <p class="text-sm text-slate-500">Pilih laporan; seluruhnya dapat diekspor ke Excel dan PDF.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Link v-for="report in reports" :key="report.key" :href="route(`admin.reports.${report.key}`)"
                  class="card group p-5 transition-shadow duration-150 hover:shadow-card-hover">
                <div class="flex items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                        <component :is="icons[report.icon]" :size="20" />
                    </div>
                    <ArrowRight :size="16" class="text-slate-300 transition-transform duration-150 group-hover:translate-x-0.5 group-hover:text-primary-500" />
                </div>
                <p class="mt-4 text-sm font-semibold text-foreground">{{ report.label }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ report.description }}</p>
            </Link>
        </div>
    </AdminLayout>
</template>

<script setup>
import { inject } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Briefcase, ClipboardList, KeyRound, ScrollText, User, Users } from 'lucide-vue-next';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');

defineProps(['reports']);

const icons = { users: Users, scroll: ScrollText, briefcase: Briefcase, clipboard: ClipboardList, user: User, key: KeyRound };
</script>
