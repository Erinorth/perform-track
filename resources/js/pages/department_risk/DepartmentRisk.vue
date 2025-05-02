<!-- 
  ไฟล์: resources\js\pages\department_risk\DepartmentRisk.vue
  หน้าหลักสำหรับแสดงและจัดการข้อมูลความเสี่ยงระดับสายงาน
  รองรับการดูรายการ, เพิ่ม, แก้ไข, และเปลี่ยนสถานะความเสี่ยง
  ออกแบบให้รองรับการแสดงผลบนทุกขนาดหน้าจอ (Responsive Design)
-->

<script setup lang="ts">
// นำเข้า components และ utilities ที่จำเป็น
import AppLayout from '@/layouts/AppLayout.vue';         // Layout หลักของแอปพลิเคชัน
import { Head, router } from '@inertiajs/vue3';          // Component สำหรับกำหนด <title> ของหน้า
import { type BreadcrumbItem } from '@/types';           // Type สำหรับ breadcrumb
import { columns } from '@/features/department_risk/columns';  // คอลัมน์สำหรับตาราง
import DataTable from '@/features/department_risk/DataTable.vue';  // Component ตาราง
import { useDepartmentRiskData } from '@/composables/useDepartmentRiskData';  // Composable function สำหรับจัดการข้อมูล
import type { DepartmentRisk } from '@/features/department_risk/department_risk';  // Type ของความเสี่ยงสายงาน
import type { OrganizationalRisk } from '@/features/organizational_risk/organizational_risk';  // Type ของความเสี่ยงองค์กร
import { Button } from '@/components/ui/button';         // Component ปุ่ม
import { PlusIcon } from 'lucide-vue-next';              // ไอคอนปุ่มเพิ่มข้อมูล
import DepartmentRiskModal from './DepartmentRiskModal.vue';  // Modal สำหรับเพิ่ม/แก้ไขข้อมูล
import { ref } from 'vue';                               // Ref API สำหรับข้อมูลแบบ reactive
import { useConfirm } from '@/composables/useConfirm';   // เพิ่ม import สำหรับ confirmation dialog
import { toast } from 'vue-sonner';                      // สำหรับแสดงข้อความแจ้งเตือน

// กำหนดข้อมูล breadcrumb สำหรับการนำทางในแอปพลิเคชัน
// แสดงเส้นทางการนำทางปัจจุบันให้ผู้ใช้ทราบว่าอยู่ที่หน้าใด
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'จัดการความเสี่ยงสายงาน',
        href: '/department-risks',
    },
];

// รับข้อมูลเริ่มต้นจาก props ที่ส่งมาจาก Inertia
const props = defineProps<{
  risks: DepartmentRisk[];  // ข้อมูลความเสี่ยงสายงานทั้งหมดจากเซิร์ฟเวอร์
  organizationalRisks: OrganizationalRisk[];  // ข้อมูลความเสี่ยงองค์กรเพื่อใช้ในการเลือก
}>();

// ใช้ composable function เพื่อจัดการข้อมูลความเสี่ยงสายงาน
const { data, updateRiskStatus, deleteRisk } = useDepartmentRiskData(props.risks);

// ตัวแปรสำหรับจัดการสถานะ Modal และข้อมูลที่กำลังแก้ไข
const showModal = ref(false);                         // ควบคุมการแสดง/ซ่อน Modal
const currentRisk = ref<DepartmentRisk | undefined>(undefined);  // ข้อมูลความเสี่ยงที่กำลังแก้ไข

// ฟังก์ชันเปิด Modal สำหรับเพิ่มข้อมูลใหม่
// กำหนดให้ currentRisk เป็น undefined เพื่อให้ Modal ทำงานในโหมดเพิ่มข้อมูล
const openCreateModal = () => {
  currentRisk.value = undefined;  // ไม่มีข้อมูลเดิม = สร้างใหม่
  showModal.value = true;         // แสดง Modal
};

// ฟังก์ชันเปิด Modal สำหรับแก้ไขข้อมูล
// กำหนด currentRisk เป็นข้อมูลที่ต้องการแก้ไข
const openEditModal = (risk: DepartmentRisk) => {
  currentRisk.value = risk;       // กำหนดข้อมูลที่ต้องการแก้ไข
  showModal.value = true;         // แสดง Modal
};

// ฟังก์ชันจัดการเมื่อบันทึกข้อมูลสำเร็จ
// โหลดหน้าเว็บใหม่เพื่อให้แสดงข้อมูลล่าสุด
const handleSaved = () => {
  router.visit(route('department-risks.index'), {
    preserveScroll: true,
    only: ['risks']
  });
};

// เพิ่ม composable สำหรับยืนยันการลบ
const { confirm } = useConfirm();

// เพิ่มฟังก์ชันจัดการการลบข้อมูล
const handleDelete = (risk: DepartmentRisk) => {
  // แสดง dialog ยืนยันการลบ
  confirm({
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
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head title="จัดการความเสี่ยงสายงาน" />

  <!-- ใช้ Layout หลักพร้อมกำหนด breadcrumbs -->
  <AppLayout :breadcrumbs="breadcrumbs">
      <!-- พื้นที่หลักของหน้า ออกแบบให้รองรับ Responsive Design -->
      <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <!-- ส่วนหัวที่แสดงชื่อหน้าและปุ่มเพิ่มข้อมูล -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
          <!-- ส่วนหัวข้อหน้า แสดงชื่อหน้าและคำอธิบาย -->
          <div>
            <h1 class="text-2xl font-bold">จัดการความเสี่ยงสายงาน</h1>
            <p class="text-muted-foreground">รายการความเสี่ยงสายงานทั้งหมดในระบบ</p>
          </div>
          <!-- ปุ่มเพิ่มข้อมูล (Responsive: แบบเต็มจอบนมือถือ, แบบปกติบนจอใหญ่) -->
          <Button @click="openCreateModal" class="flex items-center gap-2 w-full sm:w-auto">
            <PlusIcon class="h-4 w-4" />
            <span>เพิ่มความเสี่ยงสายงาน</span>
          </Button>
        </div>
        
        <!-- แสดงตารางข้อมูลความเสี่ยงสายงาน -->
        <DataTable 
          :columns="columns"
          :data="data"
          :meta="{ updateRiskStatus, onEdit: openEditModal, onDelete: handleDelete }"
        />

        <!-- Modal สำหรับเพิ่ม/แก้ไขข้อมูล -->
        <DepartmentRiskModal 
          v-model:show="showModal"        
          :risk="currentRisk"             
          :organizational-risks="props.organizationalRisks"
          @saved="handleSaved"            
        />
      </div>
  </AppLayout>
</template>
