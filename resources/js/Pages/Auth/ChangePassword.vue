<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-100 px-4">
        <div class="w-full max-w-md">
            <div class="rounded-lg bg-white p-8 shadow-sm">
                <h1 class="mb-2 text-xl font-semibold text-gray-800">{{ t('auth.password_change_title') }}</h1>
                <p class="mb-6 text-sm text-gray-500">{{ t('auth.password_change') }}</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ t('auth.password_current') }}</label>
                        <input v-model="form.current_password" type="password" autocomplete="current-password"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <p v-if="form.errors.current_password" class="mt-1 text-xs text-red-600">{{ form.errors.current_password }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ t('auth.password_new') }}</label>
                        <input v-model="form.password" type="password" autocomplete="new-password"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ t('auth.password_new_confirmation') }}</label>
                        <input v-model="form.password_confirmation" type="password" autocomplete="new-password"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <button type="submit" :disabled="form.processing"
                            class="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">
                        {{ t('common.save') }}
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

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/password/change');
}
</script>
