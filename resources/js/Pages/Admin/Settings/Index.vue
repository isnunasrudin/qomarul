<template>
    <AdminLayout>
        <Head :title="'Pengaturan Yayasan'" />

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Pengaturan Yayasan</h2>
            <p class="text-sm text-gray-500">Identitas yayasan, kop surat, dan format NIGY. Seluruh teks kop dan tembusan pada PDF diambil dari sini.</p>
        </div>

        <form @submit.prevent="save" class="space-y-6">
            <section v-for="group in groups" :key="group.key" class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-gray-700">{{ group.title }}</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div v-for="field in group.fields" :key="field.key" :class="{ 'sm:col-span-2': field.full }">
                        <label class="block text-sm font-medium text-gray-700">{{ field.label }}</label>
                        <textarea v-if="field.type === 'textarea'" v-model="form[field.key]" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                        <input v-else v-model="form[field.key]" :type="field.type ?? 'text'"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <p v-if="form.errors[field.key]" class="mt-1 text-xs text-red-600">{{ form.errors[field.key] }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-gray-700">Gambar Tanda Tangan Basah (Khusus Admin Yayasan)</h3>
                <p class="mb-3 text-xs text-gray-500">
                    Disimpan di luar document root (izin 0400), hanya dibaca proses penandatanganan,
                    tidak pernah tampil di pratinjau draft. Konfirmasi kata sandi wajib.
                </p>
                <form @submit.prevent="uploadSignature" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kata Sandi Anda</label>
                        <input v-model="signatureForm.current_password" type="password" autocomplete="current-password"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <p v-if="signatureForm.errors.current_password" class="mt-1 text-xs text-red-600">{{ signatureForm.errors.current_password }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gambar PNG/JPG (maks 2 MB)</label>
                        <input type="file" accept="image/png,image/jpeg" @change="(e) => { signatureForm.file = e.target.files[0]; }"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100">
                        <p v-if="signatureForm.errors.file" class="mt-1 text-xs text-red-600">{{ signatureForm.errors.file }}</p>
                    </div>
                    <button type="submit" :disabled="signatureForm.processing"
                            class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50 sm:col-span-2">
                        Ganti Tanda Tangan
                    </button>
                </form>
            </section>

            <button type="submit" :disabled="form.processing"
                    class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50">
                Simpan Pengaturan
            </button>
        </form>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps(['settings', 'schema']);

const form = useForm({});

Object.entries(props.settings).forEach(([group, values]) => {
    Object.entries(values).forEach(([key, value]) => {
        form[`${group}.${key}`] = Array.isArray(value) ? value.join('\n') : (value ?? '');
    });
});

const groups = computed(() => [
    {
        key: 'foundation',
        title: 'Identitas Yayasan',
        fields: [
            { key: 'foundation.name', label: 'Nama Yayasan' },
            { key: 'foundation.address', label: 'Alamat' },
            { key: 'foundation.notary_deed', label: 'Akta Notaris' },
            { key: 'foundation.sk_menkumham', label: 'Nomor SK Menkumham' },
            { key: 'foundation.chairman_name', label: 'Nama Ketua Yayasan' },
            { key: 'foundation.chairman_position', label: 'Jabatan Penanda Tangan' },
            { key: 'foundation.default_issued_place', label: 'Tempat Penetapan Default' },
        ],
    },
    {
        key: 'letterhead',
        title: 'Kop Surat & Tembusan',
        fields: [
            { key: 'letterhead.cc_list', label: 'Daftar Tembusan Default (satu per baris, gunakan {satker} untuk nama satuan kerja)', type: 'textarea', full: true },
        ],
    },
    {
        key: 'nigy',
        title: 'Format NIGY',
        fields: [
            { key: 'nigy.format', label: 'Format (token: {tahun_masuk} {bulan_masuk} {kode_satker} {kode_jenjang} {urut})' },
            { key: 'nigy.padding', label: 'Panjang Padding Nomor Urut' },
        ],
    },
]);

function save() {
    const payload = { ...form.data() };

    Object.entries(payload).forEach(([key, value]) => {
        if (typeof value === 'string' && value.includes('\n')) {
            payload[key] = value.split('\n').map((line) => line.trim()).filter(Boolean);
        }
    });

    form.post('/admin/settings', { ...payload, preserveScroll: true, forceFormData: true });
}

const signatureForm = useForm({ current_password: '', file: null });

function uploadSignature() {
    signatureForm.post('/admin/settings/signature', { preserveScroll: true, onSuccess: () => signatureForm.reset() });
}
</script>
