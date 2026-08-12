<template>
    <AdminLayout>
        <Head :title="'Keamanan Akun'" />

        <div class="mb-6">
            <h2 class="text-lg font-semibold text-foreground">Keamanan Akun</h2>
            <p class="text-sm text-slate-500">Kelola verifikasi dua langkah dan kata sandi akun Anda.</p>
        </div>

        <div class="card mb-6 p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl"
                         :class="user.two_factor_enabled ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-slate-500'">
                        <ShieldCheck :size="24" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-foreground">Verifikasi Dua Langkah (2FA)</h3>
                        <p class="text-xs text-slate-500">
                            {{ user.two_factor_active
                                ? 'Aktif — kode dari aplikasi autentikator diperlukan saat masuk.'
                                : (user.two_factor_setup ? 'Nonaktif.'
                                                         : (user.two_factor_enabled
                                                             ? 'Belum aktif — selesaikan pengaturan dengan memindai kode QR.'
                                                             : 'Nonaktif — login cukup dengan nama pengguna dan kata sandi.')) }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button v-if="user.two_factor_enabled && !user.two_factor_active" type="button" @click="enable2fa"
                            class="btn-primary">Selesaikan Pengaturan</button>
                    <button v-else-if="!user.two_factor_enabled" type="button" @click="enable2fa"
                            class="btn-primary">Aktifkan 2FA</button>
                    <button v-else type="button" @click="openDisable"
                            class="btn-secondary">Nonaktifkan 2FA</button>
                </div>
            </div>

            <div v-if="disableOpen" class="mt-4 rounded-lg border border-border bg-muted/50 p-4">
                <p class="mb-3 text-sm text-foreground">
                    Masukkan kode dari aplikasi autentikator untuk memastikan ini benar-benar Anda:
                </p>
                <form @submit.prevent="disable2fa" class="flex max-w-xs items-end gap-2">
                    <div class="flex-1">
                        <label class="label text-xs">Kode 6 digit</label>
                        <input v-model="disableForm.code" type="text" inputmode="numeric" maxlength="6" class="input" autocomplete="one-time-code">
                        <p v-if="disableForm.errors.code" class="error-text" role="alert">{{ disableForm.errors.code }}</p>
                    </div>
                    <button type="submit" :disabled="disableForm.processing" class="btn-secondary disabled:opacity-50">Nonaktifkan</button>
                </form>
            </div>
        </div>

        <div class="card p-6">
            <h3 class="mb-1 text-sm font-semibold text-foreground">Ganti Kata Sandi</h3>
            <p class="mb-4 text-xs text-slate-500">Gunakan minimal 8 karakter.</p>
            <form @submit.prevent="changePassword" class="grid max-w-lg grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label text-xs">Kata sandi saat ini</label>
                    <input v-model="pwForm.current_password" type="password" autocomplete="current-password" class="input">
                    <p v-if="pwForm.errors.current_password" class="error-text" role="alert">{{ pwForm.errors.current_password }}</p>
                </div>
                <div>
                    <label class="label text-xs">Kata sandi baru</label>
                    <input v-model="pwForm.password" type="password" autocomplete="new-password" class="input">
                    <p v-if="pwForm.errors.password" class="error-text" role="alert">{{ pwForm.errors.password }}</p>
                </div>
                <div>
                    <label class="label text-xs">Ulangi kata sandi baru</label>
                    <input v-model="pwForm.password_confirmation" type="password" autocomplete="new-password" class="input">
                </div>
                <button type="submit" :disabled="pwForm.processing" class="btn-primary disabled:opacity-50 sm:col-span-2">
                    Simpan Kata Sandi
                </button>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { ShieldCheck } from 'lucide-vue-next';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../Layouts/AdminLayout.vue';

const props = defineProps(['user']);

const disableOpen = ref(false);
const disableForm = useForm({ code: '' });

function openDisable() {
    if (!props.user.two_factor_setup) {
        router.post('/security/2fa/disable');
        return;
    }
    disableOpen.value = true;
}

function disable2fa() {
    disableForm.post('/security/2fa/disable', {
        preserveScroll: true,
        onSuccess: () => {
            disableOpen.value = false;
            disableForm.reset();
        },
    });
}

function enable2fa() {
    router.post('/security/2fa/enable');
}

const pwForm = useForm({ current_password: '', password: '', password_confirmation: '' });

function changePassword() {
    pwForm.post('/security/password', { preserveScroll: true, onSuccess: () => pwForm.reset() });
}
</script>
