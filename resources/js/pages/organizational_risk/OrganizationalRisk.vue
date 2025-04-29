<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { columns } from '@/features/organizational_risk/columns';
import DataTable from '@/features/organizational_risk/DataTable.vue';
import { useOrganizationalRiskData } from '@/features/organizational_risk/useOrganizationalRiskData';
import type { OrganizationalRisk } from '@/features/organizational_risk/organizational_risk';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-vue-next';
import OrganizationalRiskModal from './OrganizationalRiskModal.vue';
import { ref } from 'vue';

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

// เพิ่มตัวแปรสำหรับจัดการ Modal
const showModal = ref(false);
const currentRisk = ref<OrganizationalRisk | undefined>(undefined);

// เปิด Modal สำหรับเพิ่มข้อมูลใหม่
const openCreateModal = () => {
  currentRisk.value = undefined;
  showModal.value = true;
};

// เปิด Modal สำหรับแก้ไขข้อมูล
const openEditModal = (risk: OrganizationalRisk) => {
  currentRisk.value = risk;
  showModal.value = true;
};

// เมื่อบันทึกข้อมูลสำเร็จ ให้โหลดหน้าใหม่
const handleSaved = () => {
  window.location.reload();
};
</script>

<template>
  <Head title="จัดการความเสี่ยงองค์กร" />

  <AppLayout :breadcrumbs="breadcrumbs">
      <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
          <div>
            <h1 class="text-2xl font-bold">จัดการความเสี่ยงองค์กร</h1>
            <p class="text-muted-foreground">รายการความเสี่ยงองค์กรทั้งหมดในระบบ</p>
          </div>
          <Button @click="openCreateModal" class="flex items-center gap-2 w-full sm:w-auto">
            <PlusIcon class="h-4 w-4" />
            <span>เพิ่มความเสี่ยงองค์กร</span>
          </Button>
        </div>
        <DataTable 
          :columns="columns" 
          :data="data"
          :meta="{ updateRiskStatus, onEdit: openEditModal }"
        />
        
        <!-- เพิ่ม Modal สำหรับเพิ่ม/แก้ไขข้อมูล -->
        <OrganizationalRiskModal 
          v-model:show="showModal"
          :risk="currentRisk"
          @saved="handleSaved"
        />
      </div>
  </AppLayout>
</template>