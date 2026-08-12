<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-100 px-4">
        <div class="w-full max-w-md">
            <div class="rounded-lg bg-white p-8 shadow-sm">
                <div class="mb-6 text-center">
                    <h1 class="text-2xl font-bold text-emerald-800">SIMQOH</h1>
                    <p class="mt-1 text-sm text-gray-500">Sistem Informasi Manajemen Qomarul Hidayah</p>
                    <p class="text-xs text-gray-400">Yayasan Pondok Pesantren Qomarul Hidayah · Gondang Tugu Trenggalek</p>
                </div>

                <form @submit.prevent="submit">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ t('auth.username') }}</label>
                            <input v-model="form.login" type="text" autofocus autocomplete="username"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <p v-if="form.errors.login" class="mt-1 text-xs text-red-600">{{ form.errors.login }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ t('auth.password_label') }}</label>
                            <input v-model="form.password" type="password" autocomplete="current-password"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input v-model="form.remember" type="checkbox" class="rounded border-gray-300 text-emerald-600">
                            {{ t('auth.remember') }}
                        </label>
                        <button type="submit" :disabled="form.processing"
                                class="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">
                            {{ t('auth.login') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { useTranslation } from '../../helpers/translation';

const { t } = useTranslation();

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login');
}
</script>
