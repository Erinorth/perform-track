<!--
  ไฟล์: resources/js/pages/risk_control/RiskControl.vue
  
  คำอธิบาย: หน้าหลักสำหรับแสดงและจัดการข้อมูลการควบคุมความเสี่ยง
  ฟีเจอร์หลัก:
  - แสดงรายการการควบคุมความเสี่ยงในรูปแบบตาราง
  - สามารถเพิ่ม แก้ไข และลบข้อมูลการควบคุมความเสี่ยง
  - สามารถลบข้อมูลหลายรายการพร้อมกัน
  - สามารถเปลี่ยนสถานะการควบคุมความเสี่ยง (active/inactive)
  - รองรับการแสดงผลแบบ Responsive บนทุกขนาดหน้าจอ
  - รองรับการจัดการเอกสารแนบ
-->

<script setup lang="ts">
// ==================== นำเข้า Layout และ Navigation ====================
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

// ==================== นำเข้า Types และ Data ====================
import { type BreadcrumbItem } from '@/types';
import type { RiskControl, DivisionRisk } from '@/types/risk-control';

// ==================== นำเข้า Components ====================
import DataTable from '@/pages/risk_control/data-table/DataTable.vue';
import RiskControlModal from './RiskControlModal.vue';
import HeaderSection from '@/pages/risk_control/data-table/HeaderSection.vue';
// นำเข้า component สำหรับแสดง dialog ยืนยันการทำงานต่างๆ เช่น การลบข้อมูล
import AlertConfirmDialog from '@/components/AlertConfirmDialog.vue';
// นำเข้า composable สำหรับจัดการสถานะและการทำงานของ confirm dialog
import { useConfirm } from '@/composables/useConfirm';

// ==================== นำเข้า Composables และ Utilities ====================
import { columns } from '@/pages/risk_control/data-table/columns';
import { useRiskControlActions } from '@/composables/useRiskControlActions';

import { onMounted, computed } from 'vue'
import { toast } from 'vue-sonner'

// ==================== กำหนด Breadcrumbs ====================
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'การควบคุมความเสี่ยง',
        href: '/risk-controls',
    },
];

// ==================== กำหนด Props ====================
const props = defineProps<{
  controls: RiskControl[];
  divisionRisks: DivisionRisk[]; // ข้อมูลความเสี่ยงระดับฝ่ายสำหรับใช้ในการสร้างการควบคุม
  statistics?: {
    total_controls: number;
    active_controls: number;
    inactive_controls: number;
    by_type: Record<string, number>;
  };
}>();

// ==================== ใช้ Composable สำหรับจัดการ Actions ต่างๆ ====================
const {
  data,
  showModal,
  showEditModal,
  currentControl,
  isProcessing,
  selectedItems,
  openCreateModal,
  openEditModal,
  closeModal,
  handleSaved,
  handleDelete,
  handleBulkDelete,
  handleToggleStatus,
  handleSelectionChange,
  getControlTypeLabel,
  refreshData
} = useRiskControlActions(props.controls);

// ใช้ composable useConfirm เพื่อจัดการกับการแสดง confirm dialog และการตอบสนองต่อการกระทำของผู้ใช้
const { isOpen, options, isProcessing: confirmProcessing, handleConfirm, handleCancel } = useConfirm();

// ==================== Computed Properties ====================
// ตรวจสอบว่ามีความเสี่ยงระดับฝ่ายหรือไม่
const hasRequiredData = computed(() => {
  return props.divisionRisks && props.divisionRisks.length > 0;
});

// คำนวณสถิติเพิ่มเติม
const enhancedStatistics = computed(() => {
  const total = props.statistics?.total_controls || 0;
  const active = props.statistics?.active_controls || 0;
  const activeRate = total > 0 ? Math.round((active / total) * 100) : 0;
  
  return {
    total_controls: total,
    active_controls: active,
    inactive_controls: (props.statistics?.inactive_controls || 0),
    by_type: props.statistics?.by_type || {},
    active_rate: activeRate,
    division_risks_count: props.divisionRisks.length
  };
});

// ==================== Event Handlers ====================
/**
 * จัดการเมื่อมีการเปิด Modal สำหรับสร้างการควบคุมใหม่
 */
const handleCreateModal = () => {
  if (!hasRequiredData.value) {
    toast.error('ไม่สามารถสร้างการควบคุมได้ เนื่องจากไม่มีข้อมูลความเสี่ยงระดับฝ่าย', {
      description: 'กรุณาเพิ่มข้อมูลความเสี่ยงระดับฝ่ายก่อน'
    });
    return;
  }
  
  openCreateModal();
};

/**
 * จัดการเมื่อบันทึกข้อมูลสำเร็จ
 */
const handleModalSaved = async () => {
  try {
    await handleSaved();
    
    // รีเฟรชข้อมูลสถิติ
    await refreshData();
    
    toast.success('บันทึกข้อมูลการควบคุมความเสี่ยงเรียบร้อยแล้ว');
    
  } catch (error) {
    console.error('เกิดข้อผิดพลาดหลังบันทึกข้อมูล:', error);
    toast.error('เกิดข้อผิดพลาดหลังบันทึกข้อมูล');
  }
};

/**
 * จัดการการลบข้อมูลการควบคุมความเสี่ยง
 */
const handleControlDelete = async (control: RiskControl) => {
  try {
    await handleDelete(control);
    
    // รีเฟรชข้อมูลสถิติ
    await refreshData();
    
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการลบ:', error);
  }
};

/**
 * จัดการการลบหลายรายการ
 */
const handleControlBulkDelete = async (selectedIds: number[]) => {
  try {
    await handleBulkDelete(selectedIds);
    
    // รีเฟรชข้อมูลสถิติ
    await refreshData();
    
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการลบหลายรายการ:', error);
  }
};

/**
 * จัดการการเปลี่ยนสถานะ
 */
const handleStatusToggle = async (control: RiskControl) => {
  try {
    await handleToggleStatus(control);
    
    // รีเฟรชข้อมูลสถิติ
    await refreshData();
    
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ:', error);
  }
};

/**
 * จัดการการดูรายละเอียด
 */
const handleViewDetails = (control: RiskControl) => {
  // นำทางไปยังหน้ารายละเอียด (ถ้ามี)
  console.log('ดูรายละเอียดการควบคุม:', control.control_name);
  
  // สามารถเพิ่มการนำทางหรือเปิด modal รายละเอียดได้
  toast.info(`รายละเอียด: ${control.control_name}`, {
    description: `ประเภท: ${getControlTypeLabel(control.control_type)} | สถานะ: ${control.status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน'}`
  });
};

// ==================== Lifecycle Hooks ====================
onMounted(() => {
  // ตรวจสอบข้อมูลความเสี่ยงระดับฝ่าย
  if (!hasRequiredData.value) {
    toast.warning('ไม่พบข้อมูลความเสี่ยงระดับฝ่าย กรุณาเพิ่มข้อมูลความเสี่ยงก่อนสร้างการควบคุม', {
      duration: 5000,
    });
    
    console.warn('ไม่พบข้อมูลความเสี่ยงระดับฝ่าย:', {
      division_risks_count: props.divisionRisks?.length || 0,
      controls_count: props.controls?.length || 0,
      timestamp: new Date().toISOString()
    });
  } else {
    // ตรวจสอบว่ามีการควบคุมความเสี่ยงที่เชื่อมโยงกับความเสี่ยงระดับฝ่ายหรือไม่
    const controlsWithoutRisk = props.controls.filter(control => !control.division_risk_id);
    
    if (controlsWithoutRisk.length > 0) {
      console.warn('พบการควบคุมความเสี่ยงที่ไม่มีการเชื่อมโยงกับความเสี่ยงระดับฝ่าย:', controlsWithoutRisk.length);
    }
    
    // แสดงข้อมูลสถิติเมื่อโหลดหน้า
    console.log('📊 โหลดหน้าการควบคุมความเสี่ยงสำเร็จ', {
      total_controls: enhancedStatistics.value.total_controls,
      active_controls: enhancedStatistics.value.active_controls,
      active_rate: enhancedStatistics.value.active_rate,
      division_risks: props.divisionRisks.length,
      control_types: Object.keys(enhancedStatistics.value.by_type),
      timestamp: new Date().toISOString()
    });
    
    if (enhancedStatistics.value.total_controls === 0) {
      toast.info('ยังไม่มีการควบคุมความเสี่ยงในระบบ เริ่มต้นสร้างการควบคุมใหม่', {
        duration: 4000,
      });
    }
    
    // ตรวจสอบการควบคุมที่ไม่ใช้งาน
    if (enhancedStatistics.value.inactive_controls > 0) {
      toast.warning(`มีการควบคุมความเสี่ยง ${enhancedStatistics.value.inactive_controls} รายการที่ไม่ใช้งาน`, {
        description: 'กรุณาตรวจสอบและอัปเดตสถานะการควบคุม',
        duration: 6000
      });
    }
  }
});
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head title="การควบคุมความเสี่ยง" />

  <!-- ใช้ Layout หลักของแอปพลิเคชัน -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- พื้นที่หลักของหน้า -->
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- ส่วนหัวที่แยกเป็น Component ต่างหาก -->
      <HeaderSection 
        title="การควบคุมความเสี่ยง"
        description="จัดการและติดตามการควบคุมความเสี่ยงขององค์กร"
        :can-create="hasRequiredData"
        :statistics="enhancedStatistics"
        @quickCreate="handleCreateModal"
        @refresh="refreshData"
      />
      
      <!-- แสดงตารางข้อมูลการควบคุมความเสี่ยง -->
      <DataTable 
        :columns="columns"
        :data="data"
        :is-loading="isProcessing"
        :selected-items="selectedItems"
        :meta="{
          onEdit: openEditModal,
          onDelete: handleControlDelete,
          onBulkDelete: handleControlBulkDelete,
          onToggleStatus: handleStatusToggle,
          onViewDetails: handleViewDetails,
          onSelectionChange: handleSelectionChange
        }"
      />

      <!-- Modal สำหรับเพิ่มข้อมูล -->
      <RiskControlModal 
        v-model:show="showModal"
        :control="undefined"
        :division-risks="props.divisionRisks"
        :is-edit="false"
        @saved="handleModalSaved"
        @cancel="closeModal"
      />

      <!-- Modal สำหรับแก้ไขข้อมูล -->
      <RiskControlModal 
        v-model:show="showEditModal"
        :control="currentControl"
        :division-risks="props.divisionRisks"
        :is-edit="true"
        @saved="handleModalSaved"
        @cancel="closeModal"
      />

      <!-- AlertConfirmDialog สำหรับการยืนยันการทำงานที่สำคัญ -->
      <AlertConfirmDialog
        v-model:show="isOpen"
        :title="options?.title || ''"
        :message="options?.message || ''"
        :confirm-text="options?.confirmText"
        :cancel-text="options?.cancelText"
        :processing="confirmProcessing"
        @confirm="handleConfirm"
        @cancel="handleCancel"
      />
    </div>
  </AppLayout>
</template>

<style scoped lang="postcss">
/* สไตล์เพิ่มเติมสำหรับหน้าการควบคุมความเสี่ยง */
.controls-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

@media (max-width: 768px) {
  .controls-grid {
    grid-template-columns: 1fr;
  }
}

/* Animation สำหรับการโหลด */
.loading-skeleton {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
  background-color: rgb(229 231 235); /* bg-gray-200 */
  border-radius: 0.25rem; /* rounded */
}

/* สไตล์สำหรับ toast notifications */
.risk-control-toast {
  background-color: white;
  border: 1px solid rgb(229 231 235); /* border-gray-200 */
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); /* shadow-lg */
  border-radius: 0.5rem; /* rounded-lg */
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .flex-col {
    gap: 0.75rem;
  }
  
  .p-4 {
    padding: 0.75rem;
  }
}

/* เพิ่ม keyframes สำหรับ pulse animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

/* สไตล์สำหรับสถานะการควบคุม */
.control-status-active {
  background-color: rgb(34 197 94); /* bg-green-500 */
  color: white;
}

.control-status-inactive {
  background-color: rgb(107 114 128); /* bg-gray-500 */
  color: white;
}

/* สไตล์สำหรับประเภทการควบคุม */
.control-type-preventive {
  background-color: rgb(59 130 246); /* bg-blue-500 */
}

.control-type-detective {
  background-color: rgb(34 197 94); /* bg-green-500 */
}

.control-type-corrective {
  background-color: rgb(249 115 22); /* bg-orange-500 */
}

.control-type-compensating {
  background-color: rgb(168 85 247); /* bg-purple-500 */
}
</style>
