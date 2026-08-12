<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-100 px-4">
        <div class="w-full max-w-md">
            <div class="card p-8">
                <h1 class="mb-2 text-xl font-semibold text-gray-800">{{ t('auth.password_change_title') }}</h1>
                <p class="mb-6 text-sm text-gray-500">{{ t('auth.password_change') }}</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="label">{{ t('auth.password_current') }}</label>
                        <input v-model="form.current_password" type="password" autocomplete="current-password"
                               class="input">
                        <p v-if="form.errors.current_password" class="error-text" role="alert">{{ form.errors.current_password }}</p>
                    </div>
                    <div>
                        <label class="label">{{ t('auth.password_new') }}</label>
                        <input v-model="form.password" type="password" autocomplete="new-password"
                               class="input">
                        <p v-if="form.errors.password" class="error-text" role="alert">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="label">{{ t('auth.password_new_confirmation') }}</label>
                        <input v-model="form.password_confirmation" type="password" autocomplete="new-password"
                               class="input">
                    </div>
                    <button type="submit" :disabled="form.processing"
                            class="w-full rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50">
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
