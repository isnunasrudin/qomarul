<template>
    <AdminLayout>
        <Head :title="'Arsip SK Lama'" />

        <div class="mb-6">
            <h2 class="text-lg font-semibold text-foreground">Arsip SK Lama</h2>
            <p class="text-sm text-slate-500">Unggah pindaian SK yang pernah terbit sebelum sistem ini berjalan.</p>
        </div>

        <div class="card mb-6 border-blue-200 bg-blue-50/60 p-5">
            <div class="flex items-start gap-3">
                <Info :size="18" class="mt-0.5 shrink-0 text-blue-600" />
                <p class="text-sm text-blue-800">
                    Arsip berupa PDF pindaian SK yang pernah terbit. Setelah diunggah, admin akan
                    memverifikasinya sebelum masuk riwayat resmi Anda.
                </p>
            </div>
        </div>

        <form @submit.prevent="uploadLegacy" enctype="multipart/form-data" class="card p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label text-xs">Berkas PDF (maks 5 MB)</label>
                    <input type="file" accept="application/pdf" @change="(e) => { legacyForm.file = e.target.files[0]; }"
                           class="mt-0.5 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                    <p v-if="legacyForm.errors.file" class="error-text" role="alert">{{ legacyForm.errors.file }}</p>
                </div>
                <div>
                    <label class="label text-xs">Nomor SK (opsional)</label>
                    <input v-model="legacyForm.decree_number" type="text" class="input">
                </div>
                <div>
                    <label class="label text-xs">Tanggal Penetapan (opsional)</label>
                    <input v-model="legacyForm.issued_date" type="date" class="input">
                </div>
                <div>
                    <label class="label text-xs">Tahun Pelajaran (opsional)</label>
                    <input v-model="legacyForm.academic_year" type="text" placeholder="2025/2026" class="input">
                </div>
                <button type="submit" :disabled="legacyForm.processing"
                        class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50 sm:col-span-2">
                    Unggah Arsip SK
                </button>
            </div>
        </form>
    </AdminLayout>
</template>

<script setup>
import { Info } from 'lucide-vue-next';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';

const legacyForm = useForm({ file: null, decree_number: '', issued_date: '', academic_year: '' });

function uploadLegacy() {
    legacyForm.post('/portal/decrees/legacy', { preserveScroll: true, onSuccess: () => { legacyForm.reset(); } });
}
</script>
