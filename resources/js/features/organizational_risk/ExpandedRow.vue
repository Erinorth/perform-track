<!--
  ไฟล์: resources\js\features\organizational_risk\ExpandedRow.vue
  Component สำหรับแสดงข้อมูลเพิ่มเติมเมื่อขยายแถวในตารางความเสี่ยงระดับองค์กร
  แสดงรายละเอียดความเสี่ยงและความเสี่ยงระดับสายงานที่เกี่ยวข้อง
  รองรับการแสดงผลแบบ Responsive โดยใช้ grid layout
-->

<script setup lang="ts">
// นำเข้า types สำหรับโมเดลข้อมูล
import type { OrganizationalRisk } from './organizational_risk';
import type { DepartmentRisk } from '@/features/department_risk/department_risk';
import { computed } from 'vue';

// กำหนด props ที่ต้องการรับ: ข้อมูลแถวที่ขยาย
const props = defineProps<{
  rowData: OrganizationalRisk
}>();

// สร้าง computed property เพื่อตรวจสอบว่ามีความเสี่ยงระดับสายงานหรือไม่
const hasDepartmentRisks = computed(() => {
  return props.rowData.department_risks && props.rowData.department_risks.length > 0;
});

// เพิ่ม log เพื่อการตรวจสอบ
console.log('ExpandedRow: แสดงข้อมูลเพิ่มเติมสำหรับความเสี่ยง:', props.rowData.risk_name);
</script>

<template>
  <div class="p-4 bg-muted/30 rounded-md">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- ส่วนแสดงรายละเอียดความเสี่ยง -->
      <div>
        <h3 class="text-sm font-medium">รายละเอียดความเสี่ยง</h3>
        <p class="mt-1 whitespace-pre-wrap">{{ rowData.description }}</p>
      </div>
      
      <!-- ส่วนแสดงความเสี่ยงระดับสายงานที่เกี่ยวข้อง -->
      <div v-if="hasDepartmentRisks">
        <h3 class="text-sm font-medium">ความเสี่ยงระดับสายงานที่เกี่ยวข้อง</h3>
        <ul class="mt-1 space-y-1">
          <li 
            v-for="dept in (rowData.department_risks as DepartmentRisk[])" 
            :key="dept.id" 
            class="text-sm"
          >
            {{ dept.risk_name }}
          </li>
        </ul>
      </div>
      <!-- กรณีไม่มีความเสี่ยงระดับสายงานที่เกี่ยวข้อง -->
      <div v-else>
        <h3 class="text-sm font-medium">ความเสี่ยงระดับสายงานที่เกี่ยวข้อง</h3>
        <p class="text-sm text-muted-foreground mt-1">ไม่มีความเสี่ยงระดับสายงานที่เกี่ยวข้อง</p>
      </div>
    </div>
  </div>
</template>
