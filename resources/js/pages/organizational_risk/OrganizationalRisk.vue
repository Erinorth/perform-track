<!-- 
  ไฟล์: resources\js\pages\organizational_risk\OrganizationalRisk.vue
  หน้าหลักสำหรับแสดงและจัดการข้อมูลความเสี่ยงระดับองค์กร
  รองรับการดูรายการ, เพิ่ม, แก้ไข, และเปลี่ยนสถานะความเสี่ยง
  ออกแบบให้รองรับการแสดงผลบนทุกขนาดหน้าจอ (Responsive Design)
-->

<script setup lang="ts">
// นำเข้า components และ utilities ที่จำเป็น
import AppLayout from '@/layouts/AppLayout.vue';         // Layout หลักของแอปพลิเคชัน
import { Head } from '@inertiajs/vue3';                  // Component สำหรับกำหนด <title> ของหน้า
import { type BreadcrumbItem } from '@/types';           // Type สำหรับ breadcrumb
import { columns } from '@/features/organizational_risk/columns';  // คอลัมน์สำหรับตาราง
import DataTable from '@/features/organizational_risk/DataTable.vue';  // Component ตาราง
import { useOrganizationalRiskData } from '@/features/organizational_risk/useOrganizationalRiskData';  // Composable function สำหรับจัดการข้อมูล
import type { OrganizationalRisk } from '@/features/organizational_risk/organizational_risk';  // Type ของความเสี่ยงองค์กร
import { Button } from '@/components/ui/button';         // Component ปุ่ม
import { PlusIcon } from 'lucide-vue-next';              // ไอคอนปุ่มเพิ่มข้อมูล
import OrganizationalRiskModal from './OrganizationalRiskModal.vue';  // Modal สำหรับเพิ่ม/แก้ไขข้อมูล
import { ref } from 'vue';                               // Ref API สำหรับข้อมูลแบบ reactive

// กำหนดข้อมูล breadcrumb สำหรับการนำทางในแอปพลิเคชัน
// แสดงเส้นทางการนำทางปัจจุบันให้ผู้ใช้ทราบว่าอยู่ที่หน้าใด
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'จัดการความเสี่ยงองค์กร',
        href: '/organizational-risks',
    },
];

// รับข้อมูลเริ่มต้นจาก props ที่ส่งมาจาก Inertia
const props = defineProps<{
  risks: OrganizationalRisk[];  // ข้อมูลความเสี่ยงองค์กรทั้งหมดจากเซิร์ฟเวอร์
}>();

// ใช้ composable function เพื่อจัดการข้อมูลความเสี่ยงองค์กร
// data: ข้อมูลความเสี่ยงแบบ reactive
// updateRiskStatus: ฟังก์ชันสำหรับอัปเดตสถานะความเสี่ยง
const { data, updateRiskStatus } = useOrganizationalRiskData(props.risks);

// ตัวแปรสำหรับจัดการสถานะ Modal และข้อมูลที่กำลังแก้ไข
const showModal = ref(false);                         // ควบคุมการแสดง/ซ่อน Modal
const currentRisk = ref<OrganizationalRisk | undefined>(undefined);  // ข้อมูลความเสี่ยงที่กำลังแก้ไข

// ฟังก์ชันเปิด Modal สำหรับเพิ่มข้อมูลใหม่
// กำหนดให้ currentRisk เป็น undefined เพื่อให้ Modal ทำงานในโหมดเพิ่มข้อมูล
const openCreateModal = () => {
  currentRisk.value = undefined;  // ไม่มีข้อมูลเดิม = สร้างใหม่
  showModal.value = true;         // แสดง Modal
};

// ฟังก์ชันเปิด Modal สำหรับแก้ไขข้อมูล
// กำหนด currentRisk เป็นข้อมูลที่ต้องการแก้ไข
const openEditModal = (risk: OrganizationalRisk) => {
  currentRisk.value = risk;       // กำหนดข้อมูลที่ต้องการแก้ไข
  showModal.value = true;         // แสดง Modal
};

// ฟังก์ชันจัดการเมื่อบันทึกข้อมูลสำเร็จ
// โหลดหน้าเว็บใหม่เพื่อให้แสดงข้อมูลล่าสุด
const handleSaved = () => {
  window.location.reload();       // โหลดหน้าใหม่เพื่ออัปเดตข้อมูล
};
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head title="จัดการความเสี่ยงองค์กร" />

  <!-- ใช้ Layout หลักพร้อมกำหนด breadcrumbs -->
  <AppLayout :breadcrumbs="breadcrumbs">
      <!-- พื้นที่หลักของหน้า ออกแบบให้รองรับ Responsive Design -->
      <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <!-- ส่วนหัวที่แสดงชื่อหน้าและปุ่มเพิ่มข้อมูล -->
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
        <!-- ส่งข้อมูลไปยัง DataTable พร้อมกับ meta ที่มีฟังก์ชันต่างๆ -->
        <!-- โครงสร้างคอลัมน์ -->
        <!-- ข้อมูลความเสี่ยง -->
        <!-- ฟังก์ชันที่ใช้ใน DataTable -->
        <DataTable 
          :columns="columns"                               
          :data="data"                                     
          :meta="{ updateRiskStatus, onEdit: openEditModal }"  
        />
        
        <!-- Modal สำหรับเพิ่ม/แก้ไขข้อมูล -->
        <!-- v-model:show ผูกกับตัวแปร showModal เพื่อควบคุมการแสดง/ซ่อน -->
        <!-- ผูกกับตัวแปร showModal -->
        <!-- ส่งข้อมูลที่กำลังแก้ไข -->
        <!-- รับ event เมื่อบันทึกสำเร็จ -->
        <OrganizationalRiskModal 
          v-model:show="showModal"        
          :risk="currentRisk"             
          @saved="handleSaved"            
        />
      </div>
  </AppLayout>
</template>
