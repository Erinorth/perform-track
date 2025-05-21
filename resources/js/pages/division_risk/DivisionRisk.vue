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
import DataTable from '@/pages/division_risk/data-table/DataTable.vue';
import DivisionRiskModal from './DivisionRiskModal.vue';
import HeaderSection from '@/pages/division_risk/data-table/HeaderSection.vue';
// นำเข้า component สำหรับแสดง dialog ยืนยันการทำงานต่างๆ เช่น การลบข้อมูล
import AlertConfirmDialog from '@/components/AlertConfirmDialog.vue';
// นำเข้า composable สำหรับจัดการสถานะและการทำงานของ confirm dialog
import { useConfirm } from '@/composables/useConfirm';

// ==================== นำเข้า Composables และ Utilities ====================
import { columns } from '@/pages/division_risk/data-table/columns';
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

// ใช้ composable useConfirm เพื่อจัดการกับการแสดง confirm dialog และการตอบสนองต่อการกระทำของผู้ใช้
const { isOpen, options, isProcessing, handleConfirm, handleCancel } = useConfirm();
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

      <!-- AlertConfirmDialog สำหรับการยืนยันการทำงานที่สำคัญ แทนที่ ConfirmDialog เดิม -->
      <AlertConfirmDialog
        v-model:show="isOpen"
        :title="options?.title || ''"
        :message="options?.message || ''"
        :confirm-text="options?.confirmText"
        :cancel-text="options?.cancelText"
        :processing="isProcessing"
        @confirm="handleConfirm"
        @cancel="handleCancel"
      />
    </div>
  </AppLayout>
</template>
