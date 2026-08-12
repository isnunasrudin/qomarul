<template>
    <AdminLayout>
        <Head :title="'Berkas Kepegawaian'" />

        <div class="mb-6">
            <h2 class="text-lg font-semibold text-foreground">Berkas Kepegawaian</h2>
            <p class="text-sm text-slate-500">Unggah dan unduh berkas pendukung kepegawaian Anda.</p>
        </div>

        <div v-if="missingBerkas.length" class="card mb-6 border-amber-200 bg-amber-50/60 p-5">
            <div class="flex items-start gap-3">
                <AlertCircle :size="18" class="mt-0.5 shrink-0 text-amber-600" />
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-amber-800">Berkas yang belum diunggah</p>
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <span v-for="key in missingBerkas" :key="key"
                              class="rounded-full border border-amber-300 bg-white px-2.5 py-1 text-xs text-amber-800">
                            {{ missingLabel(key) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <form @submit.prevent="uploadDocument" class="card mb-6 p-6">
            <h3 class="mb-3 text-sm font-semibold text-foreground">Unggah Berkas Baru</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label text-xs">Kategori</label>
                    <select v-model="docForm.category"
                            class="input">
                        <option v-for="category in documentCategories" :key="category.value" :value="category.value">{{ category.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="label text-xs">Berkas (PDF/JPG/PNG, maks 5 MB)</label>
                    <input type="file" accept="application/pdf,image/jpeg,image/png" @change="(e) => { docForm.file = e.target.files[0]; }"
                           class="mt-0.5 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                    <p v-if="docForm.errors.file" class="error-text" role="alert">{{ docForm.errors.file }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="label text-xs">Nama Berkas (opsional)</label>
                    <input v-model="docForm.name" type="text" class="input" placeholder="cth: Ijazah S1 asli">
                </div>
                <button type="submit" :disabled="docForm.processing"
                        class="btn-primary disabled:opacity-50 sm:col-span-2">
                    Unggah Berkas
                </button>
            </div>
        </form>

        <div class="card p-6">
            <h3 class="mb-3 text-sm font-semibold text-foreground">Berkas Tersimpan</h3>
            <ul class="divide-y divide-gray-100">
                <li v-for="document in employee.documents" :key="document.id" class="flex items-center justify-between py-2.5 text-sm">
                    <div class="min-w-0">
                        <p class="truncate text-foreground">{{ document.name }}</p>
                        <p class="text-xs text-slate-400">{{ categoryLabel(document.category) }} · {{ formatTanggal(document.created_at) }}</p>
                    </div>
                    <a :href="document.signed_url" target="_blank" class="shrink-0 text-primary-600 hover:underline">Unduh</a>
                </li>
                <li v-if="!employee.documents.length" class="py-4 text-center text-sm text-slate-400">Belum ada berkas</li>
            </ul>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { AlertCircle } from 'lucide-vue-next';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import { formatTanggal } from '../../utils/date';
import { isBerkas, missingLabel } from '../../helpers/completeness';

const props = defineProps(['employee', 'completeness', 'documentCategories']);

const missingBerkas = computed(() => (props.completeness?.missing ?? []).filter(isBerkas));

const categoryLabels = Object.fromEntries((props.documentCategories ?? []).map((c) => [c.value, c.label]));

function categoryLabel(value) {
    return categoryLabels[value] ?? value;
}

const docForm = useForm({ category: 'ktp', name: '', file: null });

function uploadDocument() {
    docForm.post('/portal/documents', { preserveScroll: true, onSuccess: () => { docForm.reset(); } });
}
</script>
