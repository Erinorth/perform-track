<!--
  ไฟล์: resources/js/pages/risk_control/data-table/ExpandedRow.vue
  
  คำอธิบาย: Component สำหรับแสดงข้อมูลเพิ่มเติมเมื่อขยายแถวในตารางการควบคุมความเสี่ยง
  ฟีเจอร์หลัก:
  - แสดงรายละเอียดการควบคุมความเสี่ยงแบบเต็ม
  - แสดงประเภทการควบคุมและรายละเอียดการดำเนินการ
  - แสดงวันที่สร้างและแก้ไขล่าสุดในรูปแบบวันที่ไทย
  - แสดงข้อมูลความเสี่ยงระดับฝ่ายที่เกี่ยวข้อง
  - รองรับการแสดงผลแบบ Responsive ทั้งบนมือถือและหน้าจอขนาดใหญ่
  - มีสถานะ loading สำหรับการโหลดข้อมูล
  - แสดงเอกสารแนบของการควบคุมความเสี่ยง
-->

<script setup lang="ts">
// ==================== นำเข้า Types และ Interfaces ====================
// นำเข้า types สำหรับโมเดลข้อมูลการควบคุมความเสี่ยง
import type { DivisionRisk, RiskControl, RiskControlAttachment } from '@/types/risk-control';

// ==================== นำเข้า Vue Composition API ====================
import { computed, onMounted, ref } from 'vue';

// ==================== นำเข้า Utilities ====================
// นำเข้า toast notifications
import { toast } from 'vue-sonner';

// ==================== นำเข้า Icons ====================
import { 
  Shield, ShieldCheck, ShieldAlert, Settings, Network, 
  CalendarDays, Users, Paperclip, Download, 
  FileText, FileImage, FileSpreadsheet, Eye,
  AlertCircle, CheckCircle2, User, Building2,
  Activity, Target, Wrench, AlertTriangle
} from 'lucide-vue-next';

// ==================== กำหนด Props ====================
// กำหนด props ที่ต้องการรับ: ข้อมูลแถวที่ขยาย
const props = defineProps<{
  rowData: RiskControl // ข้อมูลการควบคุมความเสี่ยงจากแถวที่ถูกขยาย
}>();

// ==================== Computed Properties ====================
// ตรวจสอบว่ามีความเสี่ยงระดับฝ่ายที่เกี่ยวข้องหรือไม่
const hasDivisionRisk = computed(() => {
  return props.rowData.division_risk !== null && props.rowData.division_risk !== undefined;
});

// ตรวจสอบว่ามีไฟล์แนบหรือไม่
const hasAttachments = computed(() => {
  return props.rowData.attachments && props.rowData.attachments.length > 0;
});

// คำนวณจำนวนไฟล์แนบ
const attachmentsCount = computed(() => {
  return hasAttachments.value ? props.rowData.attachments?.length : 0;
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

// คำนวณป้ายกำกับประเภทการควบคุม
const controlTypeInfo = computed(() => {
  const type = props.rowData.control_type;
  
  const typeMap = {
    'preventive': { 
      label: 'การป้องกัน', 
      class: 'bg-blue-100 text-blue-800',
      icon: Shield,
      description: 'การควบคุมที่ป้องกันไม่ให้เหตุการณ์เสี่ยงเกิดขึ้น'
    },
    'detective': { 
      label: 'การตรวจจับ', 
      class: 'bg-green-100 text-green-800',
      icon: Eye,
      description: 'การควบคุมที่ตรวจจับเหตุการณ์เสี่ยงหลังจากเกิดขึ้น'
    },
    'corrective': { 
      label: 'การแก้ไข', 
      class: 'bg-orange-100 text-orange-800',
      icon: Wrench,
      description: 'การควบคุมที่แก้ไขเหตุการณ์เสี่ยงหลังจากเกิดขึ้น'
    },
    'compensating': { 
      label: 'การชดเชย', 
      class: 'bg-purple-100 text-purple-800',
      icon: Target,
      description: 'การควบคุมที่ชดเชยเมื่อการควบคุมหลักไม่สามารถทำงานได้'
    }
  };
  
  return typeMap[type as keyof typeof typeMap] || { 
    label: 'ไม่ระบุ', 
    class: 'bg-gray-100 text-gray-800',
    icon: AlertCircle,
    description: 'ไม่ได้ระบุประเภทการควบคุม'
  };
});

// คำนวณสถานะการควบคุม
const statusInfo = computed(() => {
  const status = props.rowData.status;
  
  if (status === 'active') {
    return {
      label: 'ใช้งาน',
      class: 'bg-green-100 text-green-800',
      icon: ShieldCheck,
      description: 'การควบคุมนี้กำลังดำเนินการอยู่'
    };
  } else {
    return {
      label: 'ไม่ใช้งาน',
      class: 'bg-gray-100 text-gray-800',
      icon: ShieldAlert,
      description: 'การควบคุมนี้ไม่ได้ดำเนินการในขณะนี้'
    };
  }
});

// ==================== Reactive States ====================
// สถานะแสดง loading เมื่อกำลังโหลดข้อมูล
const isLoading = ref(false);

// ==================== Methods ====================
// ฟังก์ชันเลือกไอคอนตามประเภทไฟล์
const getFileIcon = (fileType: string) => {
  const type = fileType.toLowerCase();
  
  if (type.includes('pdf')) {
    return FileText;
  } else if (type.includes('xls') || type.includes('csv') || type.includes('sheet')) {
    return FileSpreadsheet;
  } else if (type.includes('image') || type.includes('jpg') || type.includes('png')) {
    return FileImage;
  } else {
    return FileText; // ไอคอนเริ่มต้น
  }
};

// ฟังก์ชันจัดรูปแบบขนาดไฟล์
const formatFileSize = (sizeInBytes?: number): string => {
  if (!sizeInBytes) return 'ไม่ระบุขนาด';
  
  if (sizeInBytes < 1024) {
    return `${sizeInBytes} B`;
  } else if (sizeInBytes < 1024 * 1024) {
    return `${(sizeInBytes / 1024).toFixed(2)} KB`;
  } else {
    return `${(sizeInBytes / (1024 * 1024)).toFixed(2)} MB`;
  }
};

// ฟังก์ชันดาวน์โหลดเอกสารแนบ
const downloadAttachment = (attachment: RiskControlAttachment) => {
  // แสดง toast แจ้งเตือนผู้ใช้
  toast.info('กำลังดาวน์โหลดเอกสาร', {
    description: `ไฟล์ ${attachment.filename} กำลังถูกดาวน์โหลด`,
    duration: 3000
  });
  
  // เพิ่ม log เพื่อการตรวจสอบ
  console.log('ดาวน์โหลดเอกสารแนบการควบคุมความเสี่ยง:', attachment);
  
  // เรียกใช้ API สำหรับดาวน์โหลดไฟล์
  window.open(`/risk-controls/${props.rowData.id}/attachments/${attachment.id}/download`, '_blank');
};

// เมธอดสำหรับแสดงรายละเอียดความเสี่ยงฝ่ายแบบ popup
const viewDivisionRiskDetails = (risk: DivisionRisk) => {
  // แสดง toast เมื่อคลิกดูรายละเอียด
  toast.info(`รายละเอียดความเสี่ยงฝ่าย: ${risk.risk_name}`, {
    description: risk.description || 'ไม่มีคำอธิบายเพิ่มเติม',
    duration: 5000 // แสดง toast เป็นเวลา 5 วินาที
  });
  
  // เพิ่ม log เพื่อการตรวจสอบตามที่กำหนดในคำแนะนำโปรเจค
  console.log('ดูรายละเอียดความเสี่ยงฝ่าย:', risk);
};

// ฟังก์ชันเปิดหน้าแสดงไฟล์แนบแบบเต็มจอ
const viewAttachmentFullScreen = (attachment: RiskControlAttachment) => {
  // เปิดหน้าแสดงไฟล์แนบในแท็บใหม่
  window.open(`/risk-controls/${props.rowData.id}/attachments/${attachment.id}/view`, '_blank');
  
  // เพิ่ม log เพื่อการตรวจสอบ
  console.log('เปิดหน้าดูไฟล์แนบการควบคุมความเสี่ยงแบบเต็มจอ:', attachment);
  
  // แสดง toast แจ้งผู้ใช้
  toast.info('กำลังเปิดไฟล์แนบ', {
    description: `กำลังเปิดไฟล์ ${attachment.filename}`,
    duration: 3000
  });
};

// ฟังก์ชันดูรายละเอียดการควบคุมแบบเต็ม
const viewControlDetails = () => {
  // นำทางไปยังหน้ารายละเอียดการควบคุมความเสี่ยง (ถ้ามี)
  const detailsUrl = `/risk-controls/${props.rowData.id}`;
  window.open(detailsUrl, '_blank');
  
  console.log('ดูรายละเอียดการควบคุมความเสี่ยงแบบเต็ม:', props.rowData);
  
  toast.info('กำลังเปิดหน้ารายละเอียด', {
    description: `การควบคุม "${props.rowData.control_name}"`,
    duration: 3000
  });
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
    console.log('ExpandedRow: โหลดข้อมูลเพิ่มเติมสำหรับการควบคุมความเสี่ยง:', props.rowData.id);
  }, 300); // จำลองการโหลด 300ms
});
</script>

<template>
  <div class="p-4 bg-muted/30 rounded-md overflow-hidden transition-all duration-300 max-w-full">
    <!-- แสดงสถานะ loading -->
    <div v-if="isLoading" class="flex justify-center items-center py-4">
      <div class="animate-spin w-6 h-6 border-2 border-primary border-t-transparent rounded-full"></div>
      <span class="ml-2 text-sm">กำลังโหลดข้อมูล...</span>
    </div>
    
    <!-- แสดงข้อมูลหลังโหลดเสร็จ -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- คอลัมน์ซ้าย: รายละเอียดการควบคุมความเสี่ยง -->
      <div class="space-y-3 overflow-hidden">
        <!-- ชื่อและรายละเอียดการควบคุม -->
        <div class="flex items-start space-x-2">
          <Shield class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="min-w-0 w-full overflow-hidden">
            <h3 class="text-sm font-medium">รายละเอียดการควบคุม</h3>
            <p class="mt-1 text-sm whitespace-pre-wrap break-words">{{ rowData.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
            
            <!-- ปุ่มดูรายละเอียดเต็ม -->
            <button 
              @click="viewControlDetails"
              class="mt-2 text-xs text-primary hover:text-primary/80 transition-colors"
            >
              ดูรายละเอียดเต็ม →
            </button>
          </div>
        </div>
        
        <!-- ประเภทการควบคุม -->
        <div class="flex items-start space-x-2">
          <component :is="controlTypeInfo.icon" class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="min-w-0 w-full overflow-hidden">
            <h3 class="text-sm font-medium">ประเภทการควบคุม</h3>
            <div class="mt-2">
              <div :class="`px-3 py-1 rounded-full text-xs inline-flex items-center gap-1 ${controlTypeInfo.class}`">
                <component :is="controlTypeInfo.icon" class="h-3 w-3" />
                {{ controlTypeInfo.label }}
              </div>
              <p class="mt-1 text-xs text-muted-foreground">{{ controlTypeInfo.description }}</p>
            </div>
          </div>
        </div>

        <!-- สถานะการควบคุม -->
        <div class="flex items-start space-x-2">
          <component :is="statusInfo.icon" class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="min-w-0 w-full overflow-hidden">
            <h3 class="text-sm font-medium">สถานะการควบคุม</h3>
            <div class="mt-2">
              <div :class="`px-3 py-1 rounded-full text-xs inline-flex items-center gap-1 ${statusInfo.class}`">
                <component :is="statusInfo.icon" class="h-3 w-3" />
                {{ statusInfo.label }}
              </div>
              <p class="mt-1 text-xs text-muted-foreground">{{ statusInfo.description }}</p>
            </div>
          </div>
        </div>
      
        <!-- ผู้รับผิดชอบ -->
        <div v-if="rowData.owner" class="flex items-start space-x-2">
          <User class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="min-w-0 w-full overflow-hidden">
            <h3 class="text-sm font-medium">ผู้รับผิดชอบ</h3>
            <div class="mt-1 flex items-center space-x-2">
              <Users class="h-4 w-4 text-muted-foreground" />
              <span class="text-sm">{{ rowData.owner }}</span>
            </div>
          </div>
        </div>

        <!-- รายละเอียดการดำเนินการ -->
        <div v-if="rowData.implementation_details" class="flex items-start space-x-2">
          <Settings class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="min-w-0 w-full overflow-hidden">
            <h3 class="text-sm font-medium">รายละเอียดการดำเนินการ</h3>
            <p class="mt-1 text-sm whitespace-pre-wrap break-words">{{ rowData.implementation_details }}</p>
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
      
      <!-- คอลัมน์ขวา: ความเสี่ยงระดับฝ่ายที่เกี่ยวข้อง -->
      <div class="overflow-hidden">
        <div class="flex items-start space-x-2">
          <Network class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="w-full min-w-0 overflow-hidden">
            <!-- หัวข้อพร้อมตัวนับจำนวนรายการ -->
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium truncate">ความเสี่ยงฝ่ายที่เกี่ยวข้อง</h3>
            </div>
            
            <!-- แสดงรายการความเสี่ยงฝ่าย -->
            <div v-if="hasDivisionRisk" class="mt-2">
              <ul class="space-y-2">
                <li class="text-sm bg-background rounded-md p-2 border border-border overflow-hidden">
                  <!-- รายละเอียดความเสี่ยงฝ่าย -->
                  <div class="flex flex-col sm:flex-row items-start sm:justify-between gap-2">
                    <div class="flex items-start space-x-2 min-w-0 overflow-hidden">
                      <Building2 class="h-4 w-4 text-muted-foreground mt-0.5 flex-shrink-0" />
                      <div class="min-w-0 overflow-hidden">
                        <p class="font-medium truncate">{{ rowData.division_risk?.risk_name }}</p>
                        <p v-if="rowData.division_risk?.description" class="text-xs text-muted-foreground mt-1 line-clamp-2">
                          {{ rowData.division_risk?.description }}
                        </p>
                        <!-- แสดงความเสี่ยงระดับองค์กร (ถ้ามี) -->
                        <p v-if="rowData.division_risk?.organizational_risk" class="text-xs text-muted-foreground mt-1">
                          องค์กร: {{ rowData.division_risk.organizational_risk.risk_name }}
                        </p>
                      </div>
                    </div>
                    
                    <!-- ปุ่มดูรายละเอียด -->
                    <button 
                      @click="viewDivisionRiskDetails(rowData.division_risk as DivisionRisk)"
                      class="text-xs text-primary hover:text-primary/80 transition-colors flex-shrink-0"
                    >
                      ดูรายละเอียด
                    </button>
                  </div>
                </li>
              </ul>
            </div>
            
            <!-- กรณีไม่มีความเสี่ยงฝ่าย -->
            <div v-else class="mt-2 flex items-center space-x-2 text-sm text-muted-foreground">
              <AlertTriangle class="h-4 w-4" />
              <p>ไม่มีข้อมูลความเสี่ยงฝ่ายที่เกี่ยวข้อง</p>
            </div>
          </div>
        </div>
        
        <!-- ส่วนเอกสารแนบ -->
        <div class="flex items-start space-x-2 mt-4">
          <Paperclip class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="min-w-0 w-full overflow-hidden">
            <!-- หัวข้อพร้อมตัวนับจำนวนรายการ -->
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium">เอกสารแนบ</h3>
              <span v-if="hasAttachments" class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full flex-shrink-0">
                {{ attachmentsCount }} ไฟล์
              </span>
            </div>
            
            <!-- กรณีมีเอกสารแนบ แสดงรายการทั้งหมด -->
            <div v-if="hasAttachments" class="mt-2">
              <ul class="space-y-2">
                <li 
                  v-for="attachment in (rowData.attachments as RiskControlAttachment[])" 
                  :key="attachment.id" 
                  class="text-sm bg-background rounded-md p-2 border border-border overflow-hidden"
                >
                  <!-- แสดงชื่อและรายละเอียดของเอกสารแนบ -->
                  <div class="flex flex-col sm:flex-row items-start sm:justify-between gap-2">
                    <div class="flex items-start space-x-2 min-w-0 overflow-hidden">
                      <component 
                        :is="getFileIcon(attachment.file_type || '')" 
                        class="h-4 w-4 text-muted-foreground mt-0.5 flex-shrink-0" 
                      />
                      <div class="min-w-0 overflow-hidden">
                        <p 
                          class="font-medium truncate cursor-pointer hover:text-primary transition-colors" 
                          @click="viewAttachmentFullScreen(attachment)"
                        >
                          {{ attachment.file_name }}
                        </p>
                        <p class="text-xs text-muted-foreground mt-1">
                          {{ formatFileSize(attachment.file_size) }} • 
                          อัปโหลดเมื่อ {{ new Date(attachment.created_at).toLocaleDateString('th-TH') }}
                        </p>
                      </div>
                    </div>
                    
                    <!-- ปุ่มดาวน์โหลด -->
                    <div class="flex gap-1 flex-shrink-0">
                      <button 
                        v-if="attachment.file_type && attachment.file_type.includes('pdf')"
                        @click="viewAttachmentFullScreen(attachment)"
                        class="text-xs text-primary hover:text-primary/80 transition-colors flex items-center gap-1"
                        title="ดูไฟล์"
                      >
                        <Eye class="h-3 w-3" />
                      </button>
                      <button 
                        @click="downloadAttachment(attachment)"
                        class="text-xs text-primary hover:text-primary/80 transition-colors flex items-center gap-1"
                        title="ดาวน์โหลดไฟล์"
                      >
                        <Download class="h-3 w-3" />
                      </button>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
            
            <!-- กรณีไม่มีเอกสารแนบ -->
            <div v-else class="mt-2 flex items-center space-x-2 text-sm text-muted-foreground">
              <AlertTriangle class="h-4 w-4" />
              <p>ไม่มีเอกสารแนบ</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animation สำหรับ loading */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* สไตล์สำหรับข้อความที่ยาวเกินไป */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* สไตล์สำหรับการ hover */
.hover-effect {
  transition: all 0.2s ease-in-out;
}

.hover-effect:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .grid-cols-1 {
    gap: 1rem;
  }
  
  .space-y-3 > * + * {
    margin-top: 0.75rem;
  }
}
</style>
