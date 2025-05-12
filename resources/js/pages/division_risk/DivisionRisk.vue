<!--
  ไฟล์: resources/js/pages/division_risk/DivisionRisk.vue
  
  คำอธิบาย: หน้าหลักสำหรับแสดงและจัดการข้อมูลความเสี่ยงระดับฝ่าย
  ฟีเจอร์หลัก:
  - แสดงรายการความเสี่ยงระดับฝ่ายในรูปแบบตาราง
  - สามารถเพิ่ม แก้ไข และลบข้อมูลความเสี่ยง
  - สามารถลบข้อมูลหลายรายการพร้อมกัน
  - รองรับการแสดงผลแบบ Responsive บนทุกขนาดหน้าจอ
-->

<script setup lang="ts">
// ==================== นำเข้า Layout และ Navigation ====================
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

// ==================== นำเข้า Types และ Data ====================
import { type BreadcrumbItem } from '@/types';
import type { DivisionRisk } from '@/types/types';

// ==================== นำเข้า Components ====================
import DataTable from '@/features/division_risk/DataTable.vue';
import DivisionRiskModal from './DivisionRiskModal.vue';
import HeaderSection from '@/features/division_risk/HeaderSection.vue';

// ==================== นำเข้า Composables และ Utilities ====================
import { columns } from '@/features/division_risk/columns';
import { useDivisionRiskActions } from '@/composables/useDivisionRiskActions';

// ==================== กำหนด Breadcrumbs ====================
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'จัดการความเสี่ยงฝ่าย',
        href: '/division-risks',
    },
];

// ==================== กำหนด Props ====================
const props = defineProps<{
  risks: DivisionRisk[];
  organizationalRisks?: Array<any>; // เพิ่ม prop เพื่อรับข้อมูลความเสี่ยงระดับองค์กร
}>();

// ==================== ใช้ Composable สำหรับจัดการ Actions ต่างๆ ====================
const {
  data,
  showModal,
  currentRisk,
  openCreateModal,
  openEditModal,
  handleSaved,
  handleDelete,
  handleBulkDelete
} = useDivisionRiskActions(props.risks);
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head title="จัดการความเสี่ยงฝ่าย" />

  <!-- ใช้ Layout หลักของแอปพลิเคชัน -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- พื้นที่หลักของหน้า -->
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- ส่วนหัวที่แยกเป็น Component ต่างหาก -->
      <HeaderSection 
        title="จัดการความเสี่ยงฝ่าย"
        description="รายการความเสี่ยงฝ่ายทั้งหมดในระบบ"
        @create="openCreateModal"
      />
      
      <!-- แสดงตารางข้อมูลความเสี่ยงฝ่าย -->
      <DataTable 
        :columns="columns"
        :data="data"
        :meta="{
          onEdit: openEditModal,
          onDelete: handleDelete,
          onBulkDelete: handleBulkDelete
        }"
      />

      <!-- Modal สำหรับเพิ่ม/แก้ไขข้อมูล -->
      <DivisionRiskModal 
        v-model:show="showModal"
        :risk="currentRisk"
        :organizational-risks="props.organizationalRisks"
        @saved="handleSaved"
      />
    </div>
  </AppLayout>
</template>
