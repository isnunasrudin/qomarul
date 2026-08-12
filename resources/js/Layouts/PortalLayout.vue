<template>
    <div class="min-h-screen bg-gray-50">
        <header class="sticky top-0 z-40 bg-emerald-800 text-white shadow-sm">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3">
                <div>
                    <p class="text-sm font-bold">SIMQOH</p>
                    <p class="text-[11px] text-emerald-200">Portal Mandiri GTK</p>
                </div>
                <div class="flex items-center gap-3">
                    <span v-if="auth.user" class="max-w-32 truncate text-xs text-emerald-100">{{ auth.user.name }}</span>
                    <button type="button" @click="logout" class="text-xs text-emerald-200 hover:text-white">
                        {{ t('common.logout') }}
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-5 pb-16">
            <div v-if="flash.success" class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ flash.success }}</div>
            <div v-if="flash.error" class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ flash.error }}</div>

            <slot />
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue';
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
