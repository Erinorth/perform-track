<!-- 
  ไฟล์: resources/js/pages/division_risk/Show.vue
  
  คำอธิบาย: หน้าแสดงรายละเอียดเต็มของความเสี่ยงระดับฝ่าย
  ฟีเจอร์หลัก:
  - แสดงรายละเอียดความเสี่ยงระดับฝ่ายแบบเต็มในหน้าแยก
  - แสดงข้อมูลความเสี่ยงระดับองค์กรที่เกี่ยวข้อง
  - แสดงประวัติการประเมินความเสี่ยง
  - แสดงเกณฑ์การประเมินโอกาสและผลกระทบ
  - แสดงเอกสารแนบและรองรับการดาวน์โหลด
  - รองรับการแสดงผลแบบ Responsive ทั้งบนมือถือและหน้าจอขนาดใหญ่
-->

<script setup lang="ts">
// ==================== นำเข้า Layout และ Navigation ====================
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

// ==================== นำเข้า Types และ Interfaces ====================
import type { 
  DivisionRisk, 
  OrganizationalRisk, 
  DivisionRiskAttachment, 
  RiskAssessment,
  LikelihoodCriteria,
  ImpactCriteria 
} from '@/types/types';
import { type BreadcrumbItem } from '@/types';

// ==================== นำเข้า Vue Composition API ====================
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

// ==================== นำเข้า Components ====================
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AlertConfirmDialog from '@/components/AlertConfirmDialog.vue';
import { useConfirm } from '@/composables/useConfirm';

// ==================== นำเข้า Utilities ====================
import { toast } from 'vue-sonner';

// ==================== นำเข้า Icons ====================
import { 
  ClipboardList, AlertTriangle, Link, Network, BarChart3,
  CalendarDays, Users, Paperclip, Download, 
  FileText, FileImage, FileSpreadsheet, Eye,
  ArrowLeft, Pencil, Trash, CheckCircle2,
  AlertCircle, Clock, Gauge, ArrowUpRight
} from 'lucide-vue-next';

// ==================== กำหนด Props ====================
const props = defineProps<{
  risk: DivisionRisk;
  organizational_risk?: OrganizationalRisk;
  assessments?: RiskAssessment[];
  likelihood_criteria?: LikelihoodCriteria[];
  impact_criteria?: ImpactCriteria[];
}>();

// ==================== กำหนด Breadcrumbs ====================
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'จัดการความเสี่ยงระดับฝ่าย',
    href: route('division-risks.index'),
  },
  {
    title: props.risk.risk_name,
    href: '#',
  },
];

// ==================== Reactive States ====================
const isLoading = ref(false);

// ==================== Computed Properties ====================
// ตรวจสอบว่ามีความเสี่ยงระดับองค์กรที่เกี่ยวข้องหรือไม่
const hasOrganizationalRisk = computed(() => {
  return props.organizational_risk !== undefined;
});

// ตรวจสอบว่ามีการประเมินความเสี่ยงหรือไม่
const hasAssessments = computed(() => {
  return props.assessments && props.assessments.length > 0;
});

// คำนวณจำนวนการประเมินความเสี่ยง
const assessmentsCount = computed(() => {
  return hasAssessments.value ? props.assessments?.length : 0;
});

// ตรวจสอบว่ามีเอกสารแนบหรือไม่
const hasAttachments = computed(() => {
  return props.risk.attachments && props.risk.attachments.length > 0;
});

// คำนวณจำนวนเอกสารแนบ
const attachmentsCount = computed(() => {
  return hasAttachments.value ? props.risk.attachments?.length : 0;
});

// ดึงการประเมินล่าสุด
const latestAssessment = computed(() => {
  if (!hasAssessments.value) return null;
  
  return [...props.assessments!].sort((a, b) => 
    new Date(b.assessment_date).getTime() - new Date(a.assessment_date).getTime()
  )[0];
});

// คำนวณระดับความเสี่ยงจากคะแนน
const getRiskLevel = (score: number) => {
  if (score >= 12) return { level: 'สูงมาก', class: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' };
  if (score >= 8) return { level: 'สูง', class: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400' };
  if (score >= 4) return { level: 'ปานกลาง', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' };
  return { level: 'ต่ำ', class: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' };
};

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
const downloadAttachment = (attachment: DivisionRiskAttachment) => {
  // แสดง toast แจ้งเตือนผู้ใช้
  toast.info('กำลังดาวน์โหลดเอกสาร', {
    description: `ไฟล์ ${attachment.file_name} กำลังถูกดาวน์โหลด`,
    duration: 3000
  });
  
  // เพิ่ม log เพื่อการตรวจสอบ
  console.log('ดาวน์โหลดเอกสารแนบ:', attachment);
  
  window.open(`/division-risks/${props.risk.id}/attachments/${attachment.id}/download`, '_blank');
};

// ฟังก์ชันเปิดหน้าแสดงไฟล์แนบแบบเต็มจอ
const viewAttachmentFullScreen = (attachment: DivisionRiskAttachment) => {
  window.open(`/division-risks/${props.risk.id}/attachments/${attachment.id}/view`, '_blank');
  
  console.log('เปิดหน้าดูไฟล์แนบแบบเต็มจอ:', attachment);
  
  toast.info('กำลังเปิดไฟล์แนบ', {
    description: `กำลังเปิดไฟล์ ${attachment.file_name}`,
    duration: 3000
  });
};

// ฟังก์ชันนำทางไปยังหน้าแก้ไข
const navigateToEdit = () => {
  router.visit(`/division-risks/${props.risk.id}/edit`);
};

// ฟังก์ชันนำทางไปยังหน้าประเมินความเสี่ยงใหม่
const navigateToNewAssessment = () => {
  router.visit(`/division-risks/${props.risk.id}/assessments/create`);
};

// ฟังก์ชันดูรายละเอียดการประเมินความเสี่ยง
const viewAssessmentDetails = (assessment: RiskAssessment) => {
  router.visit(`/division-risks/${props.risk.id}/assessments/${assessment.id}`);
};

// ฟังก์ชันดูรายละเอียดความเสี่ยงระดับองค์กร
const viewOrganizationalRiskDetails = () => {
  if (!hasOrganizationalRisk.value) return;
  
  router.visit(route('organizational-risks.show', props.organizational_risk!.id));
};

// ==================== Lifecycle Hooks ====================
onMounted(() => {
  isLoading.value = true;
  
  setTimeout(() => {
    isLoading.value = false;
    console.log('Show: โหลดข้อมูลรายละเอียดสำหรับความเสี่ยงระดับฝ่าย:', props.risk.risk_name);
  }, 300);
});

// ==================== ฟังก์ชันลบความเสี่ยงระดับฝ่าย ====================
const isDeleting = ref(false);

// ฟังก์ชันแสดง dialog ยืนยันการลบ
const confirmDelete = () => {
  openConfirm({
    title: 'ยืนยันการลบความเสี่ยง',
    message: `คุณต้องการลบ "${props.risk.risk_name}" หรือไม่? การลบนี้ไม่สามารถย้อนกลับได้`,
    confirmText: 'ลบ',
    cancelText: 'ยกเลิก',
    onConfirm: async () => {
      await deleteRisk();
    }
  });
};

// ฟังก์ชันลบความเสี่ยง
const deleteRisk = async () => {
  isDeleting.value = true;
  // log สำหรับตรวจสอบ
  console.log('Show: เริ่มลบความเสี่ยงระดับฝ่าย', props.risk.id);
  try {
    await router.delete(route('division-risks.destroy', props.risk.id), {
      onSuccess: () => {
        toast.success('ลบความเสี่ยงระดับฝ่ายเรียบร้อยแล้ว', {
          description: 'ข้อมูลถูกลบเรียบร้อย',
          duration: 3000
        });
        // log สำเร็จ
        console.log('Show: ลบความเสี่ยงสำเร็จ', props.risk.id);
        // กลับไปหน้า index หลังลบสำเร็จ
        router.visit(route('division-risks.index'));
      },
      onError: (err) => {
        toast.error('เกิดข้อผิดพลาด', {
          description: 'ไม่สามารถลบข้อมูลได้',
          duration: 4000
        });
        console.error('Show: ลบความเสี่ยงล้มเหลว', err);
      },
      onFinish: () => {
        isDeleting.value = false;
      }
    });
  } catch (e) {
    isDeleting.value = false;
    toast.error('เกิดข้อผิดพลาด', {
      description: 'ไม่สามารถลบข้อมูลได้',
      duration: 4000
    });
    console.error('Show: ลบความเสี่ยงล้มเหลว', e);
  }
};

// เพิ่ม openConfirm จาก useConfirm
const { isOpen, options, isProcessing, handleConfirm, handleCancel, openConfirm } = useConfirm();
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head :title="`รายละเอียดความเสี่ยงระดับฝ่าย - ${risk.risk_name}`" />

  <!-- ใช้ Layout หลักของแอปพลิเคชันพร้อมส่ง breadcrumbs เป็น prop -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- พื้นที่หลักของหน้า -->
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- หัวข้อและปุ่มดำเนินการ -->
      <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-2">
          <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            รายละเอียดความเสี่ยงระดับฝ่าย
          </h1>
          <div v-if="isLoading" class="animate-pulse">
            <div class="h-5 w-5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button 
            variant="outline" 
            size="sm"
            @click="router.visit(route('division-risks.index'))"
          >
            <ArrowLeft class="mr-1 h-4 w-4" />
            กลับไปรายการความเสี่ยงระดับฝ่าย
          </Button>
          <Button 
            size="sm"
            variant="outline"
            @click="navigateToNewAssessment"
          >
            <BarChart3 class="mr-1 h-4 w-4" />
            ประเมินความเสี่ยงใหม่
          </Button>
          <Button 
            size="sm"
            @click="navigateToEdit"
          >
            <Pencil class="mr-1 h-4 w-4" />
            แก้ไข
          </Button>
          <Button 
            size="sm"
            :disabled="isDeleting"
            variant="destructive"
            @click="confirmDelete"
          >
            <Trash class="mr-1 h-4 w-4" />
            ลบ
          </Button>
        </div>
      </div>

      <!-- แสดงข้อมูลหลักของความเสี่ยง -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- คอลัมน์ซ้าย: ข้อมูลหลักของความเสี่ยงและการประเมินล่าสุด -->
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

                <!-- แสดงความเสี่ยงระดับองค์กรที่เกี่ยวข้อง -->
                <div v-if="hasOrganizationalRisk">
                  <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง</h3>
                  <div class="rounded-lg border p-4 bg-secondary/50">
                    <div class="flex items-start justify-between">
                      <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
                          <Link class="h-4 w-4 mr-1.5 text-blue-500" />
                          {{ organizational_risk?.risk_name }}
                        </h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                          {{ organizational_risk?.description }}
                        </p>
                      </div>
                      <Button variant="outline" size="sm" @click="viewOrganizationalRiskDetails">
                        <Eye class="mr-1 h-4 w-4" />
                        ดูรายละเอียด
                      </Button>
                    </div>
                  </div>
                </div>

                <!-- แสดงการประเมินความเสี่ยงล่าสุด -->
                <div v-if="latestAssessment">
                  <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">การประเมินความเสี่ยงล่าสุด</h3>
                  <div class="rounded-lg border p-4 bg-secondary/50">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                      <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">วันที่ประเมิน</p>
                        <p class="font-medium flex items-center">
                          <CalendarDays class="h-4 w-4 mr-1.5 text-gray-400" />
                          {{ new Date(latestAssessment.assessment_date).toLocaleDateString('th-TH') }}
                        </p>
                      </div>
                      <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ระดับความเสี่ยง</p>
                        <div class="flex items-center mt-1">
                          <Badge :class="getRiskLevel(latestAssessment.risk_score).class">
                            {{ getRiskLevel(latestAssessment.risk_score).level }} ({{ latestAssessment.risk_score }})
                          </Badge>
                        </div>
                      </div>
                      <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">โอกาสเกิด</p>
                        <p class="font-medium flex items-center">
                          <AlertCircle class="h-4 w-4 mr-1.5 text-blue-500" />
                          ระดับ {{ latestAssessment.likelihood_level }}
                        </p>
                      </div>
                      <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ผลกระทบ</p>
                        <p class="font-medium flex items-center">
                          <AlertCircle class="h-4 w-4 mr-1.5 text-red-500" />
                          ระดับ {{ latestAssessment.impact_level }}
                        </p>
                      </div>
                      <div class="sm:col-span-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">บันทึกเพิ่มเติม</p>
                        <p class="font-medium mt-1">{{ latestAssessment.notes || '-' }}</p>
                      </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                      <Button size="sm" variant="outline" @click="viewAssessmentDetails(latestAssessment)">
                        <Eye class="mr-1 h-4 w-4" />
                        ดูรายละเอียดเพิ่มเติม
                      </Button>
                    </div>
                  </div>
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

          <!-- แสดงประวัติการประเมินความเสี่ยง -->
          <Card class="mt-6">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <BarChart3 class="h-5 w-5 text-blue-500" />
                ประวัติการประเมินความเสี่ยง
                <span v-if="assessmentsCount" class="ml-auto inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/10 dark:text-blue-400 dark:ring-blue-400/30">
                  {{ assessmentsCount }} รายการ
                </span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="hasAssessments" class="space-y-4">
                <!-- แสดงรายการประเมินความเสี่ยงทั้งหมด -->
                <div v-for="(assessment, index) in props.assessments" :key="assessment.id" 
                  class="rounded-lg border p-4 shadow-sm transition-all hover:bg-accent hover:text-accent-foreground">
                  <div class="flex flex-wrap items-start justify-between gap-4">
                    <!-- ข้อมูลการประเมิน -->
                    <div class="flex-1">
                      <div class="flex items-center gap-2">
                        <Badge :class="getRiskLevel(assessment.risk_score).class">
                          {{ getRiskLevel(assessment.risk_score).level }} ({{ assessment.risk_score }})
                        </Badge>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                          ประเมินเมื่อ {{ new Date(assessment.assessment_date).toLocaleDateString('th-TH') }}
                        </span>
                      </div>
                      
                      <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                        <div class="flex items-center gap-1.5">
                          <Clock class="h-4 w-4 text-blue-500" />
                          <span>โอกาสเกิด: ระดับ {{ assessment.likelihood_level }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                          <Gauge class="h-4 w-4 text-red-500" />
                          <span>ผลกระทบ: ระดับ {{ assessment.impact_level }}</span>
                        </div>
                      </div>
                      
                      <p v-if="assessment.notes" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ assessment.notes }}
                      </p>
                    </div>
                    
                    <!-- ปุ่มดูรายละเอียดเพิ่มเติม -->
                    <Button variant="outline" size="sm" @click="viewAssessmentDetails(assessment)">
                      <Eye class="mr-1 h-4 w-4" />
                      ดูรายละเอียด
                    </Button>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-12">
                <BarChart3 class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">ยังไม่มีการประเมินความเสี่ยง</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">คลิกที่ปุ่ม "ประเมินความเสี่ยงใหม่" เพื่อเริ่มต้นการประเมิน</p>
                <div class="mt-4">
                  <Button size="sm" @click="navigateToNewAssessment">
                    <BarChart3 class="mr-1 h-4 w-4" />
                    ประเมินความเสี่ยงใหม่
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- คอลัมน์ขวา: เอกสารแนบและเกณฑ์การประเมิน -->
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
              <div v-else class="text-center py-8">
                <Paperclip class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">ไม่มีเอกสารแนบ</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ยังไม่มีการอัพโหลดเอกสารแนบสำหรับความเสี่ยงนี้</p>
              </div>
            </CardContent>
          </Card>

          <!-- แสดงเกณฑ์การประเมินโอกาสเกิด -->
          <Card class="mt-6">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Clock class="h-5 w-5 text-blue-500" />
                เกณฑ์การประเมินโอกาสเกิด
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="likelihood_criteria && likelihood_criteria.length > 0" class="space-y-3">
                <div v-for="criteria in likelihood_criteria" :key="criteria.id" 
                  class="rounded-lg border p-3 text-sm">
                  <div class="flex items-center justify-between">
                    <h4 class="font-medium">ระดับ {{ criteria.level }}</h4>
                    <Badge variant="secondary">{{ criteria.name }}</Badge>
                  </div>
                  <p v-if="criteria.description" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ criteria.description }}
                  </p>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <Clock class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">ยังไม่มีเกณฑ์การประเมิน</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">คุณสามารถกำหนดเกณฑ์การประเมินได้ในหน้าแก้ไข</p>
              </div>
            </CardContent>
          </Card>

          <!-- แสดงเกณฑ์การประเมินผลกระทบ -->
          <Card class="mt-6">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Gauge class="h-5 w-5 text-red-500" />
                เกณฑ์การประเมินผลกระทบ
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="impact_criteria && impact_criteria.length > 0" class="space-y-3">
                <div v-for="criteria in impact_criteria" :key="criteria.id" 
                  class="rounded-lg border p-3 text-sm">
                  <div class="flex items-center justify-between">
                    <h4 class="font-medium">ระดับ {{ criteria.level }}</h4>
                    <Badge variant="secondary">{{ criteria.name }}</Badge>
                  </div>
                  <p v-if="criteria.description" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ criteria.description }}
                  </p>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <Gauge class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">ยังไม่มีเกณฑ์การประเมิน</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">คุณสามารถกำหนดเกณฑ์การประเมินได้ในหน้าแก้ไข</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <AlertConfirmDialog
        v-model:show="isOpen"
        :title="options?.title || ''"
        :message="options?.message || ''"
        :confirm-text="options?.confirmText"
        :cancel-text="options?.cancelText"
        :processing="isProcessing"
        @confirm="handleConfirm"
        @cancel="handleCancel"
      />

    </div>
  </AppLayout>
</template>
