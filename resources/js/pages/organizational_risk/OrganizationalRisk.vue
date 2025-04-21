<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { columns } from '@/features/organizational_risk/columns';
import DataTable from '@/features/organizational_risk/DataTable.vue';
import { useOrganizationalRiskData } from '@/features/organizational_risk/useOrganizationalRiskData';
import type { OrganizationalRisk } from '@/features/organizational_risk/organizational_risk';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'จัดการความเสี่ยงองค์กร',
        href: '/organizational-risks',
    },
];

const props = defineProps<{
  risks: OrganizationalRisk[];
}>();

const { data, updateRiskStatus } = useOrganizationalRiskData(props.risks);
</script>

<template>
  <Head title="จัดการความเสี่ยงองค์กร" />

  <AppLayout :breadcrumbs="breadcrumbs">
      <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="mb-4">
          <h1 class="text-2xl font-bold">จัดการความเสี่ยงองค์กร</h1>
          <p class="text-muted-foreground">รายการความเสี่ยงองค์กรทั้งหมดในระบบ</p>
        </div>
        <DataTable :columns="columns" :data="data" :meta="{ updateRiskStatus }" />
      </div>
  </AppLayout>
</template>
