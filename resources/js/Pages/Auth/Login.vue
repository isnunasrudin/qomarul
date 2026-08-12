<template>
    <div class="flex min-h-screen">
        <!-- Panel kiri: brand -->
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-sidebar p-10 text-white lg:flex">
            <div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-primary-600/20 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-primary-500/10 blur-3xl"></div>

            <div class="relative flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white">
                    <Building2 :size="22" />
                </div>
                <div>
                    <p class="text-lg font-bold">SIMQOH</p>
                    <p class="text-xs text-slate-400">Sistem Informasi Manajemen Qomarul Hidayah</p>
                </div>
            </div>

            <div class="relative">
                <h2 class="mb-3 text-2xl leading-snug font-semibold">
                    Kelola kepegawaian &amp; surat keputusan<br>yayasan dalam satu sistem.
                </h2>
                <p class="max-w-md text-sm text-slate-400">
                    Data GTK terpusat, SK terbit otomatis dengan penomoran aman, tanda tangan digital,
                    dan verifikasi publik melalui QR.
                </p>
                <div class="mt-8 space-y-3">
                    <div v-for="item in features" :key="item.label" class="flex items-center gap-3 text-sm text-slate-300">
                        <component :is="item.icon" :size="16" class="text-primary-400" />
                        {{ item.label }}
                    </div>
                </div>
            </div>

            <p class="relative text-xs text-slate-500">YPP Qomarul Hidayah · Gondang Tugu Trenggalek · Jawa Timur</p>
        </div>

        <!-- Panel kanan: form -->
        <div class="flex w-full items-center justify-center bg-background px-4 lg:w-1/2">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white">
                        <Building2 :size="22" />
                    </div>
                    <div>
                        <p class="text-lg font-bold text-foreground">SIMQOH</p>
                        <p class="text-xs text-slate-500">YPP Qomarul Hidayah</p>
                    </div>
                </div>

                <h1 class="mb-1 text-xl font-semibold text-foreground">Masuk</h1>
                <p class="mb-6 text-sm text-slate-500">Gunakan nama pengguna atau email Anda.</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">{{ t('auth.username') }}</label>
                        <input v-model="form.login" type="text" autofocus autocomplete="username" class="input">
                        <p v-if="form.errors.login" class="error-text" role="alert">{{ form.errors.login }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">{{ t('auth.password_label') }}</label>
                        <input v-model="form.password" type="password" autocomplete="current-password" class="input">
                        <p v-if="form.errors.password" class="error-text" role="alert">{{ form.errors.password }}</p>
                    </div>
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                        <input v-model="form.remember" type="checkbox" class="rounded border-border text-primary-600 focus:ring-primary-500">
                        {{ t('auth.remember') }}
                    </label>
                    <button type="submit" :disabled="form.processing" class="btn-primary w-full py-2.5">
                        <LogIn :size="16" />
                        {{ t('auth.login') }}
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-slate-400">
                    SIMQOH — Sistem Informasi Manajemen Qomarul Hidayah
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Building2, FileCheck2, KeyRound, LogIn, QrCode, Users } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import { useTranslation } from '../../helpers/translation';

const { t } = useTranslation();

const features = [
    { label: 'Data GTK terpusat seluruh satuan kerja', icon: Users },
    { label: 'SK terbit otomatis dengan penomoran aman', icon: FileCheck2 },
    { label: 'Tanda tangan digital & verifikasi QR', icon: QrCode },
    { label: 'Akses aman dengan verifikasi dua langkah', icon: KeyRound },
];

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login');
}
</script>
