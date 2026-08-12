<template>
    <AdminLayout>
        <Head :title="title" />
        <MasterCrud :title="title" :columns="columns" :fields="fields" :items="items" route-base="decree-types" />
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import MasterCrud from '../../../Components/MasterCrud.vue';

const title = 'Jenis Surat Keputusan';
const props = defineProps(['decreeTypes']);

const items = computed(() => {
    const data = Array.isArray(props.decreeTypes.data)
        ? props.decreeTypes.data
        : props.decreeTypes;

    return {
        ...props.decreeTypes,
        data: data.map((item) => ({
            ...item,
            consideration_weighing: Array.isArray(item.consideration_weighing)
                ? item.consideration_weighing.join('\n')
                : item.consideration_weighing,
        })),
    };
});

const columns = [
    { key: 'code', label: 'Kode' },
    { key: 'name', label: 'Nama' },
    { key: 'template_view', label: 'Template' },
    { key: 'number_format', label: 'Format Nomor' },
    { key: 'decrees_count', label: 'Jumlah SK' },
    { key: 'is_active', label: 'Status', badge: true },
];

const fields = [
    { key: 'code', label: 'Kode (mis. SK-PPT)' },
    { key: 'name', label: 'Nama' },
    { key: 'template_view', label: 'Template Blade (appointment, additional_duty, mutation, termination)' },
    { key: 'number_format', label: 'Format Nomor (token: {nomor} {kode_jenis} {kode_satker} {bulan_romawi} {bulan} {tahun} {tahun_pelajaran})' },
    { key: 'number_padding', label: 'Padding Nomor' },
    { key: 'consideration_recalling', label: 'Mengingat (teks)', type: 'textarea' },
    { key: 'consideration_weighing', label: 'Menimbang (satu butir per baris)', type: 'textarea' },
    { key: 'consideration_observing', label: 'Memperhatikan (teks)', type: 'textarea' },
    { key: 'requires_effective_date', label: 'Wajib TMT', type: 'checkbox', default: true },
    { key: 'is_active', label: 'Aktif', type: 'checkbox', default: true },
];
</script>
