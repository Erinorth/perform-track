<!-- resources/js/pages/risk_control/RiskControlShow.vue -->
<script setup lang="ts">
// ==================== นำเข้า Layout และ Navigation ====================
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

// ==================== นำเข้า Types และ Interfaces ====================
import type { RiskControl, RiskControlAttachment, DivisionRisk } from '@/types/risk-control'
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

// ==================== นำเข้า Utilities ====================
import { toast } from 'vue-sonner'

// ==================== นำเข้า Icons ====================
import { 
  ArrowLeft, 
  Pencil,
  Trash,
  Shield,
  ShieldCheck,
  ShieldAlert,
  CalendarDays,
  AlertCircle,
  FileText,
  CheckCircle2,
  XCircle,
  ClipboardList,
  Network,
  Link,
  Paperclip,
  Download,
  Eye,
  FileImage,
  FileSpreadsheet,
  User,
  Settings,
  Target,
  Activity,
  ToggleLeft,
  Building2,
  Wrench
} from 'lucide-vue-next'

// ==================== กำหนด Props ====================
const props = defineProps<{
  riskControl: RiskControl
}>()

// ==================== กำหนด Breadcrumbs ====================
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'การควบคุมความเสี่ยง',
    href: route('risk-controls.index'),
  },
  {
    title: props.riskControl.control_name || `การควบคุมความเสี่ยง #${props.riskControl.id}`,
    href: '#',
  },
]

// ==================== Reactive States ====================
const isLoading = ref(false)
const isDeleting = ref(false)

// ==================== Computed Properties ====================
// คำนวณประเภทการควบคุมพร้อมสีและไอคอน
const controlTypeInfo = computed(() => {
  const type = props.riskControl.control_type
  
  const typeMap = {
    'preventive': { 
      label: 'การป้องกัน', 
      color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
      icon: Shield,
      description: 'ป้องกันไม่ให้เหตุการณ์เสี่ยงเกิดขึ้น'
    },
    'detective': { 
      label: 'การตรวจจับ', 
      color: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
      icon: Eye,
      description: 'ตรวจจับเหตุการณ์เสี่ยงหลังจากเกิดขึ้น'
    },
    'corrective': { 
      label: 'การแก้ไข', 
      color: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400',
      icon: Wrench,
      description: 'แก้ไขเหตุการณ์เสี่ยงหลังจากเกิดขึ้น'
    },
    'compensating': { 
      label: 'การชดเชย', 
      color: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
      icon: Target,
      description: 'ชดเชยเมื่อการควบคุมหลักไม่สามารถทำงานได้'
    }
  }
  
  return typeMap[type as keyof typeof typeMap] || { 
    label: 'ไม่ระบุ', 
    color: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
    icon: AlertCircle,
    description: 'ไม่ได้ระบุประเภทการควบคุม'
  }
})

// คำนวณสถานะการควบคุมพร้อมสีและไอคอน
const statusInfo = computed(() => {
  const status = props.riskControl.status
  
  if (status === 'active') {
    return {
      label: 'ใช้งาน',
      color: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
      icon: ShieldCheck,
      description: 'การควบคุมนี้กำลังดำเนินการอยู่'
    }
  } else {
    return {
      label: 'ไม่ใช้งาน',
      color: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
      icon: ShieldAlert,
      description: 'การควบคุมนี้ไม่ได้ดำเนินการในขณะนี้'
    }
  }
})

// จัดรูปแบบวันที่สร้างและแก้ไข
const formattedDates = computed(() => {
  return {
    created: props.riskControl.created_at 
      ? new Date(props.riskControl.created_at).toLocaleDateString('th-TH', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })
      : 'ไม่ระบุ',
    updated: props.riskControl.updated_at 
      ? new Date(props.riskControl.updated_at).toLocaleDateString('th-TH', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })
      : 'ไม่ระบุ'
  }
})

// ตรวจสอบว่ามีเอกสารแนบหรือไม่
const hasAttachments = computed(() => {
  return props.riskControl.attachments && props.riskControl.attachments.length > 0
})

// คำนวณจำนวนเอกสารแนบ
const attachmentsCount = computed(() => {
  return hasAttachments.value ? props.riskControl.attachments?.length : 0
})

// ==================== Methods ====================
/**
 * ฟังก์ชันดาวน์โหลดเอกสารแนบ
 */
const downloadAttachment = (attachment: RiskControlAttachment) => {
  // แสดง toast แจ้งเตือนผู้ใช้
  toast.info('กำลังดาวน์โหลดเอกสาร', {
    description: `ไฟล์ ${attachment.filename} กำลังถูกดาวน์โหลด`,
    duration: 3000
  })
  
  // เพิ่ม log เพื่อการตรวจสอบ
  console.log('ดาวน์โหลดเอกสารแนบการควบคุมความเสี่ยง:', attachment)
  
  // เปิดลิงก์ดาวน์โหลดในแท็บใหม่
  window.open(`/risk-controls/${props.riskControl.id}/attachments/${attachment.id}/download`, '_blank')
}

/**
 * ฟังก์ชันเปิดเอกสารแนบแบบเต็มหน้าจอ
 */
const viewAttachmentFullScreen = (attachment: RiskControlAttachment) => {
  // เปิดหน้าดูไฟล์แนบในแท็บใหม่
  window.open(`/risk-controls/${props.riskControl.id}/attachments/${attachment.id}/view`, '_blank')
  
  console.log('เปิดหน้าดูไฟล์แนบการควบคุมความเสี่ยงแบบเต็มจอ:', attachment)
  
  toast.info('กำลังเปิดไฟล์แนบ', {
    description: `กำลังเปิดไฟล์ ${attachment.filename}`,
    duration: 3000
  })
}

// ฟังก์ชันนำทางไปยังหน้าแก้ไข
const navigateToEdit = () => {
  router.visit(route('risk-controls.edit', props.riskControl.id))
  console.log('นำทางไปยังหน้าแก้ไขการควบคุมความเสี่ยง:', props.riskControl.id)
}

// ฟังก์ชันนำทางไปยังหน้าแสดงรายละเอียดความเสี่ยงระดับฝ่าย
const navigateToDivisionRiskDetails = () => {
  router.visit(route('division-risks.show', props.riskControl.division_risk?.id))
  console.log('นำทางไปยังหน้าแสดงรายละเอียดความเสี่ยงระดับฝ่าย:', props.riskControl.division_risk?.id)
}

// ฟังก์ชันนำทางไปยังหน้าแสดงรายละเอียดความเสี่ยงระดับองค์กร
const navigateToOrganizationalRiskDetails = () => {
  router.visit(route('organizational-risks.show', props.riskControl.division_risk?.organizational_risk?.id))
  console.log('นำทางไปยังหน้าแสดงรายละเอียดความเสี่ยงระดับองค์กร:', props.riskControl.division_risk?.organizational_risk?.id)
}

// ฟังก์ชันแสดง dialog ยืนยันการลบ
const confirmDelete = () => {
  openConfirm({
    title: 'ยืนยันการลบการควบคุมความเสี่ยง',
    message: `คุณต้องการลบการควบคุม "${props.riskControl.control_name}" หรือไม่? การลบนี้ไม่สามารถย้อนกลับได้`,
    confirmText: 'ลบ',
    cancelText: 'ยกเลิก',
    onConfirm: async () => {
      await deleteRiskControl()
    }
  })
}

// ฟังก์ชันลบการควบคุมความเสี่ยง
const deleteRiskControl = async () => {
  isDeleting.value = true
  
  // log สำหรับตรวจสอบ
  console.log('เริ่มลบการควบคุมความเสี่ยง:', props.riskControl.id)
  
  try {
    await router.delete(route('risk-controls.destroy', props.riskControl.id), {
      onSuccess: () => {
        toast.success('ลบการควบคุมความเสี่ยงเรียบร้อยแล้ว', {
          description: 'ข้อมูลถูกลบเรียบร้อย',
          duration: 3000
        })
        // log สำเร็จ
        console.log('ลบการควบคุมความเสี่ยงสำเร็จ:', props.riskControl.id)
        // กลับไปหน้า index หลังลบสำเร็จ
        router.visit(route('risk-controls.index'))
      },
      onError: (err) => {
        toast.error('เกิดข้อผิดพลาด', {
          description: 'ไม่สามารถลบข้อมูลได้: ' + (err.message || 'ข้อผิดพลาดที่ไม่ทราบสาเหตุ'),
          duration: 4000
        })
        console.error('ลบการควบคุมความเสี่ยงล้มเหลว:', err)
      },
      onFinish: () => {
        isDeleting.value = false
      }
    })
  } catch (e: any) {
    isDeleting.value = false
    toast.error('เกิดข้อผิดพลาด', {
      description: 'ไม่สามารถลบข้อมูลได้: ' + (e.message || 'ข้อผิดพลาดที่ไม่ทราบสาเหตุ'),
      duration: 4000
    })
    console.error('ลบการควบคุมความเสี่ยงล้มเหลว:', e)
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

// ==================== Lifecycle Hooks ====================
onMounted(() => {
  isLoading.value = true
  
  setTimeout(() => {
    isLoading.value = false
    console.log('โหลดข้อมูลรายละเอียดสำหรับการควบคุมความเสี่ยง:', props.riskControl.id)
  }, 300)
})

// เพิ่ม openConfirm จาก useConfirm
const { isOpen, options, isProcessing, handleConfirm, handleCancel, openConfirm } = useConfirm()
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head :title="`รายละเอียดการควบคุมความเสี่ยง: ${riskControl.control_name}`" />

  <!-- ใช้ Layout หลักของแอปพลิเคชันพร้อมส่ง breadcrumbs เป็น prop -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- พื้นที่หลักของหน้า -->
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- หัวข้อและปุ่มดำเนินการ -->
      <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-2">
          <Shield class="h-6 w-6 text-blue-600" />
          <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            รายละเอียดการควบคุมความเสี่ยง
          </h1>
          <div v-if="isLoading" class="animate-pulse">
            <div class="h-5 w-5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button 
            variant="outline" 
            size="sm"
            @click="router.visit(route('risk-controls.index'))"
          >
            <ArrowLeft class="mr-1 h-4 w-4" />
            กลับไปรายการควบคุมความเสี่ยง
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
        <!-- คอลัมน์ซ้าย: ข้อมูลหลักของการควบคุมความเสี่ยง -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <component :is="controlTypeInfo.icon" class="h-5 w-5" :class="controlTypeInfo.color.includes('blue') ? 'text-blue-500' : controlTypeInfo.color.includes('green') ? 'text-green-500' : controlTypeInfo.color.includes('orange') ? 'text-orange-500' : 'text-purple-500'" />
                {{ riskControl.control_name }}
              </CardTitle>
              <CardDescription class="text-sm text-muted-foreground flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5">
                  <CalendarDays class="h-4 w-4" />
                  สร้างเมื่อ: {{ formattedDates.created }}
                </div>
                <div class="flex items-center gap-1.5">
                  <Activity class="h-4 w-4" />
                  แก้ไขล่าสุด: {{ formattedDates.updated }}
                </div>
              </CardDescription>
            </CardHeader>
            <CardContent>
              <!-- ข้อมูลหลักของการควบคุมความเสี่ยง -->
              <div class="space-y-6">
                <!-- ส่วนของประเภทและสถานะการควบคุม -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- ประเภทการควบคุม -->
                  <div class="flex flex-col space-y-2">
                    <div class="text-sm font-medium text-muted-foreground">ประเภทการควบคุม</div>
                    <div class="flex items-center gap-2">
                      <Badge :class="controlTypeInfo.color" class="text-sm">
                        <component :is="controlTypeInfo.icon" class="w-3 h-3 mr-1" />
                        {{ controlTypeInfo.label }}
                      </Badge>
                    </div>
                    <div class="text-xs text-muted-foreground">
                      {{ controlTypeInfo.description }}
                    </div>
                  </div>

                  <!-- สถานะการควบคุม -->
                  <div class="flex flex-col space-y-2">
                    <div class="text-sm font-medium text-muted-foreground">สถานะการควบคุม</div>
                    <div class="flex items-center gap-2">
                      <Badge :class="statusInfo.color" class="text-sm">
                        <component :is="statusInfo.icon" class="w-3 h-3 mr-1" />
                        {{ statusInfo.label }}
                      </Badge>
                    </div>
                    <div class="text-xs text-muted-foreground">
                      {{ statusInfo.description }}
                    </div>
                  </div>
                </div>

                <!-- เส้นคั่น -->
                <div class="border-t dark:border-gray-800"></div>

                <!-- ผู้รับผิดชอบ -->
                <div v-if="riskControl.owner">
                  <div class="flex flex-col gap-1">
                    <div class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                      <User class="h-4 w-4" />
                      ผู้รับผิดชอบ
                    </div>
                    <div class="text-lg font-medium">{{ riskControl.owner }}</div>
                  </div>
                </div>

                <!-- คำอธิบายการควบคุม -->
                <div v-if="riskControl.description">
                  <div class="flex flex-col gap-1">
                    <div class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                      <FileText class="h-4 w-4" />
                      คำอธิบายการควบคุม
                    </div>
                    <div class="text-sm whitespace-pre-wrap">{{ riskControl.description }}</div>
                  </div>
                </div>

                <!-- รายละเอียดการดำเนินการ -->
                <div v-if="riskControl.implementation_details" class="rounded-lg border p-4">
                  <div class="flex items-center gap-2 mb-2">
                    <Settings class="h-4 w-4 text-muted-foreground" />
                    <div class="text-sm font-medium">รายละเอียดการดำเนินการ</div>
                  </div>
                  <div class="text-sm whitespace-pre-wrap">{{ riskControl.implementation_details }}</div>
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
                    {{ riskControl.division_risk?.risk_name }}
                  </div>
                </div>
                
                <!-- คำอธิบายความเสี่ยงระดับฝ่าย -->
                <div v-if="riskControl.division_risk?.description">
                  <div class="text-sm font-medium text-muted-foreground">คำอธิบาย</div>
                  <div class="mt-1 text-sm whitespace-pre-wrap">
                    {{ riskControl.division_risk?.description }}
                  </div>
                </div>

                <!-- ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง -->
                <div v-if="riskControl.division_risk?.organizational_risk">
                  <div class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                    <Building2 class="h-4 w-4" />
                    ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง
                  </div>
                  <div class="mt-1 text-sm">
                    {{ riskControl.division_risk.organizational_risk.risk_name }}
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
                  v-if="riskControl.division_risk?.organizational_risk"
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
                  <div v-for="attachment in riskControl.attachments" :key="attachment.id" 
                    class="flex items-center justify-between gap-4 rounded-lg border p-3 text-sm shadow-sm transition-all hover:bg-accent hover:text-accent-foreground">
                    <!-- แสดงข้อมูลไฟล์ -->
                    <div class="flex flex-1 items-center gap-3 truncate">
                      <!-- แสดงไอคอนตามประเภทไฟล์ -->
                      <component :is="getFileIcon(attachment.filetype)" class="h-5 w-5 flex-shrink-0 text-gray-400" />
                      
                      <!-- ชื่อไฟล์และข้อมูลขนาด -->
                      <div class="flex-1 truncate">
                        <p class="truncate font-medium">{{ attachment.filename }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          {{ formatFileSize(attachment.filesize) }}
                        </p>
                      </div>
                    </div>
                    
                    <!-- ปุ่มดำเนินการกับไฟล์ -->
                    <div class="flex items-center gap-2">
                      <Button 
                        variant="ghost" 
                        size="icon" 
                        @click="viewAttachmentFullScreen(attachment)" 
                        v-if="attachment.filetype && (attachment.filetype.includes('pdf') || attachment.filetype.includes('image'))"
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
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ยังไม่มีการอัพโหลดเอกสารแนบสำหรับการควบคุมนี้</p>
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

<style scoped>
/* Animation สำหรับ loading */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* สไตล์สำหรับ hover effects */
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
  
  .lg\\:col-span-2 {
    grid-column: span 1;
  }
}
</style>
