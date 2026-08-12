<template>
    <AdminLayout>
        <Head :title="'Sertifikat Penandatanganan'" />

        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Sertifikat Penandatanganan</h2>
                <p class="text-sm text-gray-500">
                    PKCS#12 (.p12) untuk tanda tangan digital SK — buat baru atau unggah berkas. Disimpan di luar document root.
                </p>
            </div>
            <div class="flex gap-2">
                <button type="button" @click="openGenerate"
                        class="btn-primary">
                    Buat Sertifikat
                </button>
                <button type="button" @click="openUpload"
                        class="rounded-md border border-primary-200 px-3 py-2 text-sm text-primary-600 hover:bg-primary-50">
                    Unggah .p12
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Nama</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Subject (CN)</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Berlaku</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Fingerprint</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="certificate in certificates" :key="certificate.id">
                        <td class="px-4 py-3 text-gray-700">{{ certificate.name }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ certificate.subject }}</td>
                        <td class="px-4 py-3">
                            <span :class="expiryClass(certificate.valid_until)">{{ certificate.valid_until || '—' }}</span>
                            <span v-if="expiringSoon(certificate.valid_until)"
                                  class="ml-2 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">
                                ≤ 60 hari
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ certificate.fingerprint }}</td>
                        <td class="px-4 py-3">
                            <span :class="certificate.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                  class="rounded-full px-2 py-0.5 text-xs">
                                {{ certificate.is_active ? 'Aktif' : 'Arsip' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" @click="showDetail(certificate)" class="text-primary-600 hover:underline">Lihat Detail</button>
                        </td>
                    </tr>
                    <tr v-if="!certificates.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">
                            Belum ada sertifikat. Buat sertifikat baru atau unggah .p12.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Buat Sertifikat -->
        <div v-if="modal === 'generate'" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto card p-6">
                <h3 class="mb-1 text-base font-semibold text-gray-800">Buat Sertifikat X.509 (Self-Signed)</h3>
                <p class="mb-4 text-xs text-gray-500">Pasangan kunci RSA digenerate di server, diekspor sebagai .p12, dan langsung diaktifkan.</p>
                <form @submit.prevent="submitGenerate" class="space-y-4">
                    <div>
                        <label class="label">Nama Sertifikat *</label>
                        <input v-model="genForm.name" type="text" placeholder="mis. yayasan-2027"
                               class="input">
                        <p v-if="genForm.errors.name" class="error-text" role="alert">{{ genForm.errors.name }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Common Name (CN) *</label>
                            <input v-model="genForm.common_name" type="text" placeholder="Yayasan Pondok Pesantren Qomarul Hidayah"
                                   class="input">
                            <p v-if="genForm.errors.common_name" class="error-text" role="alert">{{ genForm.errors.common_name }}</p>
                        </div>
                        <div>
                            <label class="label">Organisasi (O)</label>
                            <input v-model="genForm.organization" type="text" placeholder="YPP Qomarul Hidayah"
                                   class="input">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Unit Organisasi (OU)</label>
                            <input v-model="genForm.organizational_unit" type="text" placeholder="Bidang Personalia & SDM"
                                   class="input">
                        </div>
                        <div>
                            <label class="label">Negara (C, 2 huruf)</label>
                            <input v-model="genForm.country" type="text" maxlength="2" placeholder="ID"
                                   class="input">
                            <p v-if="genForm.errors.country" class="error-text" role="alert">{{ genForm.errors.country }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Provinsi (ST)</label>
                            <input v-model="genForm.state" type="text" placeholder="Jawa Timur"
                                   class="input">
                        </div>
                        <div>
                            <label class="label">Kota (L)</label>
                            <input v-model="genForm.locality" type="text" placeholder="Trenggalek"
                                   class="input">
                        </div>
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input v-model="genForm.email" type="email" placeholder="admin@qomarulhidayah.sch.id"
                               class="input">
                        <p v-if="genForm.errors.email" class="error-text" role="alert">{{ genForm.errors.email }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="label">Masa Berlaku (hari)</label>
                            <input v-model="genForm.days" type="number" min="1"
                                   class="input">
                        </div>
                        <div>
                            <label class="label">Kunci (bit)</label>
                            <select v-model="genForm.key_bits" class="input">
                                <option value="2048">2048</option>
                                <option value="3072">3072</option>
                                <option value="4096">4096</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Digest</label>
                            <select v-model="genForm.digest" class="input">
                                <option value="sha256">SHA-256</option>
                                <option value="sha384">SHA-384</option>
                                <option value="sha512">SHA-512</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="label">Kata Sandi .p12 * (min. 4 karakter)</label>
                        <input v-model="genForm.password" type="password" autocomplete="new-password"
                               class="input">
                        <p v-if="genForm.errors.password" class="error-text" role="alert">{{ genForm.errors.password }}</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="btn-secondary">Batal</button>
                        <button type="submit" :disabled="genForm.processing" class="rounded-md bg-primary-600 px-4 py-2 text-sm text-white hover:bg-primary-700 disabled:opacity-50">
                            Generate & Aktifkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Unggah .p12 -->
        <div v-if="modal === 'upload'" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
            <div class="w-full max-w-md card p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">Unggah Sertifikat .p12</h3>
                <form @submit.prevent="submitUpload" class="space-y-4">
                    <div>
                        <label class="label">Nama Sertifikat</label>
                        <input v-model="form.name" type="text" placeholder="mis. yayasan-2026"
                               class="input">
                        <p v-if="form.errors.name" class="error-text" role="alert">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="label">Kata Sandi .p12</label>
                        <input v-model="form.password" type="password" autocomplete="new-password"
                               class="input">
                        <p v-if="form.errors.password" class="error-text" role="alert">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="label">Berkas .p12 (maks 4 MB)</label>
                        <input type="file" accept=".p12" @change="(e) => { form.file = e.target.files[0]; }"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                        <p v-if="form.errors.file" class="error-text" role="alert">{{ form.errors.file }}</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="btn-secondary">Batal</button>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-primary-600 px-4 py-2 text-sm text-white hover:bg-primary-700 disabled:opacity-50">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Detail -->
        <div v-if="modal === 'detail'" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto card p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">Detail Sertifikat — {{ detail?.name }}</h3>

                <p v-if="detail?.error" class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{{ detail.error }}</p>

                <template v-else-if="detail">
                    <section class="mb-4">
                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Ringkasan</h4>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-1.5 text-sm sm:grid-cols-2">
                            <div><span class="text-gray-400">Status:</span> <b>{{ detail.is_active ? 'Aktif' : 'Arsip' }}</b></div>
                            <div><span class="text-gray-400">Berkas:</span> <span class="font-mono text-xs">{{ detail.p12_path }}</span></div>
                            <div><span class="text-gray-400">Fingerprint (SHA-256):</span> <span class="font-mono text-xs">{{ detail.fingerprint }}</span></div>
                            <div><span class="text-gray-400">Serial:</span> <span class="font-mono text-xs">{{ detail.serial }}</span></div>
                            <div><span class="text-gray-400">Berlaku dari:</span> {{ formatTs(detail.valid_from) }}</div>
                            <div><span class="text-gray-400">Berlaku sampai:</span> {{ formatTs(detail.valid_until) }}</div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Subject (Pemilik)</h4>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-1.5 text-sm sm:grid-cols-2">
                            <div v-for="(value, key) in detail.subject" :key="'s-' + key">
                                <span class="text-gray-400">{{ fieldLabel(key) }}:</span> <b>{{ value }}</b>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Issuer (Penerbit)</h4>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-1.5 text-sm sm:grid-cols-2">
                            <div v-for="(value, key) in detail.issuer" :key="'i-' + key">
                                <span class="text-gray-400">{{ fieldLabel(key) }}:</span> <b>{{ value }}</b>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Kriptografi</h4>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-1.5 text-sm sm:grid-cols-2">
                            <div><span class="text-gray-400">Algoritma tanda tangan:</span> {{ detail.signature_algorithm }}</div>
                            <div><span class="text-gray-400">Subject Key Identifier:</span> <span class="font-mono text-xs">{{ detail.public_key }}</span></div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Ekstensi X.509</h4>
                        <div v-if="Object.keys(detail.extensions || {}).length" class="rounded-md border border-gray-100 bg-gray-50 p-3">
                            <div v-for="(value, key) in detail.extensions" :key="'e-' + key" class="mb-1.5 text-xs">
                                <span class="font-semibold text-gray-600">{{ key }}:</span>
                                <span class="break-all text-gray-500">{{ value }}</span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-gray-400">Tidak ada ekstensi.</p>
                    </section>

                    <section>
                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Sertifikat (PEM)</h4>
                        <textarea rows="7" readonly class="w-full rounded-md border-gray-200 bg-gray-50 font-mono text-[11px] text-gray-600">{{ detail.pem_cert }}</textarea>
                    </section>
                </template>

                <div class="mt-4 flex justify-end">
                    <button type="button" @click="modal = null" class="btn-secondary">Tutup</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { inject, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');

defineProps(['certificates', 'activeCertificate']);

const modal = ref(null);
const detail = ref(null);

const form = useForm({ name: '', password: '', file: null });

const genForm = useForm({
    name: '',
    common_name: 'Yayasan Pondok Pesantren Qomarul Hidayah',
    organization: 'YPP Qomarul Hidayah',
    organizational_unit: '',
    country: 'ID',
    state: 'Jawa Timur',
    locality: 'Trenggalek',
    email: '',
    days: 3650,
    key_bits: 4096,
    digest: 'sha256',
    password: '',
});

const fieldLabels = {
    CN: 'Common Name', O: 'Organisasi', OU: 'Unit Organisasi',
    C: 'Negara', ST: 'Provinsi', L: 'Kota',
    emailAddress: 'Email', serialNumber: 'Nomor Seri',
};

function fieldLabel(key) {
    return fieldLabels[key] ?? key;
}

function openGenerate() {
    genForm.clearErrors();
    modal.value = 'generate';
}

function submitGenerate() {
    genForm.post(route('admin.certificates.generate'), { onSuccess: () => { modal.value = null; } });
}

function openUpload() {
    form.clearErrors();
    form.reset();
    modal.value = 'upload';
}

function submitUpload() {
    form.post(route('admin.certificates.store'), { onSuccess: () => { modal.value = null; } });
}

async function showDetail(certificate) {
    detail.value = null;
    modal.value = 'detail';
    const response = await fetch(route('admin.certificates.detail', certificate.id), { credentials: 'include' });
    detail.value = await response.json();
}

function formatTs(ts) {
    if (!ts) return '—';
    const d = new Date(ts * 1000);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

function expiringSoon(validUntil) {
    if (!validUntil) return false;
    const until = new Date(validUntil);
    const limit = new Date();
    limit.setDate(limit.getDate() + 60);
    return until <= limit;
}

function expiryClass(validUntil) {
    if (!validUntil) return 'text-gray-400';
    return new Date(validUntil) < new Date() ? 'text-red-600 font-medium' : 'text-gray-700';
}
</script>
