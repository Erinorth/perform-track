<!-- 
  ไฟล์: resources/js/pages/organizational_risk/Show.vue
  
  คำอธิบาย: หน้าแสดงรายละเอียดเต็มของความเสี่ยงระดับองค์กร
  ฟีเจอร์หลัก:
  - แสดงรายละเอียดความเสี่ยงระดับองค์กรแบบเต็มในหน้าแยก
  - แสดงวันที่สร้างและแก้ไขล่าสุดในรูปแบบวันที่ไทย
  - แสดงรายการความเสี่ยงระดับฝ่ายที่เกี่ยวข้อง
  - แสดงเอกสารแนบและรองรับการดาวน์โหลด
  - รองรับการแสดงผลแบบ Responsive ทั้งบนมือถือและหน้าจอขนาดใหญ่
-->

<script setup lang="ts">
// ==================== นำเข้า Layout และ Navigation ====================
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

// ==================== นำเข้า Types และ Interfaces ====================
// นำเข้า types สำหรับโมเดลข้อมูลความเสี่ยงและ BreadcrumbItem
import type { OrganizationalRisk, DivisionRisk, OrganizationalRiskAttachment } from '@/types/types';
import { type BreadcrumbItem } from '@/types';

// ==================== นำเข้า Vue Composition API ====================
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

// ==================== นำเข้า Components ====================
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

// ==================== นำเข้า Utilities ====================
import { toast } from 'vue-sonner';

// ==================== นำเข้า Icons ====================
import { 
  ClipboardList, AlertTriangle, Network, 
  CalendarDays, Users, Paperclip, Download, 
  FileText, FileImage, FileSpreadsheet, Eye,
  ArrowLeft, Pencil
} from 'lucide-vue-next';

// ==================== กำหนด Props ====================
const props = defineProps<{
  risk: OrganizationalRisk 
}>();

// ==================== กำหนด Breadcrumbs ====================
// กำหนด breadcrumbs ในรูปแบบเดียวกับที่ใช้ใน OrganizationalRisk.vue
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'จัดการความเสี่ยงองค์กร',
    href: route('organizational-risks.index'),
  },
  {
    title: props.risk.risk_name,
    href: '#',
  },
];

// ==================== Reactive States ====================
const isLoading = ref(false);

// ==================== Computed Properties ====================
// ตรวจสอบว่ามีความเสี่ยงระดับฝ่ายที่เกี่ยวข้องหรือไม่
const hasDivisionRisks = computed(() => {
  return props.risk.division_risks && props.risk.division_risks.length > 0;
});

// คำนวณจำนวนความเสี่ยงระดับฝ่ายที่เกี่ยวข้อง
const divisionRisksCount = computed(() => {
  return hasDivisionRisks.value ? props.risk.division_risks?.length : 0;
});

// ตรวจสอบว่ามีเอกสารแนบหรือไม่
const hasAttachments = computed(() => {
  return props.risk.attachments && props.risk.attachments.length > 0;
});

// คำนวณจำนวนเอกสารแนบ
const attachmentsCount = computed(() => {
  return hasAttachments.value ? props.risk.attachments?.length : 0;
});

// จัดรูปแบบวันที่สร้างและแก้ไขล่าสุดเป็นรูปแบบวันที่ไทย
const formattedDates = computed(() => {
  // สร้างวันที่สร้างในรูปแบบไทย
  const created = props.risk.created_at 
    ? new Date(props.risk.created_at).toLocaleDateString('th-TH', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }) 
    : 'ไม่ระบุ';
  
  // สร้างวันที่แก้ไขล่าสุดในรูปแบบไทย
  const updated = props.risk.updated_at 
    ? new Date(props.risk.updated_at).toLocaleDateString('th-TH', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }) 
    : 'ไม่ระบุ';
  
  return { created, updated };
});

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
const downloadAttachment = (attachment: OrganizationalRiskAttachment) => {
  // แสดง toast แจ้งเตือนผู้ใช้
  toast.info('กำลังดาวน์โหลดเอกสาร', {
    description: `ไฟล์ ${attachment.file_name} กำลังถูกดาวน์โหลด`,
    duration: 3000
  });
  
  // เพิ่ม log เพื่อการตรวจสอบ
  console.log('ดาวน์โหลดเอกสารแนบ:', attachment);
  
  window.open(`/organizational-risks/${props.risk.id}/attachments/${attachment.id}/download`, '_blank');
};

// เมธอดสำหรับแสดงรายละเอียดความเสี่ยงระดับฝ่ายแบบ popup
const viewDivisionRiskDetails = (risk: DivisionRisk) => {
  toast.info(`รายละเอียดความเสี่ยง: ${risk.risk_name}`, {
    description: risk.description || 'ไม่มีคำอธิบายเพิ่มเติม',
    duration: 5000
  });
  
  console.log('ดูรายละเอียดความเสี่ยงระดับฝ่าย:', risk);
};

// ฟังก์ชันเปิดหน้าแสดงไฟล์แนบแบบเต็มจอ
const viewAttachmentFullScreen = (attachment: OrganizationalRiskAttachment) => {
  window.open(`/organizational-risks/${props.risk.id}/attachments/${attachment.id}/view`, '_blank');
  
  console.log('เปิดหน้าดูไฟล์แนบแบบเต็มจอ:', attachment);
  
  toast.info('กำลังเปิดไฟล์แนบ', {
    description: `กำลังเปิดไฟล์ ${attachment.file_name}`,
    duration: 3000
  });
};

// ฟังก์ชันนำทางไปยังหน้าแก้ไข
const navigateToEdit = () => {
  router.visit(`/organizational-risks/${props.risk.id}/edit`);
};

// ==================== Lifecycle Hooks ====================
onMounted(() => {
  isLoading.value = true;
  
  setTimeout(() => {
    isLoading.value = false;
    console.log('Show: โหลดข้อมูลรายละเอียดสำหรับความเสี่ยง:', props.risk.risk_name);
  }, 300);
});
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head :title="`รายละเอียดความเสี่ยง - ${risk.risk_name}`" />

  <!-- ใช้ Layout หลักของแอปพลิเคชันพร้อมส่ง breadcrumbs เป็น prop -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- พื้นที่หลักของหน้า -->
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- หัวข้อและปุ่มดำเนินการ -->
      <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-2">
          <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            รายละเอียดความเสี่ยงระดับองค์กร
          </h1>
          <div v-if="isLoading" class="animate-pulse">
            <div class="h-5 w-5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button 
            variant="outline" 
            size="sm"
            @click="router.visit(route('organizational-risks.index'))"
          >
            <ArrowLeft class="mr-1 h-4 w-4" />
            กลับไปรายการความเสี่ยงระดับองค์กร
          </Button>
          <Button 
            size="sm"
            @click="navigateToEdit"
          >
            <Pencil class="mr-1 h-4 w-4" />
            แก้ไข
          </Button>
        </div>
      </div>

      <!-- แสดงข้อมูลหลักของความเสี่ยง -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- คอลัมน์ซ้าย: ข้อมูลหลักของความเสี่ยง -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <AlertTriangle class="h-5 w-5 text-orange-500" />
                {{ risk.risk_name }}
              </CardTitle>
              <CardDescription class="text-sm text-muted-foreground">
                รหัส: {{ risk.id }}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-6">
                <!-- รายละเอียดความเสี่ยง -->
                <div>
                  <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">รายละเอียดความเสี่ยง</h3>
                  <div class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ risk.description }}</div>
                </div>

                <!-- วันที่สร้างและอัพเดท -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">วันที่สร้าง</h3>
                    <div class="flex items-center gap-1.5">
                      <CalendarDays class="h-4 w-4 text-gray-400" />
                      <span class="text-gray-900 dark:text-gray-100">{{ formattedDates.created }}</span>
                    </div>
                  </div>
                  <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">แก้ไขล่าสุด</h3>
                    <div class="flex items-center gap-1.5">
                      <CalendarDays class="h-4 w-4 text-gray-400" />
                      <span class="text-gray-900 dark:text-gray-100">{{ formattedDates.updated }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- แสดงความเสี่ยงระดับฝ่ายที่เกี่ยวข้อง -->
          <Card class="mt-6">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Network class="h-5 w-5 text-blue-500" />
                ความเสี่ยงระดับฝ่ายที่เกี่ยวข้อง
                <span v-if="divisionRisksCount" class="ml-auto inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/10 dark:text-blue-400 dark:ring-blue-400/30">
                  {{ divisionRisksCount }} รายการ
                </span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="hasDivisionRisks" class="space-y-4">
                <!-- แสดงรายการความเสี่ยงระดับฝ่ายทั้งหมด -->
                <div v-for="(divisionRisk, index) in risk.division_risks" :key="divisionRisk.id" class="rounded-lg border bg-card p-4 shadow-sm transition-all hover:bg-accent hover:text-accent-foreground">
                  <div class="flex flex-wrap items-start justify-between gap-4">
                    <!-- ชื่อความเสี่ยงระดับฝ่าย -->
                    <div class="flex-1">
                      <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ divisionRisk.risk_name }}</h3>
                      <p v-if="divisionRisk.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                        {{ divisionRisk.description }}
                      </p>
                      <!-- แสดงเวลาที่สร้าง -->
                      <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        สร้างเมื่อ: {{ new Date(divisionRisk.created_at).toLocaleDateString('th-TH') }}
                      </p>
                    </div>
                    
                    <!-- ปุ่มดูรายละเอียดเพิ่มเติม -->
                    <Button variant="outline" size="sm" @click="viewDivisionRiskDetails(divisionRisk)">
                      <Eye class="mr-1 h-4 w-4" />
                      ดูเพิ่มเติม
                    </Button>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-12">
                <Users class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">ไม่พบข้อมูลความเสี่ยงระดับฝ่ายที่เกี่ยวข้อง</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ความเสี่ยงนี้ยังไม่ได้มีการเชื่อมโยงกับความเสี่ยงระดับฝ่ายใด ๆ</p>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- คอลัมน์ขวา: เอกสารแนบและข้อมูลสรุป -->
        <div>
          <!-- แสดงเอกสารแนบ -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Paperclip class="h-5 w-5 text-emerald-500" />
                เอกสารแนบ
                <span v-if="attachmentsCount" class="ml-auto inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-900/10 dark:text-emerald-400 dark:ring-emerald-400/30">
                  {{ attachmentsCount }} ไฟล์
                </span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="hasAttachments" class="space-y-3">
                <div v-for="attachment in risk.attachments" :key="attachment.id" 
                  class="flex items-center justify-between gap-4 rounded-lg border p-3 text-sm shadow-sm transition-all hover:bg-accent hover:text-accent-foreground">
                  <!-- แสดงข้อมูลไฟล์ -->
                  <div class="flex flex-1 items-center gap-3 truncate">
                    <!-- แสดงไอคอนตามประเภทไฟล์ -->
                    <component :is="getFileIcon(attachment.file_type)" class="h-5 w-5 flex-shrink-0 text-gray-400" />
                    
                    <!-- ชื่อไฟล์และข้อมูลขนาด -->
                    <div class="flex-1 truncate">
                      <p class="truncate font-medium">{{ attachment.file_name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ formatFileSize(attachment.file_size) }}
                      </p>
                    </div>
                  </div>
                  
                  <!-- ปุ่มดำเนินการกับไฟล์ -->
                  <div class="flex items-center gap-2">
                    <Button variant="ghost" size="icon" @click="viewAttachmentFullScreen(attachment)" v-if="attachment.file_type && attachment.file_type.includes('pdf')">
                      <Eye class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon" @click="downloadAttachment(attachment)">
                      <Download class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-12">
                <Paperclip class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">ไม่มีเอกสารแนบ</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ยังไม่มีการอัพโหลดเอกสารแนบสำหรับความเสี่ยงนี้</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
