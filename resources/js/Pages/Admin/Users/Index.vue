<template>
    <AdminLayout>
        <Head :title="'Pengguna'" />

        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Pengguna</h2>
            <button type="button" @click="openCreate"
                    class="btn-primary">
                Buat Pengguna
            </button>
        </div>

        <div class="table-wrap">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Nama</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Username</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Peran</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Satuan Kerja</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">2FA</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="user in users.data" :key="user.id">
                        <td class="px-4 py-3 text-gray-700">{{ user.name }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ user.username }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-primary-50 px-2 py-0.5 text-xs text-primary-600">
                                {{ roleLabel(user.role) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ user.work_unit?.name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                  class="rounded-full px-2 py-0.5 text-xs">
                                {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span :class="user.two_factor_active ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'"
                                  class="rounded-full px-2 py-0.5 text-xs">
                                {{ user.two_factor_active ? '2FA Aktif' : '2FA Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <button v-if="user.can_impersonate" type="button" class="text-purple-700 hover:underline" @click="impersonate(user)">
                                Masuk sebagai
                            </button>
                            <button type="button" class="text-primary-600 hover:underline" @click="openEdit(user)">Sunting</button>
                            <button v-if="!user.two_factor_enabled" type="button" class="text-blue-700 hover:underline" @click="toggleTwoFactor(user)">
                                Aktifkan 2FA
                            </button>
                            <button v-else-if="!user.two_factor_active" type="button" class="text-amber-700 hover:underline" @click="toggleTwoFactor(user)">
                                Batal Pengaturan 2FA
                            </button>
                            <button v-else type="button" class="text-blue-700 hover:underline" @click="toggleTwoFactor(user)">
                                Nonaktifkan 2FA
                            </button>
                            <button type="button" class="text-red-700 hover:underline" @click="resetTwoFactor(user)">Reset 2FA</button>
                            <button type="button" class="text-amber-700 hover:underline" @click="openReset(user)">Reset Sandi</button>
                            <button type="button" class="text-gray-500 hover:underline" @click="toggleActive(user)">
                                {{ user.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto card p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">{{ modal.title }}</h3>

                <form v-if="modal.kind === 'edit' || modal.kind === 'create'" @submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="label">Nama</label>
                        <input v-model="form.name" type="text" class="input">
                        <p v-if="form.errors.name" class="error-text" role="alert">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="label">Username</label>
                        <input v-model="form.username" type="text" class="input">
                        <p v-if="form.errors.username" class="error-text" role="alert">{{ form.errors.username }}</p>
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input v-model="form.email" type="email" class="input">
                        <p v-if="form.errors.email" class="error-text" role="alert">{{ form.errors.email }}</p>
                    </div>
                    <div v-if="modal.kind === 'create'">
                        <label class="label">Kata Sandi Awal</label>
                        <input v-model="form.password" type="text" class="input">
                        <p v-if="form.errors.password" class="error-text" role="alert">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="label">Peran</label>
                        <select v-model="form.role" class="input">
                            <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                        </select>
                        <p v-if="form.errors.role" class="error-text" role="alert">{{ form.errors.role }}</p>
                    </div>
                    <div>
                        <label class="label">Satuan Kerja (untuk Admin Satker)</label>
                        <select v-model="form.work_unit_id" class="input">
                            <option :value="null">—</option>
                            <option v-for="unit in workUnits" :key="unit.id" :value="unit.id">{{ unit.code }} — {{ unit.name }}</option>
                        </select>
                        <p v-if="form.errors.work_unit_id" class="error-text" role="alert">{{ form.errors.work_unit_id }}</p>
                    </div>
                    <label v-if="modal.kind === 'create'" class="flex items-center gap-2 text-sm text-gray-600">
                        <input v-model="form.must_change_password" type="checkbox" class="checkbox">
                        Wajib ganti kata sandi pada masuk pertama
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="btn-secondary">Batal</button>
                        <button type="submit" :disabled="form.processing" class="btn-primary disabled:opacity-50">Simpan</button>
                    </div>
                </form>

                <form v-else-if="modal.kind === 'reset'" @submit.prevent="resetPassword" class="space-y-4">
                    <p class="text-sm text-gray-600">Reset kata sandi untuk <b>{{ modal.user.name }}</b>. Pengguna wajib mengganti kata sandi pada masuk berikutnya.</p>
                    <div>
                        <label class="label">Kata Sandi Baru</label>
                        <input v-model="form.password" type="text" class="input">
                        <p v-if="form.errors.password" class="error-text" role="alert">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="label">Ulangi Kata Sandi</label>
                        <input v-model="form.password_confirmation" type="text" class="input">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modal = null" class="btn-secondary">Batal</button>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50">Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineProps(['users', 'roles', 'workUnits']);

const roleLabels = {
    foundation_head: 'Ketua Yayasan',
    foundation_admin: 'Admin Yayasan',
    unit_admin: 'Admin Satuan Kerja',
    employee: 'GTK',
};

function roleLabel(role) {
    return roleLabels[role] ?? role;
}

const modal = ref(null);
const form = useForm({
    name: '', username: '', email: '', password: '', password_confirmation: '',
    role: 'employee', work_unit_id: null, must_change_password: true,
});

function openCreate() {
    form.reset();
    form.clearErrors();
    modal.value = { kind: 'create', title: 'Buat Pengguna' };
}

function openEdit(user) {
    form.clearErrors();
    form.name = user.name;
    form.username = user.username;
    form.email = user.email;
    form.role = user.role;
    form.work_unit_id = user.work_unit_id;
    modal.value = { kind: 'edit', title: `Sunting — ${user.name}` };
}

function openReset(user) {
    form.clearErrors();
    form.password = '';
    form.password_confirmation = '';
    modal.value = { kind: 'reset', title: 'Reset Kata Sandi', user };
}

function save() {
    if (modal.value.kind === 'edit') {
        form.put(`/admin/users/${modal.value.user.id}`, { preserveScroll: true, onSuccess: () => { modal.value = null; } });
    } else {
        form.post('/admin/users', { preserveScroll: true, onSuccess: () => { modal.value = null; } });
    }
}

function resetPassword() {
    form.post(`/admin/users/${modal.value.user.id}/reset-password`, { preserveScroll: true, onSuccess: () => { modal.value = null; } });
}

function toggleActive(user) {
    useForm({}).post(`/admin/users/${user.id}/toggle-active`);
}

function toggleTwoFactor(user) {
    useForm({}).post(`/admin/users/${user.id}/toggle-2fa`);
}

function impersonate(user) {
    if (confirm(`Masuk sebagai ${user.name}? Gunakan "Kembali ke Akun Saya" untuk kembali.`)) {
        useForm({}).post(`/admin/users/${user.id}/impersonate`);
    }
}

function resetTwoFactor(user) {
    if (confirm(`Yakin reset 2FA untuk ${user.name}? Pengguna harus mengaktifkannya kembali.`)) {
        useForm({}).post(`/admin/users/${user.id}/reset-2fa`);
    }
}
</script>
