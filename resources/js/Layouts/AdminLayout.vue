<template>
    <div class="min-h-screen bg-background">
        <div class="flex">
            <!-- Sidebar -->
            <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col bg-sidebar text-slate-300 md:flex">
                <div class="flex items-center gap-3 px-5 py-5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-600 text-white">
                        <Building2 :size="20" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">SIMQOH</p>
                        <p class="text-[11px] text-slate-400">{{ moduleName }}</p>
                    </div>
                </div>

                <nav class="flex-1 space-y-5 overflow-y-auto px-3 pb-4">
                    <div v-for="group in menuGroups" :key="group.label">
                        <p class="px-3 pb-1.5 text-[10px] font-semibold tracking-wider text-slate-500 uppercase">{{ group.label }}</p>
                        <div class="space-y-0.5">
                            <Link v-for="item in group.items" :key="item.label" :href="item.href"
                                  class="group flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] transition-colors duration-150"
                                  :class="isActive(item.href)
                                      ? 'bg-primary-600 text-white'
                                      : 'hover:bg-sidebar-hover hover:text-white'">
                                <component :is="item.icon" :size="17" class="shrink-0 opacity-80" />
                                <span>{{ item.label }}</span>
                            </Link>
                        </div>
                    </div>
                </nav>

                <div class="border-t border-white/10 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-600/30 text-xs font-bold text-white">
                            {{ initials }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-medium text-white">{{ auth.user?.name }}</p>
                            <p class="text-[11px] text-slate-400">{{ roleLabel }}</p>
                        </div>
                        <button type="button" class="cursor-pointer rounded-md p-1.5 text-slate-400 transition-colors hover:bg-white/10 hover:text-white" :title="t('common.logout')" @click="logout">
                            <LogOut :size="16" />
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Konten -->
            <div class="flex min-w-0 flex-1 flex-col md:pl-64">
                <!-- Header mobile -->
                <header class="sticky top-0 z-30 flex items-center justify-between border-b border-border bg-surface/90 px-4 py-3 backdrop-blur md:px-8">
                    <div class="flex items-center gap-2 md:hidden">
                        <button type="button" class="cursor-pointer rounded-md p-2 hover:bg-muted" @click="mobileOpen = !mobileOpen">
                            <Menu :size="20" />
                        </button>
                        <span class="text-sm font-bold text-foreground">SIMQOH</span>
                    </div>

                    <h1 class="hidden text-[15px] font-semibold text-foreground md:block">{{ pageTitle }}</h1>

                    <div class="flex items-center gap-2">
                        <span v-if="flash.success" class="badge bg-primary-50 text-primary-600">{{ flash.success }}</span>
                        <span v-if="flash.error" class="badge bg-red-50 text-red-600">{{ flash.error }}</span>
                    </div>
                </header>

                <main class="flex-1 p-4 md:p-8">
                    <slot />
                </main>
            </div>
        </div>

        <!-- Sidebar mobile -->
        <div v-if="mobileOpen" class="fixed inset-0 z-50 md:hidden" @click="mobileOpen = false">
            <div class="absolute inset-0 bg-black/50"></div>
            <aside class="absolute inset-y-0 left-0 w-72 bg-sidebar text-slate-300 shadow-xl" @click.stop>
                <div class="flex items-center justify-between px-5 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-600 text-white">
                            <Building2 :size="20" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">SIMQOH</p>
                            <p class="text-[11px] text-slate-400">{{ moduleName }}</p>
                        </div>
                    </div>
                    <button type="button" class="cursor-pointer rounded-md p-1.5 text-slate-400 hover:bg-white/10" @click="mobileOpen = false">
                        <X :size="18" />
                    </button>
                </div>
                <nav class="max-h-[calc(100vh-130px)] space-y-5 overflow-y-auto px-3 pb-4">
                    <div v-for="group in menuGroups" :key="group.label">
                        <p class="px-3 pb-1.5 text-[10px] font-semibold tracking-wider text-slate-500 uppercase">{{ group.label }}</p>
                        <div class="space-y-0.5">
                            <Link v-for="item in group.items" :key="item.label" :href="item.href"
                                  class="group flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] transition-colors duration-150"
                                  :class="isActive(item.href) ? 'bg-primary-600 text-white' : 'hover:bg-sidebar-hover hover:text-white'"
                                  @click="mobileOpen = false">
                                <component :is="item.icon" :size="17" class="shrink-0 opacity-80" />
                                <span>{{ item.label }}</span>
                            </Link>
                        </div>
                    </div>
                </nav>
            </aside>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Building2, FileText, Files, LayoutDashboard, LogOut, Menu, Settings, ShieldCheck, Users, UserCog, GraduationCap, Briefcase, ListChecks, ClipboardCheck, X, Layers, ScrollText } from 'lucide-vue-next';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useTranslation } from '../helpers/translation';

const { t } = useTranslation();
const page = usePage();
const auth = computed(() => page.props.auth || {});
const flash = computed(() => page.props.flash || {});
const mobileOpen = ref(false);

const roleLabels = {
    foundation_head: 'Ketua Yayasan',
    foundation_admin: 'Admin Yayasan',
    unit_admin: 'Admin Satuan Kerja',
    employee: 'GTK',
};

const moduleName = computed(() => (auth.value.user?.role === 'employee' ? 'Portal Mandiri GTK' : 'Kepegawaian & SK'));
const roleLabel = computed(() => roleLabels[auth.value.user?.role] ?? '');
const pageTitle = computed(() => page.props.pageTitle ?? 'Dashboard');

const initials = computed(() => (auth.value.user?.name ?? '?').split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase());

const menuGroups = computed(() => {
    const role = auth.value.user?.role;
    const groups = [];

    groups.push({
        label: 'Utama',
        items: [{ label: 'Dasbor', href: '/', icon: LayoutDashboard }],
    });

    if (role === 'foundation_head' || role === 'foundation_admin' || role === 'unit_admin') {
        groups.push({
            label: 'Kepegawaian',
            items: [
                { label: 'Data GTK', href: '/admin/employees', icon: Users },
                { label: 'Tugas Tambahan', href: '/admin/duties', icon: Briefcase },
            ],
        });

        groups.push({
            label: 'Surat Keputusan',
            items: [
                { label: 'Daftar SK', href: '/admin/decrees', icon: ScrollText },
                { label: 'Batch SK', href: '/admin/batches', icon: Files },
                { label: 'Verifikasi Arsip SK', href: '/admin/decree-legacy', icon: ClipboardCheck },
            ],
        });
    }

    if (role === 'foundation_head' || role === 'foundation_admin') {
        groups.push({
            label: 'Master Data',
            items: [
                { label: 'Satuan Kerja', href: '/admin/work-units', icon: Building2 },
                { label: 'Jabatan', href: '/admin/positions', icon: Briefcase },
                { label: 'Status Kepegawaian', href: '/admin/employment-statuses', icon: ListChecks },
                { label: 'Referensi Tugas Tambahan', href: '/admin/additional-duties', icon: Layers },
                { label: 'Jenis SK', href: '/admin/decree-types', icon: FileText },
            ],
        });

        groups.push({
            label: 'Sistem',
            items: [
                { label: 'Pengguna', href: '/admin/users', icon: UserCog },
                { label: 'Pengaturan Yayasan', href: '/admin/settings', icon: Settings },
                { label: 'Sertifikat & Tanda Tangan', href: '/admin/certificates', icon: ShieldCheck },
            ],
        });
    }

    return groups;
});

function isActive(href) {
    return page.url === href;
}

function logout() {
    router.post('/logout');
}
</script>
