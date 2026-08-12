<template>
    <AdminLayout>
        <Head :title="title" />
        <MasterCrud :title="title" :columns="columns" :fields="fields" :items="workUnits" route-base="work-units" />
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import MasterCrud from '../../../Components/MasterCrud.vue';

const title = 'Satuan Kerja';
const props = defineProps(['workUnits', 'levels']);

const columns = [
    { key: 'code', label: 'Kode' },
    { key: 'name', label: 'Nama' },
    { key: 'level', label: 'Jenjang' },
    { key: 'employees_count', label: 'Jumlah GTK' },
    { key: 'is_active', label: 'Status', badge: true },
];

const fields = computed(() => [
    { key: 'code', label: 'Kode (mis. SD1, SMP, SMK, TPQ2)' },
    { key: 'name', label: 'Nama Satuan Kerja' },
    { key: 'level', label: 'Jenjang', type: 'select', options: props.levels ?? [] },
    { key: 'npsn', label: 'NPSN' },
    { key: 'head_name', label: 'Nama Kepala Satuan Kerja' },
    { key: 'head_nigy', label: 'NIGY Kepala' },
    { key: 'phone', label: 'Telepon' },
    { key: 'email', label: 'Email' },
    { key: 'address', label: 'Alamat', type: 'textarea' },
    { key: 'is_active', label: 'Aktif', type: 'checkbox', default: true },
]);
</script>
