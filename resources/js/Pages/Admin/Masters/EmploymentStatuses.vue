<template>
    <AdminLayout>
        <Head :title="title" />
        <MasterCrud :title="title" :columns="columns" :fields="fields" :items="employmentStatuses" route-base="employment-statuses" />
    </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import MasterCrud from '../../../Components/MasterCrud.vue';

const title = 'Status Kepegawaian';
defineProps(['employmentStatuses']);

const columns = [
    { key: 'code', label: 'Kode' },
    { key: 'name', label: 'Nama Status' },
    { key: 'employees_count', label: 'Jumlah GTK' },
    { key: 'is_active', label: 'Status', badge: true },
];

const fields = [
    { key: 'code', label: 'Kode' },
    { key: 'name', label: 'Nama Status' },
    { key: 'is_active', label: 'Aktif', type: 'checkbox', default: true },
];
</script>
