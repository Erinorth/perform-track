<!--
  ไฟล์: resources/js/features/organizational_risk/ExpandedRow.vue
  
  คำอธิบาย: Component สำหรับแสดงข้อมูลเพิ่มเติมเมื่อขยายแถวในตารางความเสี่ยงระดับองค์กร
  ฟีเจอร์หลัก:
  - แสดงรายละเอียดความเสี่ยงระดับองค์กรแบบเต็ม
  - แสดงวันที่สร้างและแก้ไขล่าสุดในรูปแบบวันที่ไทย
  - แสดงรายการความเสี่ยงระดับสายงานที่เกี่ยวข้อง
  - รองรับการแสดงผลแบบ Responsive ทั้งบนมือถือและหน้าจอขนาดใหญ่
  - มีสถานะ loading สำหรับการโหลดข้อมูล
-->

<script setup lang="ts">
// ==================== นำเข้า Types และ Interfaces ====================
// นำเข้า types สำหรับโมเดลข้อมูลความเสี่ยง
import type { OrganizationalRisk, DepartmentRisk } from '@/types/types';

// ==================== นำเข้า Vue Composition API ====================
import { computed, onMounted, ref } from 'vue';

// ==================== นำเข้า Utilities ====================
// นำเข้า toast notifications
import { toast } from 'vue-sonner';

// ==================== นำเข้า Icons ====================
// นำเข้า icons จาก Lucide ตามที่กำหนดในคำแนะนำโปรเจค
import { 
  Calendar,        // สำหรับแสดงปีงบประมาณ
  ClipboardList,   // สำหรับแสดงรายละเอียดความเสี่ยง
  AlertTriangle,   // สำหรับแสดงกรณีไม่มีข้อมูล
  Network,         // สำหรับแสดงความเชื่อมโยงกับความเสี่ยงระดับสายงาน
  CalendarDays,    // สำหรับแสดงข้อมูลวันที่
  Users            // สำหรับแสดงข้อมูลสายงาน
} from 'lucide-vue-next';

// ==================== กำหนด Props ====================
// กำหนด props ที่ต้องการรับ: ข้อมูลแถวที่ขยาย
const props = defineProps<{
  rowData: OrganizationalRisk // ข้อมูลความเสี่ยงระดับองค์กรจากแถวที่ถูกขยาย
}>();

// ==================== Computed Properties ====================
// ตรวจสอบว่ามีความเสี่ยงระดับสายงานที่เกี่ยวข้องหรือไม่
const hasDepartmentRisks = computed(() => {
  return props.rowData.department_risks && props.rowData.department_risks.length > 0;
});

// คำนวณจำนวนความเสี่ยงระดับสายงานที่เกี่ยวข้อง
const departmentRisksCount = computed(() => {
  return hasDepartmentRisks.value ? props.rowData.department_risks?.length : 0;
});

// จัดรูปแบบวันที่สร้างและแก้ไขล่าสุดเป็นรูปแบบวันที่ไทย
const formattedDates = computed(() => {
  // สร้างวันที่สร้างในรูปแบบไทย
  const created = props.rowData.created_at 
    ? new Date(props.rowData.created_at).toLocaleDateString('th-TH', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }) 
    : 'ไม่ระบุ';
  
  // สร้างวันที่แก้ไขล่าสุดในรูปแบบไทย
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

// ==================== Reactive States ====================
// สถานะแสดง loading เมื่อกำลังโหลดข้อมูล
const isLoading = ref(false);

// ==================== Methods ====================
// เมธอดสำหรับแสดงรายละเอียดความเสี่ยงระดับสายงานแบบ popup
const viewDepartmentRiskDetails = (risk: DepartmentRisk) => {
  // แสดง toast เมื่อคลิกดูรายละเอียด
  toast.info(`รายละเอียดความเสี่ยง: ${risk.risk_name}`, {
    description: risk.description || 'ไม่มีคำอธิบายเพิ่มเติม',
    duration: 5000 // แสดง toast เป็นเวลา 5 วินาที
  });
  
  // เพิ่ม log เพื่อการตรวจสอบตามที่กำหนดในคำแนะนำโปรเจค
  console.log('ดูรายละเอียดความเสี่ยงระดับสายงาน:', risk);
};

// ==================== Lifecycle Hooks ====================
// การทำงานเมื่อ component ถูกแสดง (mounted)
onMounted(() => {
  // เริ่มสถานะ loading
  isLoading.value = true;
  
  // จำลองการโหลดข้อมูลเพิ่มเติม (ในระบบจริงอาจมีการเรียก API)
  setTimeout(() => {
    // เมื่อโหลดเสร็จ ให้ปิดสถานะ loading
    isLoading.value = false;
    
    // เพิ่ม log เพื่อการตรวจสอบตามที่กำหนดในคำแนะนำโปรเจค
    console.log('ExpandedRow: โหลดข้อมูลเพิ่มเติมสำหรับความเสี่ยง:', props.rowData.risk_name);
  }, 300); // จำลองการโหลด 300ms
});
</script>

<template>
  <!-- คอนเทนเนอร์หลักของข้อมูลที่แสดงเมื่อขยายแถว -->
  <div class="p-4 bg-muted/30 rounded-md overflow-hidden transition-all duration-300 max-w-full">
    <!-- แสดงสถานะ loading ระหว่างรอข้อมูล -->
    <div v-if="isLoading" class="flex justify-center items-center py-4">
      <div class="animate-spin w-6 h-6 border-2 border-primary border-t-transparent rounded-full"></div>
      <span class="ml-2 text-sm">กำลังโหลดข้อมูล...</span>
    </div>
    
    <!-- แสดงข้อมูลหลังโหลดเสร็จ ใช้ grid สำหรับการแสดงผลแบบ Responsive -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- คอลัมน์ซ้าย: แสดงรายละเอียดความเสี่ยง -->
      <div class="space-y-3 overflow-hidden">
        <!-- ส่วนแสดงรายละเอียดคำอธิบายความเสี่ยง -->
        <div class="flex items-start space-x-2">
          <ClipboardList class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="min-w-0 w-full overflow-hidden">
            <h3 class="text-sm font-medium">รายละเอียดความเสี่ยง</h3>
            <p class="mt-1 text-sm whitespace-pre-wrap break-words">{{ rowData.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
          </div>
        </div>
        
        <!-- ส่วนแสดงปีงบประมาณ -->
        <div class="flex items-start space-x-2">
          <Calendar class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div>
            <h3 class="text-sm font-medium">ปีงบประมาณ</h3>
            <p class="mt-1 text-sm">{{ rowData.year }}</p>
          </div>
        </div>
        
        <!-- ส่วนแสดงข้อมูลวันที่สร้างและแก้ไขล่าสุด -->
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
      
      <!-- คอลัมน์ขวา: แสดงความเสี่ยงระดับสายงานที่เกี่ยวข้อง -->
      <div class="overflow-hidden">
        <div class="flex items-start space-x-2">
          <Network class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="w-full min-w-0 overflow-hidden">
            <!-- หัวข้อพร้อมตัวนับจำนวนรายการ -->
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium truncate">ความเสี่ยงระดับสายงานที่เกี่ยวข้อง</h3>
              <span v-if="hasDepartmentRisks" class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full flex-shrink-0">
                {{ departmentRisksCount }} รายการ
              </span>
            </div>
            
            <!-- กรณีมีความเสี่ยงระดับสายงาน แสดงรายการทั้งหมด -->
            <div v-if="hasDepartmentRisks" class="mt-2">
              <ul class="space-y-2">
                <li 
                  v-for="dept in (rowData.department_risks as DepartmentRisk[])" 
                  :key="dept.id" 
                  class="text-sm bg-background rounded-md p-2 border border-border overflow-hidden"
                >
                  <!-- แสดงชื่อและรายละเอียดย่อของความเสี่ยงระดับสายงาน -->
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
                    
                    <!-- ปุ่มดูรายละเอียดเพิ่มเติม -->
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
            
            <!-- กรณีไม่มีความเสี่ยงระดับสายงาน -->
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