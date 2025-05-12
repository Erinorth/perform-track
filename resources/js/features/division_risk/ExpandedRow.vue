<!--
  ไฟล์: resources/js/features/division_risk/ExpandedRow.vue
  
  คำอธิบาย: Component สำหรับแสดงข้อมูลเพิ่มเติมเมื่อขยายแถวในตารางความเสี่ยงระดับฝ่าย
  ฟีเจอร์หลัก:
  - แสดงรายละเอียดความเสี่ยงระดับฝ่ายแบบเต็ม
  - แสดงวันที่สร้างและแก้ไขล่าสุดในรูปแบบวันที่ไทย
  - แสดงความเสี่ยงระดับองค์กรที่เกี่ยวข้อง
  - รองรับการแสดงผลแบบ Responsive ทั้งบนมือถือและหน้าจอขนาดใหญ่
  - มีสถานะ loading สำหรับการโหลดข้อมูล
-->

<script setup lang="ts">
// ==================== นำเข้า Types และ Interfaces ====================
// นำเข้า types สำหรับโมเดลข้อมูลความเสี่ยง
import type { OrganizationalRisk, DivisionRisk, DivisionRiskAttachment } from '@/types/types';

// ==================== นำเข้า Vue Composition API ====================
import { computed, onMounted, ref } from 'vue';

// ==================== นำเข้า Utilities ====================
// นำเข้า toast notifications
import { toast } from 'vue-sonner';

// ==================== นำเข้า Icons ====================
// นำเข้า icons จาก Lucide ตามที่กำหนดในคำแนะนำโปรเจค
import { 
  ClipboardList, AlertTriangle, Network, 
  CalendarDays, Users, Paperclip, Download, 
  FileText, FileImage, FileSpreadsheet, Eye
} from 'lucide-vue-next';

// ==================== กำหนด Props ====================
// กำหนด props ที่ต้องการรับ: ข้อมูลแถวที่ขยาย
const props = defineProps<{
  rowData: DivisionRisk // ข้อมูลความเสี่ยงระดับฝ่ายจากแถวที่ถูกขยาย
}>();

// ==================== Computed Properties ====================
// ตรวจสอบว่ามีความเสี่ยงระดับองค์กรที่เกี่ยวข้องหรือไม่
const hasOrganizationalRisk = computed(() => {
  return props.rowData.organizational_risk !== null && props.rowData.organizational_risk !== undefined;
});

// คำนวณจำนวนความเสี่ยงระดับองค์กรที่เกี่ยวข้อง (จะเป็น 0 หรือ 1)
const organizationalRiskCount = computed(() => {
  return hasOrganizationalRisk.value ? 1 : 0;
});

// ตรวจสอบว่ามีเอกสารแนบหรือไม่
const hasAttachments = computed(() => {
  return props.rowData.attachments && props.rowData.attachments.length > 0;
});

// คำนวณจำนวนเอกสารแนบ
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
const formatFileSize = (sizeInBytes: number): string => {
  if (sizeInBytes < 1024) {
    return `${sizeInBytes} B`;
  } else if (sizeInBytes < 1024 * 1024) {
    return `${(sizeInBytes / 1024).toFixed(2)} KB`;
  } else {
    return `${(sizeInBytes / (1024 * 1024)).toFixed(2)} MB`;
  }
};

// ฟังก์ชันดาวน์โหลดเอกสารแนบ
const downloadAttachment = (attachment: DivisionRiskAttachment) => {
  // แสดง toast แจ้งเตือนผู้ใช้
  toast.info('กำลังดาวน์โหลดเอกสาร', {
    description: `ไฟล์ ${attachment.file_name} กำลังถูกดาวน์โหลด`,
    duration: 3000
  });
  
  // เพิ่ม log เพื่อการตรวจสอบ
  console.log('ดาวน์โหลดเอกสารแนบ:', attachment);
  
  // เรียกใช้ API สำหรับดาวน์โหลดไฟล์ด้วย route ใหม่
  window.open(`/division-risks/${props.rowData.id}/attachments/${attachment.id}/download`, '_blank');
};

// เมธอดสำหรับแสดงรายละเอียดความเสี่ยงระดับองค์กรแบบ popup
const viewOrganizationalRiskDetails = (risk: OrganizationalRisk) => {
  // แสดง toast เมื่อคลิกดูรายละเอียด
  toast.info(`รายละเอียดความเสี่ยง: ${risk.risk_name}`, {
    description: risk.description || 'ไม่มีคำอธิบายเพิ่มเติม',
    duration: 5000 // แสดง toast เป็นเวลา 5 วินาที
  });
  
  // เพิ่ม log เพื่อการตรวจสอบตามที่กำหนดในคำแนะนำโปรเจค
  console.log('ดูรายละเอียดความเสี่ยงระดับองค์กร:', risk);
};

// ฟังก์ชันเปิดหน้าแสดงไฟล์แนบแบบเต็มจอ
const viewAttachmentFullScreen = (attachment: DivisionRiskAttachment) => {
  // เปิดหน้าแสดงไฟล์แนบในแท็บใหม่โดยใช้ route ใหม่
  window.open(`/division-risks/${props.rowData.id}/attachments/${attachment.id}/view`, '_blank');
  
  // เพิ่ม log เพื่อการตรวจสอบ
  console.log('เปิดหน้าดูไฟล์แนบแบบเต็มจอ:', attachment);
  
  // แสดง toast แจ้งผู้ใช้
  toast.info('กำลังเปิดไฟล์แนบ', {
    description: `กำลังเปิดไฟล์ ${attachment.file_name}`,
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
    console.log('ExpandedRow: โหลดข้อมูลเพิ่มเติมสำหรับความเสี่ยง:', props.rowData.risk_name);
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
      <!-- คอลัมน์ซ้าย: รายละเอียดความเสี่ยง -->
      <div class="space-y-3 overflow-hidden">
        <!-- รายละเอียดความเสี่ยง -->
        <div class="flex items-start space-x-2">
          <ClipboardList class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="min-w-0 w-full overflow-hidden">
            <h3 class="text-sm font-medium">รายละเอียดความเสี่ยง</h3>
            <p class="mt-1 text-sm whitespace-pre-wrap break-words">{{ rowData.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
          </div>
        </div>   
      
        <!-- ข้อมูลวันที่ -->
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
        
        <!-- ส่วนเอกสารแนบ (เพิ่มใหม่) -->
        <div class="flex items-start space-x-2">
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
                  v-for="attachment in (rowData.attachments as DivisionRiskAttachment[])" 
                  :key="attachment.id" 
                  class="text-sm bg-background rounded-md p-2 border border-border overflow-hidden"
                >
                  <!-- แสดงชื่อและรายละเอียดของเอกสารแนบ -->
                  <div class="flex flex-col sm:flex-row items-start sm:justify-between gap-2">
                    <div class="flex items-start space-x-2 min-w-0 overflow-hidden">
                      <component 
                        :is="getFileIcon(attachment.file_type)" 
                        class="h-4 w-4 text-muted-foreground mt-0.5 flex-shrink-0" 
                      />
                      <div class="min-w-0 overflow-hidden">
                        <p class="font-medium truncate" @click="viewAttachmentFullScreen(attachment)">{{ attachment.file_name }}</p>
                        <p class="text-xs text-muted-foreground mt-1">
                          {{ formatFileSize(attachment.file_size) }} • 
                          อัปโหลดเมื่อ {{ new Date(attachment.created_at).toLocaleDateString('th-TH') }}
                        </p>
                      </div>
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
      
      <!-- คอลัมน์ขวา: ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง -->
      <div class="overflow-hidden">
        <div class="flex items-start space-x-2">
          <Network class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
          <div class="w-full min-w-0 overflow-hidden">
            <!-- หัวข้อพร้อมตัวนับจำนวนรายการ -->
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium truncate">ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง</h3>
              <span v-if="hasOrganizationalRisk" class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full flex-shrink-0">
                {{ organizationalRiskCount }} รายการ
              </span>
            </div>
            
            <!-- แสดงรายการความเสี่ยงระดับองค์กร -->
            <div v-if="hasOrganizationalRisk" class="mt-2">
              <ul class="space-y-2">
                <li 
                  class="text-sm bg-background rounded-md p-2 border border-border overflow-hidden"
                >
                  <!-- รายละเอียดความเสี่ยงระดับองค์กร -->
                  <div class="flex flex-col sm:flex-row items-start sm:justify-between gap-2">
                    <div class="flex items-start space-x-2 min-w-0 overflow-hidden">
                      <Users class="h-4 w-4 text-muted-foreground mt-0.5 flex-shrink-0" />
                      <div class="min-w-0 overflow-hidden">
                        <p class="font-medium truncate">{{ rowData.organizational_risk?.risk_name }}</p>
                        <p v-if="rowData.organizational_risk?.description" class="text-xs text-muted-foreground mt-1 line-clamp-2">
                          {{ rowData.organizational_risk?.description }}
                        </p>
                      </div>
                    </div>
                    
                    <!-- ปุ่มดูรายละเอียด -->
                    <button 
                      @click="viewOrganizationalRiskDetails(rowData.organizational_risk as OrganizationalRisk)"
                      class="text-xs text-primary hover:text-primary/80 transition-colors flex-shrink-0"
                    >
                      ดูรายละเอียด
                    </button>
                  </div>
                </li>
              </ul>
            </div>
            
            <!-- กรณีไม่มีความเสี่ยงระดับองค์กร -->
            <div v-else class="mt-2 flex items-center space-x-2 text-sm text-muted-foreground">
              <AlertTriangle class="h-4 w-4" />
              <p>ไม่มีความเสี่ยงระดับองค์กรที่เกี่ยวข้อง</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
