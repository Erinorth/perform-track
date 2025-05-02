<!--
  ไฟล์: resources\js\features\department_risk\ExpandedRow.vue
  Component สำหรับแสดงข้อมูลเพิ่มเติมเมื่อขยายแถวในตารางความเสี่ยงระดับสายงาน
  แสดงรายละเอียดความเสี่ยง, ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง และการประเมินความเสี่ยง
  รองรับการแสดงผลแบบ Responsive โดยใช้ grid layout
-->

<script setup lang="ts">
// นำเข้า types สำหรับโมเดลข้อมูล
import type { OrganizationalRisk, DepartmentRisk, RiskAssessment } from '@/types/types';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
// นำเข้า icons จาก Lucide
import { 
  Calendar, 
  ClipboardList, 
  AlertTriangle, 
  Network, 
  CalendarDays, 
  ArrowUpRight,
  BarChart,
  ThermometerIcon
} from 'lucide-vue-next';

// กำหนด props ที่ต้องการรับ: ข้อมูลแถวที่ขยาย
const props = defineProps<{
  rowData: DepartmentRisk
}>();

// สร้าง computed property เพื่อตรวจสอบว่ามีความเสี่ยงระดับองค์กรที่เกี่ยวข้องหรือไม่
const hasOrganizationalRisk = computed(() => {
  return props.rowData.organizational_risk !== null && props.rowData.organizational_risk !== undefined;
});

// สร้าง computed property เพื่อตรวจสอบว่ามีข้อมูลการประเมินความเสี่ยงหรือไม่
const hasRiskAssessments = computed(() => {
  return props.rowData.risk_assessments && props.rowData.risk_assessments.length > 0;
});

// จำนวนการประเมินความเสี่ยงที่เกี่ยวข้อง
const riskAssessmentsCount = computed(() => {
  return hasRiskAssessments.value ? props.rowData.risk_assessments?.length : 0;
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

// เมธอดสำหรับการดูรายละเอียดความเสี่ยงระดับองค์กร
const viewOrganizationalRiskDetails = (risk: OrganizationalRisk) => {
  // แสดง toast เมื่อคลิกดูรายละเอียด
  toast.info(`รายละเอียดความเสี่ยงองค์กร: ${risk.risk_name}`, {
    description: risk.description || 'ไม่มีคำอธิบายเพิ่มเติม',
    duration: 5000
  });
  
  // เพิ่ม log เพื่อการตรวจสอบ
  console.log('ดูรายละเอียดความเสี่ยงระดับองค์กร:', risk);
};

// เมธอดสำหรับการดูรายละเอียดการประเมินความเสี่ยง
const viewAssessmentDetails = (assessment: RiskAssessment) => {
  // แสดง toast เมื่อคลิกดูรายละเอียด
  toast.info(`รายละเอียดการประเมิน: ${new Date(assessment.assessment_date).toLocaleDateString('th-TH')}`, {
    description: assessment.notes || 'ไม่มีหมายเหตุเพิ่มเติม',
    duration: 5000
  });
  
  // เพิ่ม log เพื่อการตรวจสอบ
  console.log('ดูรายละเอียดการประเมินความเสี่ยง:', assessment);
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

// ฟังก์ชันสำหรับแปลงค่าระดับความเสี่ยง (1-4) เป็นข้อความ
const getRiskLevelText = (level: number) => {
  switch (level) {
    case 1: return 'ต่ำ';
    case 2: return 'ปานกลาง';
    case 3: return 'สูง';
    case 4: return 'สูงมาก';
    default: return 'ไม่ระบุ';
  }
};

// ฟังก์ชันสำหรับแปลงค่าระดับความเสี่ยง (1-4) เป็นสี
const getRiskLevelColor = (level: number) => {
  switch (level) {
    case 1: return 'bg-green-100 text-green-800';
    case 2: return 'bg-yellow-100 text-yellow-800';
    case 3: return 'bg-orange-100 text-orange-800';
    case 4: return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};
</script>

<template>
  <div class="p-4 bg-muted/30 rounded-md overflow-hidden transition-all duration-300">
    <!-- แสดง loading state -->
    <div v-if="isLoading" class="flex justify-center items-center py-4">
      <div class="animate-spin w-6 h-6 border-2 border-primary border-t-transparent rounded-full"></div>
      <span class="ml-2 text-sm">กำลังโหลดข้อมูล...</span>
    </div>
    
    <!-- แสดงข้อมูลเมื่อโหลดเสร็จแล้ว -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- ส่วนแสดงรายละเอียดความเสี่ยง -->
      <div class="space-y-3">
        <div class="flex items-start space-x-2">
          <ClipboardList class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div>
            <h3 class="text-sm font-medium">รายละเอียดความเสี่ยง</h3>
            <p class="mt-1 text-sm whitespace-pre-wrap">{{ rowData.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
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
      
      <!-- ส่วนแสดงความเสี่ยงระดับองค์กรที่เกี่ยวข้องและการประเมินความเสี่ยง -->
      <div class="space-y-5">
        <!-- ส่วนความเสี่ยงระดับองค์กรที่เกี่ยวข้อง -->
        <div class="flex items-start space-x-2">
          <ArrowUpRight class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="w-full">
            <h3 class="text-sm font-medium">ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง</h3>
            
            <!-- กรณีมีความเสี่ยงระดับองค์กร -->
            <div v-if="hasOrganizationalRisk" class="mt-2">
              <div class="text-sm bg-background rounded-md p-2 border border-border">
                <div class="flex items-start justify-between">
                  <div class="flex items-start space-x-2">
                    <Network class="h-4 w-4 text-muted-foreground mt-0.5 flex-shrink-0" />
                    <div>
                      <p class="font-medium">{{ rowData.organizational_risk.risk_name }}</p>
                      <p v-if="rowData.organizational_risk.description" class="text-xs text-muted-foreground mt-1 line-clamp-2">
                        {{ rowData.organizational_risk.description }}
                      </p>
                    </div>
                  </div>
                  
                  <!-- ปุ่มดูรายละเอียด -->
                  <button 
                    @click="viewOrganizationalRiskDetails(rowData.organizational_risk)"
                    class="text-xs text-primary hover:text-primary/80 transition-colors"
                  >
                    ดูรายละเอียด
                  </button>
                </div>
              </div>
            </div>
            
            <!-- กรณีไม่มีความเสี่ยงระดับองค์กร -->
            <div v-else class="mt-2 flex items-center space-x-2 text-sm text-muted-foreground">
              <AlertTriangle class="h-4 w-4" />
              <p>ไม่ได้เชื่อมโยงกับความเสี่ยงระดับองค์กร</p>
            </div>
          </div>
        </div>
        
        <!-- ส่วนการประเมินความเสี่ยง -->
        <div class="flex items-start space-x-2">
          <BarChart class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="w-full">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium">การประเมินความเสี่ยง</h3>
              <span v-if="hasRiskAssessments" class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full">
                {{ riskAssessmentsCount }} รายการ
              </span>
            </div>
            
            <!-- กรณีมีข้อมูลการประเมินความเสี่ยง -->
            <div v-if="hasRiskAssessments" class="mt-2">
              <ul class="space-y-2">
                <li 
                  v-for="assessment in (rowData.risk_assessments as RiskAssessment[])" 
                  :key="assessment.id" 
                  class="text-sm bg-background rounded-md p-2 border border-border"
                >
                  <div class="flex items-start justify-between">
                    <div class="w-full space-y-2">
                      <div class="flex justify-between items-start">
                        <p class="font-medium">
                          วันที่ประเมิน: {{ new Date(assessment.assessment_date).toLocaleDateString('th-TH') }}
                        </p>
                        
                        <!-- ปุ่มดูรายละเอียด -->
                        <button 
                          @click="viewAssessmentDetails(assessment)"
                          class="text-xs text-primary hover:text-primary/80 transition-colors"
                        >
                          ดูรายละเอียด
                        </button>
                      </div>
                      
                      <div class="flex flex-wrap gap-2">
                        <!-- ระดับโอกาสเกิด -->
                        <div class="flex items-center space-x-1">
                          <span class="text-xs text-muted-foreground">โอกาสเกิด:</span>
                          <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', getRiskLevelColor(assessment.likelihood_level)]">
                            {{ getRiskLevelText(assessment.likelihood_level) }} ({{ assessment.likelihood_level }})
                          </span>
                        </div>
                        
                        <!-- ระดับผลกระทบ -->
                        <div class="flex items-center space-x-1">
                          <span class="text-xs text-muted-foreground">ผลกระทบ:</span>
                          <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', getRiskLevelColor(assessment.impact_level)]">
                            {{ getRiskLevelText(assessment.impact_level) }} ({{ assessment.impact_level }})
                          </span>
                        </div>
                        
                        <!-- ระดับความเสี่ยง (คะแนน) -->
                        <div class="flex items-center space-x-1">
                          <ThermometerIcon class="h-3 w-3 text-muted-foreground" />
                          <span class="text-xs text-muted-foreground">ระดับความเสี่ยง:</span>
                          <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', getRiskLevelColor(assessment.risk_score)]">
                            {{ assessment.risk_score }}
                          </span>
                        </div>
                      </div>
                      
                      <!-- หมายเหตุ (ถ้ามี) -->
                      <p v-if="assessment.notes" class="text-xs text-muted-foreground mt-1">
                        หมายเหตุ: {{ assessment.notes }}
                      </p>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
            
            <!-- กรณีไม่มีข้อมูลการประเมินความเสี่ยง -->
            <div v-else class="mt-2 flex items-center space-x-2 text-sm text-muted-foreground">
              <AlertTriangle class="h-4 w-4" />
              <p>ยังไม่มีการประเมินความเสี่ยงนี้</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
