<!-- resources/js/pages/risk_assessment/RiskAssessmentShow.vue -->
<script setup lang="ts">
// ==================== นำเข้า Layout และ Navigation ====================
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

// ==================== นำเข้า Types และ Interfaces ====================

import type { LikelihoodCriteria, ImpactCriteria, DivisionRisk, RiskAssessment, RiskAssessmentAttachment } from '@/types/types';
import type { BreadcrumbItem } from '@/types'

// ==================== นำเข้า Vue Composition API ====================
import { computed, onMounted, ref } from 'vue'

// ==================== นำเข้า Components ====================
import { Button } from '@/components/ui/button'
import { 
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
  CardDescription,
} from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import AlertConfirmDialog from '@/components/AlertConfirmDialog.vue'
import { useConfirm } from '@/composables/useConfirm'
import RiskMatrix from '@/components/RiskMatrix.vue'

// ==================== นำเข้า Utilities ====================
import { toast } from 'vue-sonner'

// ==================== นำเข้า Icons ====================
import { 
  ArrowLeft, 
  Pencil,
  Trash,
  BarChart4,
  CalendarDays,
  AlertCircle,
  FileText,
  CheckCircle2,
  XCircle,
  ClipboardList,
  Network,
  Link,Paperclip,
  Download,
  Eye,
  FileImage,
  FileSpreadsheet,
  User,
  ArrowDown,
  History
} from 'lucide-vue-next'

// ==================== กำหนด Props ====================
const props = defineProps<{
  riskAssessment: RiskAssessment
}>()

// ==================== กำหนด Breadcrumbs ====================
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'การประเมินความเสี่ยง',
    href: route('risk-assessments.index'),
  },
  {
    title: props.riskAssessment.division_risk?.risk_name || `การประเมินความเสี่ยง #${props.riskAssessment.id}`,
    href: '#',
  },
]

// ==================== Reactive States ====================
const isLoading = ref(false)
const showMatrix = ref(false)
const isDeleting = ref(false)

// ==================== Computed Properties ====================
// คำนวณระดับความเสี่ยง
const riskLevel = computed(() => {
  const score = props.riskAssessment.risk_score
  if (score <= 3) {
    return { text: 'ความเสี่ยงต่ำ', color: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' }
  } else if (score <= 8) {
    return { text: 'ความเสี่ยงปานกลาง', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' }
  } else if (score <= 12) {
    return { text: 'ความเสี่ยงสูง', color: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400' }
  } else {
    return { text: 'ความเสี่ยงสูงมาก', color: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' }
  }
})

// หาเกณฑ์การประเมินที่สอดคล้องกับระดับที่เลือก
const selectedLikelihoodCriteria = computed(() => {
  return props.riskAssessment.division_risk?.likelihood_criteria?.find(
    criteria => criteria.level === props.riskAssessment.likelihood_level
  )
})

const selectedImpactCriteria = computed(() => {
  return props.riskAssessment.division_risk?.impact_criteria?.find(
    criteria => criteria.level === props.riskAssessment.impact_level
  )
})

// จัดรูปแบบวันที่
const formattedAssessmentDate = computed(() => {
  return props.riskAssessment.assessment_date 
    ? new Date(props.riskAssessment.assessment_date).toLocaleDateString('th-TH', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric'
      }) 
    : 'ไม่ระบุ'
})

// ตรวจสอบว่ามีเอกสารแนบหรือไม่
const hasAttachments = computed(() => {
  return props.riskAssessment.attachments && props.riskAssessment.attachments.length > 0
})

// คำนวณจำนวนเอกสารแนบ
const attachmentsCount = computed(() => {
  return hasAttachments.value ? props.riskAssessment.attachments?.length : 0
})

// ==================== Methods ====================
// สลับการแสดงแผนภูมิความเสี่ยง
const toggleRiskMatrix = () => {
  showMatrix.value = !showMatrix.value
  console.log('สลับการแสดงแผนภูมิความเสี่ยง:', showMatrix.value ? 'แสดง' : 'ซ่อน')
}

/**
 * ฟังก์ชันดาวน์โหลดเอกสารแนบ
 */
const downloadAttachment = (attachment: any) => {
  // แสดง toast แจ้งเตือนผู้ใช้
  toast.info('กำลังดาวน์โหลดเอกสาร', {
    description: `ไฟล์ ${attachment.file_name} กำลังถูกดาวน์โหลด`, // ใช้ file_name แทน filename
    duration: 3000
  })
  
  // เพิ่ม log เพื่อการตรวจสอบ
  console.log('ดาวน์โหลดเอกสารแนบ:', attachment)
  
  // แก้ไขวิธีการส่งพารามิเตอร์เป็นแบบ Object
  window.open(`/risk-assessments/${props.riskAssessment.id}/attachments/${attachment.id}/download`, '_blank');
}

// ==================== Lifecycle Hooks ====================
onMounted(() => {
  isLoading.value = true
  
  setTimeout(() => {
    isLoading.value = false
    console.log('โหลดข้อมูลรายละเอียดสำหรับการประเมินความเสี่ยง:', props.riskAssessment.id)
  }, 300)
})

/**
 * ฟังก์ชันเปิดเอกสารแนบแบบเต็มหน้าจอ
 */
const viewAttachmentFullScreen = (attachment: any) => {
  // แก้ไขวิธีการส่งพารามิเตอร์เป็นแบบ Object ที่มี key ชื่อ attachmentId
  window.open(`/risk-assessments/${props.riskAssessment.id}/attachments/${attachment.id}/view`, '_blank');
  
  console.log('เปิดหน้าดูไฟล์แนบแบบเต็มจอ:', attachment)
  
  toast.info('กำลังเปิดไฟล์แนบ', {
    description: `กำลังเปิดไฟล์ ${attachment.file_name}`, // ใช้ file_name แทน filename
    duration: 3000
  })
}
// ฟังก์ชันนำทางไปยังหน้าแก้ไข
const navigateToEdit = () => {
  router.visit(route('risk-assessments.edit', props.riskAssessment.id))
  console.log('นำทางไปยังหน้าแก้ไขการประเมินความเสี่ยง:', props.riskAssessment.id)
}

// ฟังก์ชันนำทางไปยังหน้าแสดงรายละเอียดความเสี่ยงระดับฝ่าย
const navigateToDivisionRiskDetails = () => {
  router.visit(route('division-risks.show', props.riskAssessment.division_risk?.id))
  console.log('นำทางไปยังหน้าแสดงรายละเอียดความเสี่ยงระดับฝ่าย:', props.riskAssessment.division_risk?.id)
}

// ฟังก์ชันนำทางไปยังหน้าแสดงรายละเอียดความเสี่ยงระดับองค์กร
const navigateToOrganizationalRiskDetails = () => {
  router.visit(route('organizational-risks.show', props.riskAssessment.division_risk?.organizational_risk?.id))
  console.log('นำทางไปยังหน้าแสดงรายละเอียดความเสี่ยงระดับองค์กร:', props.riskAssessment.division_risk?.organizational_risk?.id)
}

// ฟังก์ชันแสดง dialog ยืนยันการลบ
const confirmDelete = () => {
  openConfirm({
    title: 'ยืนยันการลบการประเมินความเสี่ยง',
    message: `คุณต้องการลบการประเมินความเสี่ยงนี้หรือไม่? การลบนี้ไม่สามารถย้อนกลับได้`,
    confirmText: 'ลบ',
    cancelText: 'ยกเลิก',
    onConfirm: async () => {
      await deleteRiskAssessment()
    }
  })
}

// ฟังก์ชันลบการประเมินความเสี่ยง
const deleteRiskAssessment = async () => {
  isDeleting.value = true
  
  // log สำหรับตรวจสอบ
  console.log('เริ่มลบการประเมินความเสี่ยง:', props.riskAssessment.id)
  
  try {
    await router.delete(route('risk-assessments.destroy', props.riskAssessment.id), {
      onSuccess: () => {
        toast.success('ลบข้อมูลการประเมินความเสี่ยงเรียบร้อยแล้ว', {
          description: 'ข้อมูลถูกลบเรียบร้อย',
          duration: 3000
        })
        // log สำเร็จ
        console.log('ลบการประเมินความเสี่ยงสำเร็จ:', props.riskAssessment.id)
        // กลับไปหน้า index หลังลบสำเร็จ
        router.visit(route('risk-assessments.index'))
      },
      onError: (err) => {
        toast.error('เกิดข้อผิดพลาด', {
          description: 'ไม่สามารถลบข้อมูลได้: ' + err.message,
          duration: 4000
        })
        console.error('ลบการประเมินความเสี่ยงล้มเหลว:', err)
      },
      onFinish: () => {
        isDeleting.value = false
      }
    })
  } catch (e: any) {
    isDeleting.value = false
    toast.error('เกิดข้อผิดพลาด', {
      description: 'ไม่สามารถลบข้อมูลได้: ' + e.message,
      duration: 4000
    })
    console.error('ลบการประเมินความเสี่ยงล้มเหลว:', e)
  }
}

/**
 * ฟังก์ชันเลือกไอคอนตามประเภทไฟล์
 */
const getFileIcon = (filetype: string | null) => {
  if (!filetype) return FileText
  
  const type = filetype.toLowerCase()
  
  if (type.includes('pdf')) {
    return FileText
  } else if (type.includes('xls') || type.includes('csv') || type.includes('sheet')) {
    return FileSpreadsheet
  } else if (type.includes('image') || type.includes('jpg') || type.includes('png') || type.includes('jpeg')) {
    return FileImage
  } else {
    return FileText // ไอคอนเริ่มต้น
  }
}

/**
 * ฟังก์ชันจัดรูปแบบขนาดไฟล์
 */
const formatFileSize = (bytes: number | null) => {
  if (!bytes) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// เพิ่ม openConfirm จาก useConfirm
const { isOpen, options, isProcessing, handleConfirm, handleCancel, openConfirm } = useConfirm()
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head :title="`รายละเอียดการประเมินความเสี่ยง #${riskAssessment.id}`" />

  <!-- ใช้ Layout หลักของแอปพลิเคชันพร้อมส่ง breadcrumbs เป็น prop -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- พื้นที่หลักของหน้า -->
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- หัวข้อและปุ่มดำเนินการ -->
      <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-2">
          <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            รายละเอียดการประเมินความเสี่ยง
          </h1>
          <div v-if="isLoading" class="animate-pulse">
            <div class="h-5 w-5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button 
            variant="outline" 
            size="sm"
            @click="router.visit(route('risk-assessments.index'))"
          >
            <ArrowLeft class="mr-1 h-4 w-4" />
            กลับไปรายการประเมินความเสี่ยง
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

      <!-- แสดงข้อมูลหลัก -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- คอลัมน์ซ้าย: ข้อมูลหลักของการประเมินความเสี่ยง -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <ClipboardList class="h-5 w-5 text-blue-500" />
                การประเมินความเสี่ยง "{{ riskAssessment.division_risk?.risk_name }}"
              </CardTitle>
              <CardDescription class="text-sm text-muted-foreground flex items-center gap-1.5">
                <CalendarDays class="h-4 w-4" />
                            วันที่ประเมิน: {{ formattedAssessmentDate }}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <!-- ข้อมูลหลักของการประเมินความเสี่ยง -->
          <div class="space-y-6">
            <!-- ส่วนของคะแนนความเสี่ยง -->
            <div class="flex flex-col space-y-2">
              <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                  <div class="text-sm font-medium text-muted-foreground">คะแนนความเสี่ยง</div>
                  <div class="mt-1 flex items-center gap-2">
                    <div class="text-3xl font-bold">{{ riskAssessment.risk_score }}</div>
                    <Badge :class="riskLevel.color" class="text-xs">
                      {{ riskLevel.text }}
                    </Badge>
                  </div>
                </div>
                <Button 
                  variant="outline" 
                  size="sm"
                  @click="toggleRiskMatrix"
                  class="md:self-end"
                >
                  <BarChart4 class="mr-1 h-4 w-4" />
                  {{ showMatrix ? 'ซ่อนแผนภูมิความเสี่ยง' : 'แสดงแผนภูมิความเสี่ยง' }}
                </Button>
              </div>
              
              <!-- แผนภูมิความเสี่ยง (แสดงเมื่อกดปุ่ม) -->
              <div v-if="showMatrix" class="mt-4 rounded-lg border p-4">
                <RiskMatrix
                  :likelihood="riskAssessment.likelihood_level"
                  :impact="riskAssessment.impact_level"
                  :risk-score="riskAssessment.risk_score"
                  class="max-w-md mx-auto"
                />
              </div>
            </div>

            <!-- เส้นคั่น -->
            <div class="border-t dark:border-gray-800"></div>

            <!-- ระดับโอกาสเกิด -->
            <div>
              <div class="flex flex-col gap-1">
                <div class="text-sm font-medium text-muted-foreground">ระดับโอกาสเกิด (Likelihood)</div>
                <div class="flex items-center gap-2">
                  <div class="text-lg font-medium">{{ riskAssessment.likelihood_level }}</div>
                  <div v-if="selectedLikelihoodCriteria" class="text-md">
                    {{ selectedLikelihoodCriteria.name }}
                  </div>
                </div>
                <div v-if="selectedLikelihoodCriteria?.description" class="mt-1 text-sm text-muted-foreground">
                  {{ selectedLikelihoodCriteria.description }}
                </div>
              </div>
            </div>

            <!-- ระดับผลกระทบ -->
            <div>
              <div class="flex flex-col gap-1">
                <div class="text-sm font-medium text-muted-foreground">ระดับผลกระทบ (Impact)</div>
                <div class="flex items-center gap-2">
                  <div class="text-lg font-medium">{{ riskAssessment.impact_level }}</div>
                  <div v-if="selectedImpactCriteria" class="text-md">
                    {{ selectedImpactCriteria.name }}
                  </div>
                </div>
                <div v-if="selectedImpactCriteria?.description" class="mt-1 text-sm text-muted-foreground">
                  {{ selectedImpactCriteria.description }}
                </div>
              </div>
            </div>

            <!-- หมายเหตุ -->
            <div v-if="riskAssessment.notes" class="rounded-lg border p-4">
              <div class="flex items-center gap-2 mb-2">
                <FileText class="h-4 w-4 text-muted-foreground" />
                <div class="text-sm font-medium">หมายเหตุ</div>
              </div>
              <div class="text-sm whitespace-pre-wrap">{{ riskAssessment.notes }}</div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- คอลัมน์ขวา: รายละเอียดความเสี่ยงระดับฝ่าย -->
    <div>
      <!-- การ์ดข้อมูลความเสี่ยงระดับฝ่าย -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Network class="h-5 w-5 text-orange-500" />
            ความเสี่ยงระดับฝ่าย
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <!-- ชื่อความเสี่ยงระดับฝ่าย -->
            <div>
              <div class="text-sm font-medium text-muted-foreground">ชื่อความเสี่ยง</div>
              <div class="mt-1 text-lg font-medium">
                {{ riskAssessment.division_risk?.risk_name }}
              </div>
            </div>
            
            <!-- คำอธิบายความเสี่ยงระดับฝ่าย -->
            <div v-if="riskAssessment.division_risk?.description">
              <div class="text-sm font-medium text-muted-foreground">คำอธิบาย</div>
              <div class="mt-1 text-sm whitespace-pre-wrap">
                {{ riskAssessment.division_risk?.description }}
              </div>
            </div>

            <!-- ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง -->
            <div v-if="riskAssessment.division_risk?.organizational_risk">
              <div class="text-sm font-medium text-muted-foreground">ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง</div>
              <div class="mt-1 text-sm">
                {{ riskAssessment.division_risk.organizational_risk.risk_name }}
              </div>
            </div>
          </div>
        </CardContent>
        <CardFooter class="flex flex-col border-t pt-2 pb-2">
          <div class="flex flex-col gap-2 w-full">
            <!-- ปุ่มแรก: ดูความเสี่ยงระดับฝ่าย -->
            <Button 
              variant="outline" 
              size="sm"
              class="w-full h-8 text-xs overflow-hidden"
              @click="navigateToDivisionRiskDetails"
            >
              <Link class="mr-0.5 h-3.5 w-3.5 shrink-0" />
              <span class="truncate">ดูความเสี่ยงฝ่าย</span>
            </Button>
            
            <!-- ปุ่มที่สอง: ดูความเสี่ยงระดับองค์กร -->
            <Button 
              v-if="riskAssessment.division_risk?.organizational_risk"
              variant="outline" 
              size="sm"
              class="w-full h-8 text-xs overflow-hidden"
              @click="navigateToOrganizationalRiskDetails"
            >
              <Link class="mr-0.5 h-3.5 w-3.5 shrink-0" />
              <span class="truncate">ดูความเสี่ยงองค์กร</span>
            </Button>
          </div>
        </CardFooter>
      </Card>

      <!-- คอลัมน์ขวา ส่วนล่าง: เอกสารแนบ (ถ้ามี) -->
      <div class="mt-6">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Paperclip class="h-5 w-5 text-purple-500" />
              เอกสารแนบ
              <span v-if="attachmentsCount" class="ml-auto inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-900/10 dark:text-purple-400 dark:ring-purple-400/30">
                {{ attachmentsCount }} ไฟล์
              </span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="hasAttachments" class="space-y-3">
              <div v-for="attachment in riskAssessment.attachments" :key="attachment.id" 
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
                  <Button 
                    variant="ghost" 
                    size="icon" 
                    @click="viewAttachmentFullScreen(attachment)" 
                    v-if="attachment.file_type && attachment.file_type.includes('pdf')"
                  >
                    <Eye class="h-4 w-4" />
                  </Button>
                  <Button 
                    variant="ghost" 
                    size="icon" 
                    @click="downloadAttachment(attachment)"
                  >
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
</div>

<!-- Dialog ยืนยันการลบ -->
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
</AppLayout>
</template>