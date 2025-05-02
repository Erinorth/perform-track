<!--
  ไฟล์: resources\js\features\organizational_risk\ExpandedRow.vue
  Component สำหรับแสดงข้อมูลเพิ่มเติมเมื่อขยายแถวในตารางความเสี่ยงระดับองค์กร
  แสดงรายละเอียดความเสี่ยงและความเสี่ยงระดับสายงานที่เกี่ยวข้อง
  รองรับการแสดงผลแบบ Responsive โดยใช้ grid layout
-->

<script setup lang="ts">
// นำเข้า types สำหรับโมเดลข้อมูล
import type { OrganizationalRisk, DepartmentRisk } from './types/types';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
// นำเข้า icons จาก Lucide
import { 
  Calendar, 
  ClipboardList, 
  AlertTriangle, 
  Network, 
  CalendarDays, 
  Users 
} from 'lucide-vue-next';

// กำหนด props ที่ต้องการรับ: ข้อมูลแถวที่ขยาย
const props = defineProps<{
  rowData: OrganizationalRisk
}>();

// สร้าง computed property เพื่อตรวจสอบว่ามีความเสี่ยงระดับสายงานหรือไม่
const hasDepartmentRisks = computed(() => {
  return props.rowData.department_risks && props.rowData.department_risks.length > 0;
});

// จำนวนความเสี่ยงระดับสายงานที่เกี่ยวข้อง
const departmentRisksCount = computed(() => {
  return hasDepartmentRisks.value ? props.rowData.department_risks?.length : 0;
});

// สร้าง computed property สำหรับการจัดรูปแบบวันที่
const formattedDates = computed(() => {
  // สร้างวันที่จากข้อมูล timestamp
  const created = props.rowData.created_at 
    ? new Date(props.rowData.created_at).toLocaleDateString('th-TH', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }) 
    : 'ไม่ระบุ';
  
  const updated = props.rowData.updated_at 
    ? new Date(props.rowData.updated_at).toLocaleDateString('th-TH', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }) 
    : 'ไม่ระบุ';
  
  return { created, updated };
});

// สร้างตัวแปรสำหรับควบคุมการแสดง loading state
const isLoading = ref(false);

// เมธอดสำหรับการดูรายละเอียดความเสี่ยงระดับสายงาน
const viewDepartmentRiskDetails = (risk: DepartmentRisk) => {
  // แสดง toast เมื่อคลิกดูรายละเอียด
  toast.info(`รายละเอียดความเสี่ยง: ${risk.risk_name}`, {
    description: risk.description || 'ไม่มีคำอธิบายเพิ่มเติม',
    duration: 5000
  });
  
  // เพิ่ม log เพื่อการตรวจสอบ
  console.log('ดูรายละเอียดความเสี่ยงระดับสายงาน:', risk);
};

// ฟังก์ชันสำหรับการแสดงข้อมูลเมื่อ component โหลดเสร็จ
onMounted(() => {
  // แสดง loading state
  isLoading.value = true;
  
  // จำลองการโหลดข้อมูลเพิ่มเติม (ในระบบจริงอาจมีการเรียก API)
  setTimeout(() => {
    isLoading.value = false;
    
    // แสดง log เมื่อ component โหลดเสร็จ
    console.log('ExpandedRow: โหลดข้อมูลเพิ่มเติมสำหรับความเสี่ยง:', props.rowData.risk_name);
  }, 300);
});
</script>

<template>
  <div class="p-4 bg-muted/30 rounded-md overflow-hidden transition-all duration-300 max-w-full">
    <!-- แสดง loading state -->
    <div v-if="isLoading" class="flex justify-center items-center py-4">
      <div class="animate-spin w-6 h-6 border-2 border-primary border-t-transparent rounded-full"></div>
      <span class="ml-2 text-sm">กำลังโหลดข้อมูล...</span>
    </div>
    
    <!-- แสดงข้อมูลเมื่อโหลดเสร็จแล้ว -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- ส่วนแสดงรายละเอียดความเสี่ยง -->
      <div class="space-y-3 overflow-hidden">
        <div class="flex items-start space-x-2">
          <ClipboardList class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="min-w-0 w-full overflow-hidden">
            <h3 class="text-sm font-medium">รายละเอียดความเสี่ยง</h3>
            <p class="mt-1 text-sm whitespace-pre-wrap break-words">{{ rowData.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
          </div>
        </div>
        
        <!-- ข้อมูลเพิ่มเติม: ปี -->
        <div class="flex items-start space-x-2">
          <Calendar class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div>
            <h3 class="text-sm font-medium">ปีงบประมาณ</h3>
            <p class="mt-1 text-sm">{{ rowData.year }}</p>
          </div>
        </div>
        
        <!-- ข้อมูลวันที่สร้างและแก้ไข -->
        <div class="flex items-start space-x-2">
          <CalendarDays class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div>
            <h3 class="text-sm font-medium">ข้อมูลเวลา</h3>
            <div class="mt-1 text-sm space-y-1">
              <p>สร้างเมื่อ: {{ formattedDates.created }}</p>
              <p>แก้ไขล่าสุด: {{ formattedDates.updated }}</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- ส่วนแสดงความเสี่ยงระดับสายงานที่เกี่ยวข้อง -->
      <div class="overflow-hidden">
        <div class="flex items-start space-x-2">
          <Network class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="w-full min-w-0 overflow-hidden">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium truncate">ความเสี่ยงระดับสายงานที่เกี่ยวข้อง</h3>
              <span v-if="hasDepartmentRisks" class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full flex-shrink-0">
                {{ departmentRisksCount }} รายการ
              </span>
            </div>
            
            <!-- กรณีมีความเสี่ยงระดับสายงาน -->
            <div v-if="hasDepartmentRisks" class="mt-2">
              <ul class="space-y-2">
                <li 
                  v-for="dept in (rowData.department_risks as DepartmentRisk[])" 
                  :key="dept.id" 
                  class="text-sm bg-background rounded-md p-2 border border-border overflow-hidden"
                >
                  <div class="flex flex-col sm:flex-row items-start sm:justify-between gap-2">
                    <div class="flex items-start space-x-2 min-w-0 overflow-hidden">
                      <Users class="h-4 w-4 text-muted-foreground mt-0.5 flex-shrink-0" />
                      <div class="min-w-0 overflow-hidden">
                        <p class="font-medium truncate">{{ dept.risk_name }}</p>
                        <p v-if="dept.description" class="text-xs text-muted-foreground mt-1 line-clamp-2">
                          {{ dept.description }}
                        </p>
                      </div>
                    </div>
                    
                    <!-- ปุ่มดูรายละเอียด -->
                    <button 
                      @click="viewDepartmentRiskDetails(dept)"
                      class="text-xs text-primary hover:text-primary/80 transition-colors flex-shrink-0"
                    >
                      ดูรายละเอียด
                    </button>
                  </div>
                </li>
              </ul>
            </div>
            
            <!-- กรณีไม่มีความเสี่ยงระดับสายงาน (คงเดิม) -->
            <div v-else class="mt-2 flex items-center space-x-2 text-sm text-muted-foreground">
              <AlertTriangle class="h-4 w-4" />
              <p>ไม่มีความเสี่ยงระดับสายงานที่เกี่ยวข้อง</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>