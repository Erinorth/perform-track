<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { columns } from '@/features/organizational_risk/columns';
import DataTable from '@/features/organizational_risk/DataTable.vue';
import { useOrganizationalRiskData } from '@/features/organizational_risk/useOrganizationalRiskData';
import type { OrganizationalRisk } from '@/features/organizational_risk/organizational_risk';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-vue-next';

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

const navigateToCreate = () => {
  router.visit('/organizational-risks/create');
};
</script>

<template>
  <Head title="จัดการความเสี่ยงองค์กร" />

  <AppLayout :breadcrumbs="breadcrumbs">
      <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h1 class="text-2xl font-bold">จัดการความเสี่ยงองค์กร</h1>
            <p class="text-muted-foreground">รายการความเสี่ยงองค์กรทั้งหมดในระบบ</p>
          </div>
          <Button @click="navigateToCreate" class="flex items-center gap-2">
            <PlusIcon class="h-4 w-4" />
            <span>เพิ่มความเสี่ยงองค์กร</span>
          </Button>
        </div>
        <DataTable :columns="columns" :data="data" :meta="{ updateRiskStatus }" />
      </div>
  </AppLayout>
</template>