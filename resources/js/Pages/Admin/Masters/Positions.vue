<template>
    <AdminLayout>
        <Head :title="title" />
        <MasterCrud :title="title" :columns="columns" :fields="fields" :items="positions" route-base="positions" />
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import MasterCrud from '../../../Components/MasterCrud.vue';

const title = 'Jabatan';
const props = defineProps(['positions', 'groups']);

const columns = [
    { key: 'code', label: 'Kode' },
    { key: 'name', label: 'Nama Jabatan' },
    { key: 'group', label: 'Kelompok' },
    { key: 'employees_count', label: 'Jumlah GTK' },
    { key: 'is_active', label: 'Status', badge: true },
];

const fields = computed(() => [
    { key: 'code', label: 'Kode' },
    { key: 'name', label: 'Nama Jabatan' },
    { key: 'group', label: 'Kelompok', type: 'select', options: props.groups ?? [] },
    { key: 'is_active', label: 'Aktif', type: 'checkbox', default: true },
]);
</script>
