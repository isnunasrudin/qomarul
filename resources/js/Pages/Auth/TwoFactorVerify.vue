<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-100 px-4">
        <div class="w-full max-w-md">
            <div class="rounded-lg bg-white p-8 shadow-sm">
                <h1 class="mb-2 text-xl font-semibold text-gray-800">{{ t('auth.two_factor_title') }}</h1>
                <p class="mb-6 text-sm text-gray-500">{{ t('auth.two_factor_code') }}</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <input v-model="form.code" type="text" inputmode="numeric" maxlength="6" autofocus
                               autocomplete="one-time-code"
                               class="mt-1 block w-full rounded-md border-gray-300 text-center font-mono text-lg tracking-widest shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <p v-if="form.errors.code" class="mt-1 text-xs text-red-600">{{ form.errors.code }}</p>
                    </div>
                    <button type="submit" :disabled="form.processing"
                            class="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">
                        {{ t('common.confirmed') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { useTranslation } from '../../helpers/translation';

const { t } = useTranslation();

const form = useForm({ code: '' });

function submit() {
    form.post('/2fa/challenge');
}
</script>
