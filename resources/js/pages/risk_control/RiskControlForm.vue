<!-- 
  ไฟล์: resources/js/pages/risk_control/RiskControlForm.vue
  คำอธิบาย: หน้าฟอร์มสำหรับสร้างและแก้ไขข้อมูลการควบคุมความเสี่ยง
  รองรับทั้ง create และ edit mode ตามข้อมูลที่ส่งมา
  ทำหน้าที่: แสดงฟอร์มสำหรับจัดการข้อมูลการควบคุมความเสี่ยง, อัปโหลดเอกสารแนบ
  หลักการ: ใช้ฟอร์มแบบเต็มหน้าจอ สำหรับการจัดการข้อมูลโดยเฉพาะ
  ใช้ร่วมกับ: RiskControlController.php ในฝั่ง Backend
-->

<script setup lang="ts">
// ==================== นำเข้า Layout และ Navigation ====================
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

// ==================== นำเข้า Types และ Interfaces ====================
import type { BreadcrumbItem } from '@/types'
import type { DivisionRisk, RiskControl, RiskControlAttachment } from '@/types/risk-control'

// ==================== นำเข้า Vue Composition API ====================
import { ref, computed, onMounted, watch } from 'vue'

// ==================== นำเข้า Components ====================
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

// เพิ่ม import DivisionRiskCombobox
import DivisionRiskCombobox from '@/components/forms/DivisionRiskCombobox.vue'

// ==================== นำเข้า Utilities ====================
import { toast } from 'vue-sonner'

// ==================== นำเข้า Icons ====================
import { 
  ArrowLeft as ArrowLeftIcon, 
  Info as InfoIcon,
  Save as SaveIcon,
  X as XIcon,
  Upload as UploadIcon,
  XCircle as XCircleIcon,
  Trash2 as Trash2Icon,
  HelpCircle as HelpCircleIcon,
  Loader2 as Loader2Icon,
  AlertCircle as AlertCircleIcon,
  Plus as PlusIcon,
  Edit as EditIcon,
  Shield as ShieldIcon,
  User as UserIcon,
  Settings as SettingsIcon,
} from 'lucide-vue-next'

// ==================== แก้ไข Types Interface ====================
interface RiskControlAttachmentData {
  id: number
  filename: string
  filepath: string
  filetype: string | null
  filesize: number | null
}

// แก้ไข Props interface ให้รองรับทั้ง create และ edit mode
interface Props {
  // ข้อมูลการควบคุมความเสี่ยง (optional สำหรับ create mode)
  riskControl?: {
    id: number
    control_name: string
    description: string | null
    owner: string | null
    status: 'active' | 'inactive'
    control_type: 'preventive' | 'detective' | 'corrective' | 'compensating' | null
    implementation_details: string | null
    division_risk_id: number
    division_risk: {
      id: number
      risk_name: string
      description: string
      organizational_risk?: {
        id: number
        risk_name: string
      }
    }
    attachments?: RiskControlAttachmentData[]
  }
  
  // ใช้ DivisionRisk type จาก types
  divisionRisks: DivisionRisk[]
}

const props = defineProps<Props>()

// ตรวจสอบ mode การทำงาน
const isEditMode = computed(() => !!props.riskControl?.id)
const isCreateMode = computed(() => !props.riskControl?.id)

// ==================== กำหนด Breadcrumbs แบบ dynamic ====================
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  {
    title: 'การควบคุมความเสี่ยง',
    href: route('risk-controls.index'),
  },
  {
    title: isEditMode.value ? 'แก้ไขการควบคุมความเสี่ยง' : 'เพิ่มการควบคุมความเสี่ยงใหม่',
    href: '#',
  }
])

// หัวข้อหน้าแบบ dynamic
const pageTitle = computed(() => 
  isEditMode.value ? 'แก้ไขการควบคุมความเสี่ยง' : 'เพิ่มการควบคุมความเสี่ยงใหม่'
)

const pageDescription = computed(() => 
  isEditMode.value 
    ? 'แก้ไขข้อมูลการควบคุมความเสี่ยงและเอกสารแนบ' 
    : 'สร้างข้อมูลการควบคุมความเสี่ยงใหม่และเพิ่มเอกสารแนบ'
)

// ==================== Reactive States ====================
const isLoading = ref<boolean>(true)
const showHelpControlType = ref<boolean>(false)
const showHelpStatus = ref<boolean>(false)

// เปลี่ยนจาก selectedDivisionRisk เป็น DivisionRisk object
const selectedDivisionRisk = ref<DivisionRisk | null>(null)

const existingAttachments = ref<Array<{
  id: number
  filename: string
  filepath: string
  filesize: number | null
  filetype: string | null
  url: string
  toDelete?: boolean
}>>([])
const selectedFiles = ref<File[]>([])
const fileNames = ref<string[]>([])
const attachmentsToDelete = ref<number[]>([])

// ==================== Form สำหรับส่งข้อมูล ====================
const form = useForm({
  control_name: props.riskControl?.control_name || '',
  description: props.riskControl?.description || '',
  owner: props.riskControl?.owner || '',
  status: props.riskControl?.status || 'active',
  control_type: props.riskControl?.control_type || '',
  implementation_details: props.riskControl?.implementation_details || '',
  division_risk_id: props.riskControl?.division_risk_id?.toString() || '',
  attachments: null as File[] | null,
  delete_attachments: [] as number[]
})

// ==================== Computed Properties ====================
// กำหนดชื่อหน้าแบบ dynamic
const headTitle = computed(() => 
  isEditMode.value ? 'แก้ไขการควบคุมความเสี่ยง' : 'เพิ่มการควบคุมความเสี่ยงใหม่'
)

// ===============================
// Event Handlers สำหรับ DivisionRiskCombobox
// ===============================

/**
 * หาความเสี่ยงหน่วยงานจาก ID
 */
const findDivisionRiskById = (id: number | null): DivisionRisk | null => {
  if (!id || !props.divisionRisks) return null
  return props.divisionRisks.find(risk => risk.id === id) || null
}

/**
 * จัดการการเลือกความเสี่ยงหน่วยงาน
 */
const handleDivisionRiskSelect = (risk: DivisionRisk) => {
  console.log('เลือกความเสี่ยงระดับฝ่าย:', {
    id: risk.id,
    risk_name: risk.risk_name,
    organizational_risk: risk.organizational_risk?.risk_name || 'ไม่ระบุ'
  })
  
  selectedDivisionRisk.value = risk
  form.division_risk_id = risk.id.toString()
  
  toast.success(`เลือกความเสี่ยง: ${risk.risk_name}`, {
    duration: 2000
  })
}

/**
 * จัดการการล้างค่าความเสี่ยงหน่วยงาน
 */
const handleDivisionRiskClear = () => {
  console.log('ล้างความเสี่ยงระดับฝ่าย')
  selectedDivisionRisk.value = null
  form.division_risk_id = ''
}

// ==================== Methods ====================
/**
 * นำทางกลับไปยังหน้ารายการการควบคุมความเสี่ยง
 */
const navigateBack = () => {
  router.visit(route('risk-controls.index'))
}

/**
 * เมื่อเลือกสถานะการควบคุม - แก้ไขให้รองรับ AcceptableValue
 */
const onStatusChange = (value: unknown) => {
  const stringValue = value === null || value === undefined ? '' : String(value)
  form.status = stringValue as 'active' | 'inactive'
  console.log('เลือกสถานะการควบคุม:', stringValue)
}

/**
 * เมื่อเลือกประเภทการควบคุม - แก้ไขให้รองรับ AcceptableValue
 */
const onControlTypeChange = (value: unknown) => {
  const stringValue = value === null || value === undefined ? '' : String(value)
  form.control_type = stringValue
  console.log('เลือกประเภทการควบคุม:', stringValue)
}

/**
 * โหลดข้อมูลเอกสารแนบ
 */
const loadAttachments = () => {
  if (props.riskControl?.attachments && props.riskControl.attachments.length > 0) {
    existingAttachments.value = props.riskControl.attachments.map(attachment => ({
      id: attachment.id,
      filename: attachment.filename,
      filepath: attachment.filepath,
      filesize: attachment.filesize,
      filetype: attachment.filetype,
      url: route('risk-controls.attachments.download', attachment.id),
      toDelete: false
    }))
    console.log('โหลดเอกสารแนบ:', existingAttachments.value.length, 'ไฟล์')
  }
}

/**
 * จัดการไฟล์ที่อัปโหลด
 */
const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (!input.files || input.files.length === 0) return
  
  // ตรวจสอบไฟล์ที่อัปโหลด
  const validTypes = [
    'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg', 'image/png'
  ]
  
  const maxSize = 10 * 1024 * 1024 // 10MB
  const newFiles = Array.from(input.files)
  
  const invalidFiles = newFiles.filter(file => 
    !validTypes.includes(file.type) || file.size > maxSize
  )
  
  if (invalidFiles.length > 0) {
    toast.error('ไฟล์ไม่ถูกต้อง', {
      description: 'บางไฟล์มีรูปแบบไม่ถูกต้องหรือขนาดเกิน 10MB'
    })
    return
  }
  
  // เพิ่มไฟล์ที่ผ่านการตรวจสอบ
  selectedFiles.value = [...selectedFiles.value, ...newFiles]
  fileNames.value = [...fileNames.value, ...newFiles.map(file => file.name)]
  
  // อัปเดตฟอร์ม
  form.attachments = selectedFiles.value
  
  console.log('เพิ่มไฟล์แนบ:', newFiles.length, 'ไฟล์')
  input.value = '' // รีเซ็ต input
}

/**
 * ลบไฟล์ที่เลือกก่อนอัปโหลด
 */
const removeSelectedFile = (index: number) => {
  fileNames.value.splice(index, 1)
  selectedFiles.value.splice(index, 1)
  form.attachments = selectedFiles.value.length > 0 ? selectedFiles.value : null
  
  console.log('ลบไฟล์ที่เลือก:', index)
}

/**
 * มาร์กเอกสารแนบเพื่อลบ
 */
const markAttachmentForDeletion = (id: number) => {
  const index = existingAttachments.value.findIndex(attachment => attachment.id === id)
  if (index !== -1) {
    existingAttachments.value[index].toDelete = true
    attachmentsToDelete.value.push(id)
    form.delete_attachments = attachmentsToDelete.value
    
    console.log('มาร์กเอกสารแนบเพื่อลบ:', id)
    
    toast.success('ไฟล์ถูกมาร์กเพื่อลบเมื่อบันทึก', {
      description: 'ไฟล์จะถูกลบเมื่อบันทึกข้อมูล'
    })
  }
}

/**
 * เปิดเอกสารแนบ
 */
const openAttachment = (url: string) => {
  window.open(url, '_blank')
  console.log('เปิดเอกสารแนบ:', url)
}

/**
 * ตรวจสอบความถูกต้องของฟอร์ม
 */
const validateForm = () => {
  let isValid = true
  const errors: string[] = []
  
  // ตรวจสอบข้อมูลสำคัญ
  if (!form.control_name.trim()) {
    errors.push('กรุณาระบุชื่อการควบคุมความเสี่ยง')
    isValid = false
  }
  
  if (!form.division_risk_id) {
    errors.push('กรุณาเลือกความเสี่ยงระดับฝ่าย')
    isValid = false
  }
  
  if (!form.status) {
    errors.push('กรุณาเลือกสถานะการควบคุม')
    isValid = false
  }
  
  if (!isValid) {
    toast.warning('กรุณาตรวจสอบข้อมูล', {
      icon: InfoIcon,
      description: errors.join(', ')
    })
  }
  
  return isValid
}

/**
 * ส่งข้อมูลการควบคุม
 */
const submitForm = () => {
  // ตรวจสอบความถูกต้องของฟอร์ม
  if (!validateForm()) return
  
  // แสดงข้อความกำลังบันทึก
  const loadingMessage = isEditMode.value ? 'กำลังบันทึกการแก้ไข' : 'กำลังสร้างการควบคุมใหม่'
  toast.loading(loadingMessage, {
    id: 'saving-control',
    duration: 60000
  })
  
  console.log(`กำลังส่งข้อมูล, mode: ${isEditMode.value ? 'แก้ไข' : 'สร้างใหม่'}${isEditMode.value ? ', id: ' + props.riskControl?.id : ''}`)
  
  // กำหนด route และ method ตาม mode
  const submitRoute = isEditMode.value 
    ? route('risk-controls.update', props.riskControl!.id)
    : route('risk-controls.store')
  
  const submitMethod = isEditMode.value ? 'post' : 'post' // ใช้ post ทั้งคู่เพราะ Laravel จะจัดการ method spoofing
  
  // ส่งข้อมูลไปยัง server
  form[submitMethod](submitRoute, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      const successMessage = isEditMode.value 
        ? 'บันทึกการแก้ไขเรียบร้อยแล้ว' 
        : 'สร้างการควบคุมความเสี่ยงเรียบร้อยแล้ว'
      
      toast.success(successMessage, {
        id: 'saving-control'
      })
      
      // นำทางกลับไปยังหน้ารายการการควบคุมความเสี่ยง
      setTimeout(() => {
        router.visit(route('risk-controls.index'))
      }, 1000)
    },
    onError: (errors) => {
      console.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล:', errors)
      
      const errorMessage = isEditMode.value 
        ? 'เกิดข้อผิดพลาดในการบันทึกการแก้ไข' 
        : 'เกิดข้อผิดพลาดในการสร้างการควบคุม'
      
      toast.error(errorMessage, {
        id: 'saving-control',
        description: 'กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง'
      })
    }
  })
}

/**
 * สลับการแสดงข้อความช่วยเหลือสำหรับประเภทการควบคุม
 */
const toggleHelpControlType = () => {
  showHelpControlType.value = !showHelpControlType.value
}

/**
 * สลับการแสดงข้อความช่วยเหลือสำหรับสถานะ
 */
const toggleHelpStatus = () => {
  showHelpStatus.value = !showHelpStatus.value
}

/**
 * ฟังก์ชันสำหรับแสดงไอคอนตามประเภทไฟล์
 */
const getFileIcon = (fileName: string) => {
  const extension = fileName.split('.').pop()?.toLowerCase()
  
  if (extension === 'pdf') return AlertCircleIcon
  if (['doc', 'docx'].includes(extension || '')) return AlertCircleIcon
  if (['xls', 'xlsx'].includes(extension || '')) return AlertCircleIcon
  if (['jpg', 'jpeg', 'png'].includes(extension || '')) return AlertCircleIcon
  
  return AlertCircleIcon
}

/**
 * ฟังก์ชันจัดรูปแบบขนาดไฟล์
 */
const formatFileSize = (bytes: number) => {
  if (!bytes) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// ==================== Lifecycle Hooks ====================
onMounted(() => {
  isLoading.value = true
  
  if (isEditMode.value && props.riskControl) {
    // โหลดข้อมูลสำหรับการแก้ไข
    console.log('กำลังโหลดข้อมูลสำหรับแก้ไข:', props.riskControl.id)
    
    // โหลดข้อมูลเอกสารแนบ
    loadAttachments()
    
    // ตั้งค่า selectedDivisionRisk จากข้อมูลที่มีอยู่
    selectedDivisionRisk.value = findDivisionRiskById(props.riskControl.division_risk_id)
  } else {
    // Create mode - ใช้ค่าเริ่มต้นที่กำหนดไว้แล้วในฟอร์ม
    console.log('เริ่มต้นสำหรับการสร้างใหม่')
  }
  
  setTimeout(() => {
    isLoading.value = false
    console.log('โหลดข้อมูลเสร็จสิ้น')
  }, 300)
})
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head :title="headTitle" />

  <!-- ใช้ Layout หลักของแอปพลิเคชัน -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- พื้นที่หลักของหน้า -->
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- หัวข้อหน้า -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-2 gap-4">
        <div>
          <h1 class="text-2xl font-bold flex items-center gap-2">
            <component :is="isEditMode ? EditIcon : PlusIcon" class="h-6 w-6" />
            {{ pageTitle }}
          </h1>
          <p class="text-gray-500 mt-1">{{ pageDescription }}</p>
        </div>
        <Button variant="outline" @click="navigateBack" class="flex items-center gap-2">
          <ArrowLeftIcon class="h-4 w-4" />
          <span>กลับไปหน้ารายการ</span>
        </Button>
      </div>

      <!-- แสดง Loading indicator ระหว่างโหลดข้อมูล -->
      <div v-if="isLoading" class="flex justify-center items-center py-20">
        <Loader2Icon class="h-8 w-8 animate-spin text-primary" />
        <span class="ml-2">กำลังโหลดข้อมูล...</span>
      </div>

      <!-- แบบฟอร์มกรอกข้อมูล -->
      <form @submit.prevent="submitForm" class="space-y-6" v-else>
        <!-- ส่วนข้อมูลหลักของการควบคุม -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden dark:bg-gray-800">
          <div class="p-6 space-y-6">
            <h2 class="text-lg font-medium border-b pb-2 flex items-center gap-2">
              <ShieldIcon class="h-5 w-5" />
              ข้อมูลการควบคุมความเสี่ยง
            </h2>
            
            <!-- ชื่อการควบคุมความเสี่ยง -->
            <div>
              <Label for="control_name">
                ชื่อการควบคุมความเสี่ยง <span class="text-red-500">*</span>
              </Label>
              <Input
                id="control_name"
                v-model="form.control_name"
                type="text"
                placeholder="ระบุชื่อการควบคุมความเสี่ยง"
                :class="{ 'border-red-500': form.errors.control_name }"
              />
              <p v-if="form.errors.control_name" class="text-sm text-red-500 mt-1">
                {{ form.errors.control_name }}
              </p>
            </div>

            <!-- แทนที่ Select ด้วย DivisionRiskCombobox -->
            <div class="grid gap-2">
              <Label class="flex items-center gap-1">
                ความเสี่ยงระดับฝ่าย <span class="text-red-500">*</span>
                <Button 
                  type="button" 
                  variant="ghost" 
                  size="icon"
                  class="h-5 w-5 text-gray-500 hover:text-gray-700"
                  @click="toggleHelpControlType"
                >
                  <HelpCircleIcon class="h-4 w-4" />
                </Button>
              </Label>
              
              <!-- คำอธิบายสำหรับความเสี่ยงฝ่าย -->
              <div v-if="showHelpControlType" class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-900 p-2 rounded-md mb-1">
                เลือกความเสี่ยงระดับฝ่ายที่ต้องการสร้างการควบคุม<br>
                การควบคุมความเสี่ยงจะเชื่อมโยงกับความเสี่ยงระดับฝ่ายที่เลือก
              </div>
              
              <!-- ใช้ DivisionRiskCombobox แทน Select -->
              <DivisionRiskCombobox
                :division-risks="props.divisionRisks || []"
                v-model="selectedDivisionRisk"
                placeholder="เลือกความเสี่ยงฝ่ายที่ต้องการสร้างการควบคุม..."
                :required="true"
                :disabled="form.processing"
                show-organizational-risk
                @select="handleDivisionRiskSelect"
                @clear="handleDivisionRiskClear"
              >
                <template #error>
                  <p v-if="form.errors.division_risk_id" class="text-sm text-red-500 mt-1">
                    {{ form.errors.division_risk_id }}
                  </p>
                </template>
                
                <template #help>
                  <p v-if="selectedDivisionRisk" class="text-xs text-muted-foreground mt-1">
                    <strong>รายละเอียด:</strong> {{ selectedDivisionRisk.description }}
                    <span v-if="selectedDivisionRisk.organizational_risk">
                      <br><strong>ความเสี่ยงองค์กรที่เกี่ยวข้อง:</strong> {{ selectedDivisionRisk.organizational_risk.risk_name }}
                    </span>
                  </p>
                </template>
              </DivisionRiskCombobox>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- ประเภทการควบคุม -->
              <div>
                <div class="flex items-center justify-between">
                  <Label for="control_type">
                    ประเภทการควบคุม
                  </Label>
                  <Button 
                    type="button" 
                    variant="ghost" 
                    size="icon"
                    class="h-5 w-5 text-gray-500 hover:text-gray-700"
                    @click="toggleHelpControlType"
                  >
                    <HelpCircleIcon class="h-4 w-4" />
                  </Button>
                </div>
                
                <!-- ข้อความช่วยเหลือสำหรับประเภทการควบคุม -->
                <div v-if="showHelpControlType" class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-900 p-2 rounded-md mb-1">
                  <p class="font-medium mb-1">ประเภทการควบคุมความเสี่ยง:</p>
                  <ul class="list-disc pl-4">
                    <li><span class="font-medium">การป้องกัน (Preventive):</span> ป้องกันไม่ให้เหตุการณ์เสี่ยงเกิดขึ้น</li>
                    <li><span class="font-medium">การตรวจจับ (Detective):</span> ตรวจจับเหตุการณ์เสี่ยงหลังจากเกิดขึ้น</li>
                    <li><span class="font-medium">การแก้ไข (Corrective):</span> แก้ไขเหตุการณ์เสี่ยงหลังจากเกิดขึ้น</li>
                    <li><span class="font-medium">การชดเชย (Compensating):</span> ชดเชยเมื่อการควบคุมหลักไม่สามารถทำงานได้</li>
                  </ul>
                </div>
                
                <Select 
                  v-model="form.control_type"
                  @update:model-value="onControlTypeChange"
                >
                  <SelectTrigger 
                    class="w-full mt-1" 
                    :class="{ 'border-red-500': form.errors.control_type }"
                  >
                    <SelectValue placeholder="เลือกประเภทการควบคุม" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="preventive">การป้องกัน (Preventive)</SelectItem>
                    <SelectItem value="detective">การตรวจจับ (Detective)</SelectItem>
                    <SelectItem value="corrective">การแก้ไข (Corrective)</SelectItem>
                    <SelectItem value="compensating">การชดเชย (Compensating)</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.control_type" class="text-sm text-red-500 mt-1">
                  {{ form.errors.control_type }}
                </p>
              </div>

              <!-- สถานะการควบคุม -->
              <div>
                <div class="flex items-center justify-between">
                  <Label for="status">
                    สถานะการควบคุม <span class="text-red-500">*</span>
                  </Label>
                  <Button 
                    type="button" 
                    variant="ghost" 
                    size="icon"
                    class="h-5 w-5 text-gray-500 hover:text-gray-700"
                    @click="toggleHelpStatus"
                  >
                    <HelpCircleIcon class="h-4 w-4" />
                  </Button>
                </div>
                
                <!-- ข้อความช่วยเหลือสำหรับสถานะ -->
                <div v-if="showHelpStatus" class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-900 p-2 rounded-md mb-1">
                  <p class="font-medium mb-1">สถานะการควบคุม:</p>
                  <ul class="list-disc pl-4">
                    <li><span class="font-medium">ใช้งาน:</span> การควบคุมนี้กำลังดำเนินการอยู่</li>
                    <li><span class="font-medium">ไม่ใช้งาน:</span> การควบคุมนี้ไม่ได้ดำเนินการในขณะนี้</li>
                  </ul>
                </div>
                
                <Select 
                  v-model="form.status"
                  @update:model-value="onStatusChange"
                >
                  <SelectTrigger 
                    class="w-full mt-1" 
                    :class="{ 'border-red-500': form.errors.status }"
                  >
                    <SelectValue placeholder="เลือกสถานะการควบคุม" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="active">ใช้งาน</SelectItem>
                    <SelectItem value="inactive">ไม่ใช้งาน</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.status" class="text-sm text-red-500 mt-1">
                  {{ form.errors.status }}
                </p>
              </div>
            </div>

            <!-- ผู้รับผิดชอบ -->
            <div>
              <Label for="owner" class="flex items-center gap-1">
                <UserIcon class="h-4 w-4" />
                ผู้รับผิดชอบ
              </Label>
              <Input
                id="owner"
                v-model="form.owner"
                type="text"
                placeholder="ระบุชื่อผู้รับผิดชอบการควบคุมนี้"
                class="mt-1"
              />
              <p v-if="form.errors.owner" class="text-sm text-red-500 mt-1">
                {{ form.errors.owner }}
              </p>
            </div>

            <!-- คำอธิบายการควบคุม -->
            <div>
              <Label for="description">คำอธิบายการควบคุม</Label>
              <Textarea
                id="description"
                v-model="form.description"
                class="mt-1"
                rows="3"
                placeholder="อธิบายรายละเอียดของการควบคุมความเสี่ยงนี้"
              />
              <p v-if="form.errors.description" class="text-sm text-red-500 mt-1">
                {{ form.errors.description }}
              </p>
            </div>

            <!-- รายละเอียดการดำเนินการ -->
            <div>
              <Label for="implementation_details" class="flex items-center gap-1">
                <SettingsIcon class="h-4 w-4" />
                รายละเอียดการดำเนินการ
              </Label>
              <Textarea
                id="implementation_details"
                v-model="form.implementation_details"
                class="mt-1"
                rows="4"
                placeholder="อธิบายวิธีการดำเนินการและขั้นตอนของการควบคุมนี้"
              />
              <p v-if="form.errors.implementation_details" class="text-sm text-red-500 mt-1">
                {{ form.errors.implementation_details }}
              </p>
            </div>
          </div>
        </div>

        <!-- ส่วนของเอกสารแนับ -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden dark:bg-gray-800">
          <div class="p-6 space-y-4">
            <h2 class="text-lg font-medium">เอกสารแนบ</h2>
            
            <!-- แสดงเอกสารแนบที่มีอยู่แล้ว (กรณีแก้ไข) -->
            <div v-if="isEditMode && existingAttachments.length > 0" class="mb-4">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เอกสารแนบปัจจุบัน:</p>
              <ul class="space-y-2">
                <li 
                  v-for="attachment in existingAttachments.filter(a => !a.toDelete)" 
                  :key="attachment.id" 
                  class="flex flex-wrap items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-md text-sm border border-gray-200 dark:border-gray-700"
                >
                  <!-- ส่วนแสดงข้อมูลไฟล์ -->
                  <div 
                    class="flex items-center gap-2 flex-1 min-w-0 overflow-hidden" 
                    @click="openAttachment(attachment.url)" 
                    style="cursor: pointer"
                  >
                    <component :is="getFileIcon(attachment.filename)" class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate max-w-[200px] sm:max-w-[300px]">{{ attachment.filename }}</span>
                    <span class="text-xs text-gray-500 flex-shrink-0">{{ formatFileSize(attachment.filesize || 0) }}</span>
                  </div>
                  
                  <Button 
                    type="button" 
                    variant="ghost" 
                    size="sm" 
                    @click="markAttachmentForDeletion(attachment.id)"
                    class="text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 ml-1 flex-shrink-0"
                    aria-label="ลบเอกสาร"
                  >
                    <Trash2Icon class="h-4 w-4" />
                  </Button>
                </li>
              </ul>
            </div>
            
            <!-- แสดงไฟล์ที่เพิ่งอัปโหลด -->
            <div v-if="fileNames.length > 0" class="mb-4">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ไฟล์ที่เลือกไว้:</p>
              <ul class="space-y-2">
                <li 
                  v-for="(fileName, index) in fileNames" 
                  :key="index"
                  class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-md text-sm border border-gray-200 dark:border-gray-700"
                >
                  <div class="flex items-center gap-2 flex-1 overflow-hidden">
                    <component :is="getFileIcon(fileName)" class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate">{{ fileName }}</span>
                    <span class="text-xs text-gray-500 flex-shrink-0">{{ formatFileSize(selectedFiles[index].size) }}</span>
                  </div>
                  
                  <Button 
                    type="button" 
                    variant="ghost" 
                    size="sm" 
                    @click="removeSelectedFile(index)"
                    class="text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 ml-1 flex-shrink-0"
                  >
                    <XCircleIcon class="h-4 w-4" />
                  </Button>
                </li>
              </ul>
            </div>
            
            <!-- ปุ่มและคำแนะนำการอัปโหลดไฟล์ -->
            <div class="flex flex-col">
              <div class="flex flex-wrap items-center gap-2">
                <label for="file-upload" class="flex items-center gap-2 cursor-pointer px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                  <UploadIcon class="h-4 w-4" />
                  <span>เลือกไฟล์แนบ</span>
                </label>
                
                <input 
                  id="file-upload" 
                  type="file" 
                  multiple
                  class="hidden" 
                  @change="handleFileUpload"
                  accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                />
                
                <p class="text-xs text-gray-500">รองรับไฟล์ประเภท PDF, Word, Excel, รูปภาพ (ขนาดไม่เกิน 10MB)</p>
              </div>
              
              <p v-if="form.errors.attachments" class="text-sm text-red-500 mt-1">
                {{ form.errors.attachments }}
              </p>
            </div>
          </div>
        </div>

        <!-- ส่วนของปุ่มดำเนินการ -->
        <div class="flex flex-col-reverse sm:flex-row justify-end gap-3">
          <Button
            type="button"
            variant="outline"
            @click="navigateBack"
            class="w-full sm:w-auto flex items-center justify-center gap-2"
          >
            <XIcon class="h-4 w-4" />
            <span>ยกเลิก</span>
          </Button>
          
          <Button
            type="submit"
            :disabled="form.processing || !selectedDivisionRisk"
            class="w-full sm:w-auto flex items-center justify-center gap-2"
          >
            <Loader2Icon v-if="form.processing" class="h-4 w-4 animate-spin" />
            <SaveIcon v-else class="h-4 w-4" />
            <span>
              {{ form.processing 
                ? (isEditMode ? 'กำลังบันทึกการแก้ไข...' : 'กำลังสร้าง...') 
                : (isEditMode ? 'บันทึกการแก้ไข' : 'สร้างการควบคุมใหม่') 
              }}
            </span>
          </Button>
        </div>
      </form>

      <!-- แสดง Loading indicator ขณะประมวลผล -->
      <div 
        v-if="form.processing" 
        class="fixed inset-0 bg-black/30 flex items-center justify-center z-50"
      >
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg flex flex-col items-center gap-3">
          <Loader2Icon class="h-10 w-10 animate-spin text-primary" />
          <p class="text-base font-medium">
            {{ isEditMode ? 'กำลังบันทึกการแก้ไข กรุณารอสักครู่...' : 'กำลังสร้างการควบคุมใหม่ กรุณารอสักครู่...' }}
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
