<!--
  ไฟล์: resources/js/pages/organizational_risk/OrganizationalRisk.vue
  
  คำอธิบาย: หน้าหลักสำหรับแสดงและจัดการข้อมูลความเสี่ยงระดับองค์กร
  อัปเดต: ใช้ HeaderWithTitle component ใหม่แทน HeaderSection
  ฟีเจอร์หลัก:
  - แสดงรายการความเสี่ยงระดับองค์กรในรูปแบบตาราง
  - สามารถเพิ่ม แก้ไข และลบข้อมูลความเสี่ยง
  - สามารถลบข้อมูลหลายรายการพร้อมกัน
  - รองรับการแสดงผลแบบ Responsive บนทุกขนาดหน้าจอ
-->

<script setup lang="ts">
// ==================== นำเข้า Layout และ Navigation ====================
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'

// ==================== นำเข้า Types และ Data ====================
import { type BreadcrumbItem } from '@/types'
import type { OrganizationalRisk } from '@/types'

// ==================== นำเข้า Components ====================
import DataTable from './data-table/DataTable.vue'
import OrganizationalRiskModal from './OrganizationalRiskModal.vue'
import HeaderWithTitle from '@/components/custom/HeaderWithTitle.vue'
import AlertConfirmDialog from '@/components/AlertConfirmDialog.vue'

// ==================== นำเข้า Composables และ Utilities ====================
import { columns } from './data-table/columns'
import { useOrganizationalRiskActions } from '@/composables/useOrganizationalRiskActions'
import { useConfirm } from '@/composables/useConfirm'

// ==================== กำหนด Breadcrumbs ====================
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'จัดการความเสี่ยงองค์กร',
    href: '/organizational-risks',
  },
]

// ==================== กำหนด Props ====================
const props = defineProps<{
  risks: OrganizationalRisk[]
}>()

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
} = useOrganizationalRiskActions(props.risks)

// ใช้ composable useConfirm เพื่อจัดการกับการแสดง confirm dialog และการตอบสนองต่อการกระทำของผู้ใช้
const { isOpen, options, isProcessing, handleConfirm, handleCancel } = useConfirm()

// Log สำหรับการตรวจสอบการโหลดหน้า
console.log('OrganizationalRisk: Component loaded with', props.risks?.length || 0, 'risks')
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head title="จัดการความเสี่ยงองค์กร" />

  <!-- ใช้ Layout หลักของแอปพลิเคชัน -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- พื้นที่หลักของหน้า -->
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:p-6">
      <!-- ใช้ HeaderWithTitle component ใหม่ที่รวม Title และ HeaderSection เข้าด้วยกัน -->
      <HeaderWithTitle
        title="จัดการความเสี่ยงองค์กร"
        description="รายการความเสี่ยงองค์กรทั้งหมดในระบบ พร้อมเครื่องมือในการจัดการและประเมินความเสี่ยง"
        size="lg"
        create-route="organizational-risks.create"
        :show-add-button="true"
        :show-separator="true"
        @quick-create="openCreateModal"
      >
        <!-- Slot สำหรับ Badge หรือข้อมูลสถิติ (ถ้าต้องการ) -->
        <template #actions>
          <!-- สามารถเพิ่ม Badge แสดงจำนวนข้อมูลได้ -->
          <!-- <Badge variant="secondary">{{ data.length }} รายการ</Badge> -->
        </template>
      </HeaderWithTitle>
      
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

<style scoped>
/* การปรับแต่งเพิ่มเติมสำหรับหน้านี้ */
.rounded-xl {
  border-radius: 0.75rem;
}

/* ปรับ padding สำหรับ mobile */
@media (max-width: 768px) {
  .p-4 {
    padding: 1rem;
  }
}
</style>
