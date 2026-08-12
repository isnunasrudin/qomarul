<template>
    <div class="min-h-screen bg-background">
        <header class="sticky top-0 z-40 bg-sidebar text-white shadow-sm">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600 text-white">
                        <GraduationCap :size="18" />
                    </div>
                    <div>
                        <p class="text-sm font-bold">SIMQOH</p>
                        <p class="text-[11px] text-slate-400">Portal Mandiri GTK</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span v-if="auth.user" class="max-w-32 truncate text-xs text-slate-300">{{ auth.user.name }}</span>
                    <button type="button" @click="logout"
                            class="flex cursor-pointer items-center gap-1.5 rounded-md px-2 py-1.5 text-xs text-slate-300 transition-colors hover:bg-white/10 hover:text-white">
                        <LogOut :size="14" />
                        {{ t('common.logout') }}
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-5 pb-16">
            <div v-if="flash.success" class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <CheckCircle :size="16" class="shrink-0" />
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="mb-4 flex items-center gap-2 rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                <AlertCircle :size="16" class="shrink-0" />
                {{ flash.error }}
            </div>

            <slot />
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { AlertCircle, CheckCircle, GraduationCap, LogOut } from 'lucide-vue-next';
import { router, usePage } from '@inertiajs/vue3';
import { useTranslation } from '../helpers/translation';

const { t } = useTranslation();
const page = usePage();
const auth = computed(() => page.props.auth || {});
const flash = computed(() => page.props.flash || {});

function logout() {
    router.post('/logout');
}
</script>
