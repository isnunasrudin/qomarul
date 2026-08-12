<template>
    <div class="min-h-screen bg-gray-100">
        <div class="flex">
            <aside v-if="auth.user" class="hidden md:flex w-64 flex-col bg-emerald-900 text-white min-h-screen">
                <div class="px-6 py-5 border-b border-emerald-800">
                    <p class="font-bold text-lg">SIMQOH</p>
                    <p class="text-xs text-emerald-300">{{ moduleName }}</p>
                </div>
                <nav class="flex-1 px-3 py-4 space-y-1">
                    <template v-for="item in menu" :key="item.label">
                        <Link :href="item.href"
                              class="block px-3 py-2 rounded-md text-sm hover:bg-emerald-800"
                              :class="{ 'bg-emerald-800': isActive(item.href) }">
                            {{ item.label }}
                        </Link>
                    </template>
                </nav>
                <div class="px-6 py-4 border-t border-emerald-800 text-sm">
                    <p class="font-semibold truncate">{{ auth.user.name }}</p>
                    <p class="text-emerald-300 text-xs">{{ roleLabel }}</p>
                    <button type="button" class="mt-3 text-emerald-200 hover:text-white text-sm" @click="logout">
                        {{ t('common.logout') }}
                    </button>
                </div>
            </aside>

            <div class="flex-1 flex flex-col min-w-0">
                <header v-if="auth.user" class="bg-white shadow-sm px-6 py-4 flex items-center justify-between">
                    <h1 class="text-lg font-semibold text-gray-800">{{ pageTitle }}</h1>
                    <div class="flex items-center gap-4">
                        <span v-if="flash.success" class="text-sm text-emerald-600">{{ flash.success }}</span>
                        <span v-if="flash.error" class="text-sm text-red-600">{{ flash.error }}</span>
                    </div>
                </header>
                <main class="flex-1 p-6">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useTranslation } from '../helpers/translation';

const { t } = useTranslation();
const page = usePage();
const auth = computed(() => page.props.auth || {});
const flash = computed(() => page.props.flash || {});

const roleLabels = {
    foundation_head: 'Ketua Yayasan',
    foundation_admin: 'Admin Yayasan',
    unit_admin: 'Admin Satuan Kerja',
    employee: 'GTK',
};

const moduleName = computed(() => (auth.value.user?.role === 'employee' ? 'Portal Mandiri GTK' : 'Kepegawaian & SK'));

const roleLabel = computed(() => roleLabels[auth.value.user?.role] ?? '');

const pageTitle = computed(() => page.props.pageTitle ?? 'Dashboard');

const menu = computed(() => {
    const role = auth.value.user?.role;
    const items = [{ label: 'Dasbor', href: '/' }];

    if (role === 'foundation_head' || role === 'foundation_admin' || role === 'unit_admin') {
        items.push(
            { label: 'Data GTK', href: '/admin/employees' },
            { label: 'Surat Keputusan', href: '/admin/decrees' },
            { label: 'Tugas Tambahan', href: '/admin/duties' },
            { label: 'Verifikasi Arsip SK', href: '/admin/decree-legacy' },
        );
    }

    if (role === 'foundation_head' || role === 'foundation_admin') {
        items.push(
            { label: 'Satuan Kerja', href: '/admin/work-units' },
            { label: 'Jabatan', href: '/admin/positions' },
            { label: 'Status Kepegawaian', href: '/admin/employment-statuses' },
            { label: 'Tugas Tambahan', href: '/admin/additional-duties' },
            { label: 'Jenis SK', href: '/admin/decree-types' },
            { label: 'Pengguna', href: '/admin/users' },
            { label: 'Pengaturan Yayasan', href: '/admin/settings' },
            { label: 'Sertifikat & Tanda Tangan', href: '/admin/certificates' },
        );
    }

    return items;
});

function isActive(href) {
    return page.url === href;
}

function logout() {
    router.post('/logout');
}
</script>
