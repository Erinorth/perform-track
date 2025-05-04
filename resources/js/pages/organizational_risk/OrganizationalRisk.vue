<!--
  ไฟล์: resources\js\pages\organizational_risk\OrganizationalRisk.vue
  
  คำอธิบาย: หน้าหลักสำหรับแสดงและจัดการข้อมูลความเสี่ยงระดับองค์กร
  ฟีเจอร์หลัก:
  - แสดงรายการความเสี่ยงระดับองค์กรในรูปแบบตาราง
  - สามารถเพิ่ม แก้ไข และลบข้อมูลความเสี่ยง
  - สามารถลบข้อมูลหลายรายการพร้อมกัน
  - รองรับการแสดงผลแบบ Responsive บนทุกขนาดหน้าจอ
-->

<script setup lang="ts">
// ==================== นำเข้า Layout และ Navigation ====================
import AppLayout from '@/layouts/AppLayout.vue';         // Layout หลักของแอปพลิเคชัน
import { Head, router } from '@inertiajs/vue3';          // Component สำหรับกำหนด <title> และการนำทาง

// ==================== นำเข้า Types และ Data ====================
import { type BreadcrumbItem } from '@/types';           // Type สำหรับ breadcrumb
import type { OrganizationalRisk } from '@/features/organizational_risk/organizational_risk';  // Type ของความเสี่ยงองค์กร

// ==================== นำเข้า Components ====================
import { Button } from '@/components/ui/button';         // Component ปุ่ม
import DataTable from '@/features/organizational_risk/DataTable.vue';  // Component ตาราง
import OrganizationalRiskModal from './OrganizationalRiskModal.vue';  // Modal สำหรับเพิ่ม/แก้ไขข้อมูล

// ==================== นำเข้า Composables และ Utilities ====================
import { columns } from '@/features/organizational_risk/columns';  // คอลัมน์สำหรับตาราง
import { useOrganizationalRiskData } from '@/composables/useOrganizationalRiskData';  // Composable สำหรับจัดการข้อมูล
import { useConfirm } from '@/composables/useConfirm';   // สำหรับแสดง confirmation dialog
import { toast } from 'vue-sonner';                      // สำหรับแสดงข้อความแจ้งเตือน
import { ref } from 'vue';                               // Ref API สำหรับข้อมูลแบบ reactive

// ==================== นำเข้า Icons ====================
import { PlusIcon } from 'lucide-vue-next';              // ไอคอนปุ่มเพิ่มข้อมูล

// ==================== กำหนด Breadcrumbs ====================
// กำหนดข้อมูล breadcrumb สำหรับการนำทางในแอปพลิเคชัน
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'จัดการความเสี่ยงองค์กร',
        href: '/organizational-risks',
    },
];

// ==================== กำหนด Props ====================
// รับข้อมูลเริ่มต้นจาก props ที่ส่งมาจาก Inertia
const props = defineProps<{
  risks: OrganizationalRisk[];  // ข้อมูลความเสี่ยงองค์กรทั้งหมดจากเซิร์ฟเวอร์
}>();

// ==================== Composables & Reactive State ====================
// ใช้ composable สำหรับจัดการข้อมูลความเสี่ยงองค์กร
// - data: ข้อมูลความเสี่ยงแบบ reactive
// - updateRiskStatus: ฟังก์ชันสำหรับอัปเดตสถานะความเสี่ยง
// - deleteRisk: ฟังก์ชันสำหรับลบความเสี่ยง
// - bulkDeleteRisks: ฟังก์ชันสำหรับลบความเสี่ยงหลายรายการพร้อมกัน
const { data, updateRiskStatus, deleteRisk, bulkDeleteRisks } = useOrganizationalRiskData(props.risks);

// ตัวแปรสำหรับจัดการสถานะ Modal และข้อมูลที่กำลังแก้ไข
const showModal = ref(false);                         // ควบคุมการแสดง/ซ่อน Modal
const currentRisk = ref<OrganizationalRisk | undefined>(undefined);  // ข้อมูลความเสี่ยงที่กำลังแก้ไข

// ==================== Methods ====================
// ฟังก์ชันเปิด Modal สำหรับเพิ่มข้อมูลใหม่
const openCreateModal = () => {
  currentRisk.value = undefined;  // ไม่มีข้อมูลเดิม = สร้างใหม่
  showModal.value = true;         // แสดง Modal
};

// ฟังก์ชันเปิด Modal สำหรับแก้ไขข้อมูล
const openEditModal = (risk: OrganizationalRisk) => {
  currentRisk.value = risk;       // กำหนดข้อมูลที่ต้องการแก้ไข
  showModal.value = true;         // แสดง Modal
};

// ฟังก์ชันจัดการเมื่อบันทึกข้อมูลสำเร็จ
// โหลดหน้าเว็บใหม่เพื่อให้แสดงข้อมูลล่าสุด แต่รักษาตำแหน่งการเลื่อนหน้า
const handleSaved = () => {
  router.visit(route('organizational-risks.index'), {
    preserveScroll: true,  // รักษาตำแหน่งการเลื่อนหน้า
    only: ['risks']        // โหลดเฉพาะข้อมูลความเสี่ยงเท่านั้น
  });
};

// ฟังก์ชันสำหรับลบข้อมูลความเสี่ยง
// แสดง confirmation dialog ก่อนการลบข้อมูล
const handleDelete = (risk: OrganizationalRisk) => {
  // แสดง dialog ยืนยันการลบ
  useConfirm().confirm({
    title: 'ยืนยันการลบ',
    message: `คุณต้องการลบความเสี่ยง "${risk.risk_name}" ใช่หรือไม่?`,
    confirmText: 'ลบข้อมูล',
    cancelText: 'ยกเลิก',
    onConfirm: () => {
      // บันทึก log สำหรับการตรวจสอบ
      console.log('ยืนยันการลบความเสี่ยง:', risk.risk_name);
      
      // เรียกใช้ฟังก์ชันลบเมื่อยืนยัน
      deleteRisk(risk).catch((error) => {
        // แสดงข้อความแจ้งเตือนเมื่อเกิดข้อผิดพลาด
        toast.error('เกิดข้อผิดพลาดในการลบข้อมูล: ' + error.message);
      });
    },
    onCancel: () => {
      // บันทึก log สำหรับการตรวจสอบ
      console.log('ยกเลิกการลบความเสี่ยง:', risk.risk_name);
    }
  });
};

// ฟังก์ชันสำหรับลบข้อมูลหลายรายการพร้อมกัน
const handleBulkDelete = async (selectedIds) => {
  // แสดง dialog ยืนยันการลบ
  useConfirm().confirm({
    title: 'ยืนยันการลบข้อมูล',
    message: `คุณต้องการลบความเสี่ยงที่เลือกทั้ง ${selectedIds.length} รายการใช่หรือไม่?
              หมายเหตุ: การลบข้อมูลจะไม่สามารถเรียกคืนได้`,
    confirmText: 'ลบข้อมูล',
    cancelText: 'ยกเลิก',
    onConfirm: async () => {
      // เรียกใช้ฟังก์ชันลบหลายรายการเมื่อยืนยัน
      await bulkDeleteRisks(selectedIds);
    }
  });
};
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ (แสดงที่ tab ของ browser) -->
  <Head title="จัดการความเสี่ยงองค์กร" />

  <!-- ใช้ Layout หลักของแอปพลิเคชันพร้อมส่ง breadcrumbs ไปแสดง -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- พื้นที่หลักของหน้า ออกแบบให้รองรับ Responsive Design -->
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- ส่วนหัวที่แสดงชื่อหน้าและปุ่มเพิ่มข้อมูล -->
      <!-- ปรับ layout ให้เป็นแนวตั้งบนมือถือ (flex-col) และแนวนอนบนจอใหญ่ (sm:flex-row) -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <!-- ส่วนหัวข้อหน้า แสดงชื่อหน้าและคำอธิบาย -->
        <div>
          <h1 class="text-2xl font-bold">จัดการความเสี่ยงองค์กร</h1>
          <p class="text-muted-foreground">รายการความเสี่ยงองค์กรทั้งหมดในระบบ</p>
        </div>
        
        <!-- ปุ่มเพิ่มข้อมูล (Responsive: แบบเต็มจอบนมือถือ, แบบปกติบนจอใหญ่) -->
        <Button @click="openCreateModal" class="flex items-center gap-2 w-full sm:w-auto">
          <PlusIcon class="h-4 w-4" />
          <span>เพิ่มความเสี่ยงองค์กร</span>
        </Button>
      </div>
      
      <!-- แสดงตารางข้อมูลความเสี่ยงองค์กร -->
      <!-- ส่งข้อมูลและฟังก์ชันต่างๆ ไปยัง DataTable -->
      <DataTable 
        :columns="columns"
        :data="data"
        :meta="{
          updateRiskStatus,
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
    </div>
  </AppLayout>
</template>
