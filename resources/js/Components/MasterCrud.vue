<template>
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">{{ title }}</h2>
            <button type="button" @click="openCreate"
                    class="btn-primary">
                {{ t('common.create') }}
            </button>
        </div>

        <div class="table-wrap">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th v-for="column in columns" :key="column.key"
                            class="px-4 py-3 text-left font-medium text-gray-600">
                            {{ column.label }}
                        </th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="item in items.data" :key="item.id">
                        <td v-for="column in columns" :key="column.key" class="px-4 py-3 text-gray-700">
                            <span v-if="column.badge && ['active', 'is_active'].includes(column.key)"
                                  :class="item[column.key] ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                  class="inline-block rounded-full px-2 py-0.5 text-xs">
                                {{ item[column.key] ? t('common.active') : t('common.inactive') }}
                            </span>
                            <span v-else>{{ formatValue(item, column) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" @click="openEdit(item)"
                                    class="text-primary-600 hover:underline">{{ t('common.edit') }}</button>
                        </td>
                    </tr>
                    <tr v-if="!items.data.length">
                        <td :colspan="columns.length + 1" class="px-4 py-10 text-center text-sm text-slate-400">
                            {{ t('common.none') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
            <span>{{ t('common.page') }} {{ items.current_page }} / {{ items.last_page }} ·
                {{ items.total }} {{ t('common.records') }}</span>
            <div v-if="items.last_page > 1" class="flex gap-2">
                <button v-if="items.prev_page_url" type="button" @click="paginate(items.current_page - 1)"
                        class="btn-secondary px-3 py-1">←</button>
                <button v-if="items.next_page_url" type="button" @click="paginate(items.current_page + 1)"
                        class="btn-secondary px-3 py-1">→</button>
            </div>
        </div>

        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="close">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto card p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">
                    {{ editingId ? t('common.edit') : t('common.create') }} — {{ title }}
                </h3>
                <form @submit.prevent="save" class="space-y-4">
                    <div v-for="field in fields" :key="field.key">
                        <label class="label">{{ field.label }}</label>
                        <textarea v-if="field.type === 'textarea'" v-model="form[field.key]"
                                  rows="3"
                                  class="input"></textarea>
                        <select v-else-if="field.type === 'select'" v-model="form[field.key]"
                                class="input">
                            <option v-if="field.placeholder !== false" value="">{{ field.placeholder ?? t('common.select') }}</option>
                            <option v-for="option in field.options" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <input v-else-if="field.type === 'checkbox'" v-model="form[field.key]" type="checkbox"
                               class="mt-2 rounded border-gray-300 text-primary-600">
                        <input v-else v-model="form[field.key]" :type="field.type ?? 'text'"
                               class="input">
                        <p v-if="form.errors[field.key]" class="error-text" role="alert">{{ form.errors[field.key] }}</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="close"
                                class="btn-secondary">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="submit" :disabled="form.processing"
                                class="btn-primary disabled:opacity-50">
                            {{ t('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useTranslation } from '../helpers/translation';

const { t } = useTranslation();

const props = defineProps({
    title: { type: String, required: true },
    columns: { type: Array, required: true },
    fields: { type: Array, required: true },
    items: { type: Object, required: true },
    routeBase: { type: String, required: true },
});

const modalOpen = ref(false);
const editingId = ref(null);

const initialForm = () => Object.fromEntries(props.fields.map((field) => [field.key, field.default ?? '']));

const form = useForm(initialForm());

function openCreate() {
    editingId.value = null;
    Object.assign(form, initialForm());
    form.clearErrors();
    modalOpen.value = true;
}

function openEdit(item) {
    editingId.value = item.id;
    Object.keys(form.data()).forEach((key) => {
        form[key] = item[key] ?? '';
    });
    form.clearErrors();
    modalOpen.value = true;
}

function close() {
    modalOpen.value = false;
}

function save() {
    if (editingId.value) {
        form.put(`/admin/${props.routeBase}/${editingId.value}`, { preserveScroll: true, onSuccess: close });
    } else {
        form.post(`/admin/${props.routeBase}`, { preserveScroll: true, onSuccess: close });
    }
}

function paginate(page) {
    router.get(window.location.pathname, { page }, { preserveState: true, preserveScroll: true });
}

function formatValue(item, column) {
    if (column.format) {
        return column.format(item[column.key]);
    }
    if (item[column.key] === null || item[column.key] === '') {
        return '—';
    }
    return item[column.key];
}

watch(() => props.items, () => {
    if (form.processing) {
        form.processing = false;
    }
});
</script>
