<template>
    <AdminLayout>
        <Head :title="'Pratinjau Impor Pengguna'" />

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Pratinjau Impor Pengguna</h2>
            <p class="text-sm text-gray-500">
                {{ preview.valid.length }} baris valid · {{ errorCount }} baris bermasalah.
                Tidak ada data yang tersimpan sebelum Anda mengonfirmasi.
            </p>
        </div>

        <div v-if="errorCount" class="mb-4 rounded-lg bg-red-50 p-5">
            <h3 class="mb-2 text-sm font-semibold text-red-700">Baris Bermasalah (tidak akan diimpor)</h3>
            <div v-for="(messages, line) in preview.errors" :key="line" class="mb-2">
                <p class="text-xs font-medium text-red-600">Baris {{ line }}:</p>
                <ul class="ml-4 list-disc text-xs text-red-500">
                    <li v-for="message in messages" :key="message">{{ message }}</li>
                </ul>
            </div>
        </div>

        <div class="card p-5">
            <h3 class="mb-2 text-sm font-semibold text-gray-700">Ringkasan Baris Valid</h3>
            <p class="mb-4 text-sm text-gray-600">
                Sandi awal dibuat otomatis bila kolom <code>kata_sandi</code> dikosongkan, dan ditampilkan
                sekali setelah impor. Semua pengguna wajib mengganti sandi saat masuk pertama.
            </p>
            <div class="max-h-72 overflow-y-auto rounded-md border border-gray-100">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="sticky top-0 bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Nama</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Username</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Email</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Peran</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Satuan Kerja</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">NIGY</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(row, index) in preview.valid" :key="row.username + index">
                            <td class="px-3 py-2 text-gray-700">{{ row.name }}</td>
                            <td class="px-3 py-2 font-mono text-xs text-gray-700">{{ row.username }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ row.email }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ roleLabel(row.role) }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ workUnitLabel(row.work_unit_id) }}</td>
                            <td class="px-3 py-2 font-mono text-xs text-gray-700">{{ row.employee_nigy || '—' }}</td>
                            <td class="px-3 py-2">
                                <span :class="row.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                      class="rounded-full px-2 py-0.5 text-xs">
                                    {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 flex justify-between">
            <Link :href="route('admin.users.index')" class="btn-secondary">
                Kembali
            </Link>
            <div v-if="preview.valid.length" class="flex gap-2">
                <button type="button" @click="router.get(route('admin.users.index'))"
                        class="btn-secondary">
                    Batal
                </button>
                <button type="button" :disabled="importing"
                        class="btn-primary disabled:opacity-50"
                        @click="confirmImport">
                    Simpan {{ preview.valid.length }} Pengguna
                </button>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, inject, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const route = inject('route');

const props = defineProps(['preview', 'roles', 'workUnits']);

const importing = ref(false);

const errorCount = computed(() => Object.keys(props.preview.errors ?? {}).length);

const roleLabels = computed(() => Object.fromEntries(
    (props.roles ?? []).map((role) => [role.value, role.label]),
));

const workUnitLabels = computed(() => Object.fromEntries(
    (props.workUnits ?? []).map((unit) => [unit.id, `${unit.code} — ${unit.name}`]),
));

function roleLabel(value) {
    return roleLabels.value[value] ?? value;
}

function workUnitLabel(id) {
    return id ? (workUnitLabels.value[id] ?? '—') : '—';
}

function confirmImport() {
    if (! window.confirm('Simpan seluruh baris valid sebagai pengguna baru?')) {
        return;
    }

    importing.value = true;
    router.post(route('admin.users.import'));
}
</script>
