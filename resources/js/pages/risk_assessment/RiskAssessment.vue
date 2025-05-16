<!--
  ไฟล์: resources/js/pages/risk_assessment/RiskAssessment.vue
  
  คำอธิบาย: หน้าหลักสำหรับแสดงและจัดการข้อมูลการประเมินความเสี่ยง
  ฟีเจอร์หลัก:
  - แสดงรายการการประเมินความเสี่ยงในรูปแบบตาราง
  - สามารถเพิ่ม แก้ไข และลบข้อมูลการประเมินความเสี่ยง
  - สามารถลบข้อมูลหลายรายการพร้อมกัน
  - รองรับการแสดงผลแบบ Responsive บนทุกขนาดหน้าจอ
-->

<script setup lang="ts">
// ==================== นำเข้า Layout และ Navigation ====================
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

// ==================== นำเข้า Types และ Data ====================
import { type BreadcrumbItem } from '@/types';
import type { RiskAssessment } from '@/types/types';

// ==================== นำเข้า Components ====================
import DataTable from '@/features/risk_assessment/DataTable.vue';
import RiskAssessmentModal from './RiskAssessmentModal.vue';
import HeaderSection from '@/features/risk_assessment/HeaderSection.vue';
// นำเข้า component สำหรับแสดง dialog ยืนยันการทำงานต่างๆ เช่น การลบข้อมูล
import AlertConfirmDialog from '@/components/AlertConfirmDialog.vue';
// นำเข้า composable สำหรับจัดการสถานะและการทำงานของ confirm dialog
import { useConfirm } from '@/composables/useConfirm';

// ==================== นำเข้า Composables และ Utilities ====================
import { columns } from '@/features/risk_assessment/columns';
import { useRiskAssessmentActions } from '@/composables/useRiskAssessmentActions';

// ==================== กำหนด Breadcrumbs ====================
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'การประเมินความเสี่ยง',
        href: '/risk-assessments',
    },
];

// ==================== กำหนด Props ====================
const props = defineProps<{
  assessments: RiskAssessment[];
  divisionRisks?: Array<any>; // เพิ่ม prop เพื่อรับข้อมูลความเสี่ยงฝ่าย
}>();

// ==================== ใช้ Composable สำหรับจัดการ Actions ต่างๆ ====================
const {
  data,
  showModal,
  currentAssessment,
  openCreateModal,
  openEditModal,
  handleSaved,
  handleDelete,
  handleBulkDelete
} = useRiskAssessmentActions(props.assessments);

// ใช้ composable useConfirm เพื่อจัดการกับการแสดง confirm dialog และการตอบสนองต่อการกระทำของผู้ใช้
const { isOpen, options, isProcessing, handleConfirm, handleCancel } = useConfirm();
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head title="การประเมินความเสี่ยง" />

  <!-- ใช้ Layout หลักของแอปพลิเคชัน -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- พื้นที่หลักของหน้า -->
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- ส่วนหัวที่แยกเป็น Component ต่างหาก -->
      <HeaderSection 
        title="การประเมินความเสี่ยง"
        description="รายการการประเมินความเสี่ยงทั้งหมดในระบบ"
        @create="openCreateModal"
      />
      
      <!-- แสดงตารางข้อมูลการประเมินความเสี่ยง -->
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
      <RiskAssessmentModal 
        v-model:show="showModal"
        :assessment="currentAssessment"
        :division-risks="props.divisionRisks"
        @saved="handleSaved"
      />

      <!-- AlertConfirmDialog สำหรับการยืนยันการทำงานที่สำคัญ -->
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
