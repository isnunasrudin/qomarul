<template>
    <AdminLayout>
        <Head :title="title" />
        <MasterCrud :title="title" :columns="columns" :fields="fields" :items="items" route-base="additional-duties" />
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import MasterCrud from '../../../Components/MasterCrud.vue';

const title = 'Referensi Tugas Tambahan';
const props = defineProps(['additionalDuties', 'levels']);

const items = computed(() => {
    const data = Array.isArray(props.additionalDuties.data)
        ? props.additionalDuties.data
        : props.additionalDuties;

    return {
        ...props.additionalDuties,
        data: data.map((item) => ({
            ...item,
            applicable_levels: Array.isArray(item.applicable_levels)
                ? item.applicable_levels.join(',')
                : item.applicable_levels,
        })),
    };
});

const columns = [
    { key: 'code', label: 'Kode' },
    { key: 'name', label: 'Nama Tugas' },
    { key: 'hour_equivalence', label: 'Ekuivalensi Jam' },
    { key: 'requires_decree', label: 'Butuh SK' },
    { key: 'assignments_count', label: 'Penetapan' },
    { key: 'is_active', label: 'Status', badge: true },
];

const fields = [
    { key: 'code', label: 'Kode' },
    { key: 'name', label: 'Nama Tugas' },
    { key: 'applicable_levels', label: 'Jenjang Berlaku (pisahkan koma, mis. SD,SMP,SMK — kosongkan = semua)' },
    { key: 'hour_equivalence', label: 'Ekuivalensi Jam' },
    { key: 'quota_per_unit', label: 'Kuota per Satuan Kerja (kosong = tanpa batas)' },
    { key: 'requires_decree', label: 'Butuh SK Tersendiri', type: 'checkbox' },
    { key: 'is_active', label: 'Aktif', type: 'checkbox', default: true },
];
</script>
