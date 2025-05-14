<!--
  ไฟล์: resources/js/pages/organizational_risk/OrganizationalRisk.vue
  
  คำอธิบาย: หน้าหลักสำหรับแสดงและจัดการข้อมูลความเสี่ยงระดับองค์กร
  ฟีเจอร์หลัก:
  - แสดงรายการความเสี่ยงระดับองค์กรในรูปแบบตาราง
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
import type { OrganizationalRisk } from '@/types/types';

// ==================== นำเข้า Components ====================
import DataTable from '@/features/organizational_risk/DataTable.vue';
import OrganizationalRiskModal from './OrganizationalRiskModal.vue';
import HeaderSection from '@/features/organizational_risk/HeaderSection.vue';
// นำเข้า component สำหรับแสดง alert dialog ยืนยันการทำงานต่างๆ เช่น การลบข้อมูล
import AlertConfirmDialog from '@/components/AlertConfirmDialog.vue';
// นำเข้า composable สำหรับจัดการสถานะและการทำงานของ confirm dialog
import { useConfirm } from '@/composables/useConfirm';

// ==================== นำเข้า Composables และ Utilities ====================
import { columns } from '@/features/organizational_risk/columns';
import { useOrganizationalRiskActions } from '@/composables/useOrganizationalRiskActions';

// ==================== กำหนด Breadcrumbs ====================
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'จัดการความเสี่ยงองค์กร',
    href: '/organizational-risks',
  },
];

// ==================== กำหนด Props ====================
const props = defineProps<{
  risks: OrganizationalRisk[];
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
} = useOrganizationalRiskActions(props.risks);

// ใช้ composable useConfirm เพื่อจัดการกับการแสดง confirm dialog และการตอบสนองต่อการกระทำของผู้ใช้
const { isOpen, options, isProcessing, handleConfirm, handleCancel } = useConfirm();
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head title="จัดการความเสี่ยงองค์กร" />

  <!-- ใช้ Layout หลักของแอปพลิเคชัน -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- พื้นที่หลักของหน้า -->
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- ส่วนหัวที่แยกเป็น Component ต่างหาก -->
      <HeaderSection 
        title="จัดการความเสี่ยงองค์กร"
        description="รายการความเสี่ยงองค์กรทั้งหมดในระบบ"
        @create="openCreateModal"
      />
      
      <!-- แสดงตารางข้อมูลความเสี่ยงองค์กร -->
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
      <OrganizationalRiskModal 
        v-model:show="showModal"
        :risk="currentRisk"
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
