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

                <div class="my-5 flex items-center gap-3">
                    <div class="h-px flex-1 bg-border"></div>
                    <span class="text-xs text-slate-400">atau</span>
                    <div class="h-px flex-1 bg-border"></div>
                </div>

                <a :href="route('auth.google')"
                   class="flex w-full items-center justify-center gap-2.5 rounded-md border border-border bg-surface px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-muted">
                    <svg width="18" height="18" viewBox="0 0 48 48">
                        <path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.3 6.1 29.4 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.6-.4-3.9z"/>
                        <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 18.9 12 24 12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.3 6.1 29.4 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
                        <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/>
                        <path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.1 5.7l6.2 5.2C36.9 39.2 44 34 44 24c0-1.3-.1-2.6-.4-3.9z"/>
                    </svg>
                    Masuk dengan Google
                </a>
                <p v-if="form.errors.login" class="mt-2 text-center text-sm text-red-600" role="alert">{{ form.errors.login }}</p>

                <p class="mt-8 text-center text-xs text-slate-400">
                    SIMQOH — Sistem Informasi Manajemen Qomarul Hidayah
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject } from 'vue';
import { Building2, FileCheck2, KeyRound, LogIn, QrCode, Users } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import { useTranslation } from '../../helpers/translation';

const route = inject('route');
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
