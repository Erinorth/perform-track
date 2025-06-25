<!-- 
  ไฟล์: resources/js/pages/risk_assessment/RiskAssessmentForm.vue
  คำอธิบาย: หน้าฟอร์มสำหรับสร้างและแก้ไขข้อมูลการประเมินความเสี่ยง
  รองรับทั้ง create และ edit mode ตามข้อมูลที่ส่งมา
  ทำหน้าที่: แสดงฟอร์มสำหรับจัดการข้อมูลการประเมินความเสี่ยง, อัปโหลดเอกสารแนบ
  หลักการ: ใช้ฟอร์มแบบเต็มหน้าจอ สำหรับการจัดการข้อมูลโดยเฉพาะ
  ใช้ร่วมกับ: RiskAssessmentController.php ในฝั่ง Backend
-->

<script setup lang="ts">
// ==================== นำเข้า Layout และ Navigation ====================
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

// ==================== นำเข้า Types และ Interfaces ====================
import type { BreadcrumbItem } from '@/types'
import type { DivisionRisk, OrganizationalRisk } from '@/types/types'

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
import RiskMatrix from '@/components/custom/RiskMatrix.vue'

// เพิ่ม import DivisionRiskCombobox
import DivisionRiskCombobox from '@/components/forms/DivisionRiskCombobox.vue'

// ==================== นำเข้า Utilities ====================
import { toast } from 'vue-sonner'

// ==================== นำเข้า Icons ====================
import { 
  ArrowLeft as ArrowLeftIcon, 
  Info as InfoIcon,
  BarChart4 as BarChart4Icon,
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
} from 'lucide-vue-next'

// ==================== แก้ไข Types Interface ====================
interface RiskAssessmentAttachment {
  id: number
  filename: string
  filepath: string
  filetype: string | null
  filesize: number | null
}

interface CriteriaItem {
  id: number
  level: number
  name: string
  description: string | null
}

// แก้ไข Props interface ให้รองรับทั้ง create และ edit mode
interface Props {
  // ข้อมูลการประเมินความเสี่ยง (optional สำหรับ create mode)
  riskAssessment?: {
    id: number
    assessment_year: number
    assessment_period: number
    likelihood_level: number
    impact_level: number
    division_risk_id: number
    notes: string | null
    division_risk: {
      id: number
      risk_name: string
      description: string
      organizational_risk?: {
        id: number
        risk_name: string
      }
    }
    attachments?: RiskAssessmentAttachment[]
  }
  
  // ใช้ DivisionRisk type จาก types.ts แทน custom interface
  divisionRisks: DivisionRisk[]
  
  // เก็บ criteria แยกไว้เพื่อ backward compatibility
  likelihoodCriteria: Record<number, CriteriaItem[]>
  impactCriteria: Record<number, CriteriaItem[]>
}

const props = defineProps<Props>()

// ตรวจสอบ mode การทำงาน
const isEditMode = computed(() => !!props.riskAssessment?.id)
const isCreateMode = computed(() => !props.riskAssessment?.id)

// ==================== กำหนด Breadcrumbs แบบ dynamic ====================
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  {
    title: 'การประเมินความเสี่ยง',
    href: route('risk-assessments.index'),
  },
  {
    title: isEditMode.value ? 'แก้ไขการประเมินความเสี่ยง' : 'เพิ่มการประเมินความเสี่ยงใหม่',
    href: '#',
  }
])

// หัวข้อหน้าแบบ dynamic
const pageTitle = computed(() => 
  isEditMode.value ? 'แก้ไขการประเมินความเสี่ยง' : 'เพิ่มการประเมินความเสี่ยงใหม่'
)

const pageDescription = computed(() => 
  isEditMode.value 
    ? 'แก้ไขข้อมูลการประเมินความเสี่ยงและเอกสารแนบ' 
    : 'สร้างข้อมูลการประเมินความเสี่ยงใหม่และเพิ่มเอกสารแนบ'
)

// ==================== Reactive States ====================
const isLoading = ref<boolean>(true)
const showMatrix = ref<boolean>(false)
const showHelpLikelihood = ref<boolean>(false)
const showHelpImpact = ref<boolean>(false)

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
  assessment_year: props.riskAssessment?.assessment_year || new Date().getFullYear(),
  assessment_period: props.riskAssessment?.assessment_period?.toString() || '',
  division_risk_id: props.riskAssessment?.division_risk_id?.toString() || '',
  likelihood_level: props.riskAssessment?.likelihood_level?.toString() || '',
  impact_level: props.riskAssessment?.impact_level?.toString() || '',
  notes: props.riskAssessment?.notes || '',
  attachments: null as File[] | null,
  delete_attachments: [] as number[]
})

// ==================== Computed Properties ====================
// เกณฑ์การประเมินโอกาสเกิดสำหรับความเสี่ยงที่เลือก
const selectedLikelihoodCriteria = computed(() => {
  if (!selectedDivisionRisk.value) return []
  
  // ลองใช้จาก division risk ก่อน
  if (selectedDivisionRisk.value.likelihood_criteria && selectedDivisionRisk.value.likelihood_criteria.length > 0) {
    return selectedDivisionRisk.value.likelihood_criteria
  }
  
  // ถ้าไม่มีให้ใช้จาก props
  return props.likelihoodCriteria[selectedDivisionRisk.value.id] || []
})

// เกณฑ์การประเมินผลกระทบสำหรับความเสี่ยงที่เลือก
const selectedImpactCriteria = computed(() => {
  if (!selectedDivisionRisk.value) return []
  
  // ลองใช้จาก division risk ก่อน
  if (selectedDivisionRisk.value.impact_criteria && selectedDivisionRisk.value.impact_criteria.length > 0) {
    return selectedDivisionRisk.value.impact_criteria
  }
  
  // ถ้าไม่มีให้ใช้จาก props
  return props.impactCriteria[selectedDivisionRisk.value.id] || []
})

// คำนวณคะแนนความเสี่ยงและระดับความเสี่ยง
const riskScore = computed(() => {
  if (!form.likelihood_level || !form.impact_level) return 0
  return parseInt(form.likelihood_level) * parseInt(form.impact_level)
})

const riskLevel = computed(() => {
  const score = riskScore.value
  if (score <= 3) {
    return { text: 'ความเสี่ยงต่ำ', color: 'text-green-700 dark:text-green-500' }
  } else if (score <= 8) {
    return { text: 'ความเสี่ยงปานกลาง', color: 'text-yellow-700 dark:text-yellow-500' }
  } else if (score <= 12) {
    return { text: 'ความเสี่ยงสูง', color: 'text-orange-700 dark:text-orange-500' }
  } else {
    return { text: 'ความเสี่ยงสูงมาก', color: 'text-red-700 dark:text-red-500' }
  }
})

// กำหนดชื่อหน้าแบบ dynamic
const headTitle = computed(() => 
  isEditMode.value ? 'แก้ไขการประเมินความเสี่ยง' : 'เพิ่มการประเมินความเสี่ยงใหม่'
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
  
  // รีเซ็ตค่าการประเมินเมื่อเลือกความเสี่ยงอื่น
  form.likelihood_level = ''
  form.impact_level = ''
  
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
  
  // รีเซ็ตค่าการประเมิน
  form.likelihood_level = ''
  form.impact_level = ''
}

// ==================== Methods ====================
/**
 * นำทางกลับไปยังหน้ารายการประเมินความเสี่ยง
 */
const navigateBack = () => {
  router.visit(route('risk-assessments.index'))
}

/**
 * เมื่อเลือกระดับโอกาสเกิด - แก้ไขให้รองรับ AcceptableValue
 */
const onLikelihoodChange = (value: unknown) => {
  const stringValue = value === null || value === undefined ? '' : String(value)
  form.likelihood_level = stringValue
  console.log('เลือกระดับโอกาสเกิด:', stringValue)
}

/**
 * เมื่อเลือกระดับผลกระทบ - แก้ไขให้รองรับ AcceptableValue
 */
const onImpactChange = (value: unknown) => {
  const stringValue = value === null || value === undefined ? '' : String(value)
  form.impact_level = stringValue
  console.log('เลือกระดับผลกระทบ:', stringValue)
}

/**
 * เมื่อเลือกงวดการประเมิน - แก้ไขให้รองรับ AcceptableValue
 */
const onAssessmentPeriodChange = (value: unknown) => {
  const stringValue = value === null || value === undefined ? '' : String(value)
  form.assessment_period = stringValue
  console.log('เลือกงวดการประเมิน:', stringValue)
}

/**
 * สลับการแสดงแผนภูมิความเสี่ยง
 */
const toggleRiskMatrix = () => {
  showMatrix.value = !showMatrix.value
  console.log('สลับการแสดงแผนภูมิความเสี่ยง:', showMatrix.value ? 'แสดง' : 'ซ่อน')
}

/**
 * โหลดข้อมูลเอกสารแนบ
 */
const loadAttachments = () => {
  if (props.riskAssessment?.attachments && props.riskAssessment.attachments.length > 0) {
    existingAttachments.value = props.riskAssessment.attachments.map(attachment => ({
      id: attachment.id,
      filename: attachment.filename,
      filepath: attachment.filepath,
      filesize: attachment.filesize,
      filetype: attachment.filetype,
      url: route('risk-assessments.attachments.download', attachment.id),
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
  if (!form.assessment_year) {
    errors.push('กรุณาระบุปีที่ประเมิน')
    isValid = false
  }
  
  if (!form.assessment_period) {
    errors.push('กรุณาเลือกงวดการประเมิน')
    isValid = false
  }
  
  if (!form.division_risk_id) {
    errors.push('กรุณาเลือกความเสี่ยงระดับฝ่าย')
    isValid = false
  }
  
  if (!form.likelihood_level) {
    errors.push('กรุณาเลือกระดับโอกาสเกิด')
    isValid = false
  }
  
  if (!form.impact_level) {
    errors.push('กรุณาเลือกระดับผลกระทบ')
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
 * ส่งข้อมูลการประเมิน
 */
const submitForm = () => {
  // ตรวจสอบความถูกต้องของฟอร์ม
  if (!validateForm()) return
  
  // แสดงข้อความกำลังบันทึก
  const loadingMessage = isEditMode.value ? 'กำลังบันทึกการแก้ไข' : 'กำลังสร้างการประเมินใหม่'
  toast.loading(loadingMessage, {
    id: 'saving-assessment',
    duration: 60000
  })
  
  console.log(`กำลังส่งข้อมูล, mode: ${isEditMode.value ? 'แก้ไข' : 'สร้างใหม่'}${isEditMode.value ? ', id: ' + props.riskAssessment?.id : ''}`)
  
  // กำหนด route และ method ตาม mode
  const submitRoute = isEditMode.value 
    ? route('risk-assessments.update', props.riskAssessment!.id)
    : route('risk-assessments.store')
  
  const submitMethod = isEditMode.value ? 'post' : 'post' // ใช้ post ทั้งคู่เพราะ Laravel จะจัดการ method spoofing
  
  // ส่งข้อมูลไปยัง server
  form[submitMethod](submitRoute, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      const successMessage = isEditMode.value 
        ? 'บันทึกการแก้ไขเรียบร้อยแล้ว' 
        : 'สร้างการประเมินความเสี่ยงเรียบร้อยแล้ว'
      
      toast.success(successMessage, {
        id: 'saving-assessment'
      })
      
      // นำทางกลับไปยังหน้ารายการประเมินความเสี่ยง
      setTimeout(() => {
        router.visit(route('risk-assessments.index'))
      }, 1000)
    },
    onError: (errors) => {
      console.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล:', errors)
      
      const errorMessage = isEditMode.value 
        ? 'เกิดข้อผิดพลาดในการบันทึกการแก้ไข' 
        : 'เกิดข้อผิดพลาดในการสร้างการประเมิน'
      
      toast.error(errorMessage, {
        id: 'saving-assessment',
        description: 'กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง'
      })
    }
  })
}

/**
 * สลับการแสดงข้อความช่วยเหลือสำหรับโอกาสเกิด
 */
const toggleHelpLikelihood = () => {
  showHelpLikelihood.value = !showHelpLikelihood.value
}

/**
 * สลับการแสดงข้อความช่วยเหลือสำหรับผลกระทบ
 */
const toggleHelpImpact = () => {
  showHelpImpact.value = !showHelpImpact.value
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
  
  if (isEditMode.value && props.riskAssessment) {
    // โหลดข้อมูลสำหรับการแก้ไข
    console.log('กำลังโหลดข้อมูลสำหรับแก้ไข:', props.riskAssessment.id)
    
    // โหลดข้อมูลเอกสารแนบ
    loadAttachments()
    
    // ตั้งค่า selectedDivisionRisk จากข้อมูลที่มีอยู่
    selectedDivisionRisk.value = findDivisionRiskById(props.riskAssessment.division_risk_id)
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
        <!-- ส่วนข้อมูลหลักของการประเมิน -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden dark:bg-gray-800">
          <div class="p-6 space-y-6">
            <h2 class="text-lg font-medium border-b pb-2">ข้อมูลการประเมินความเสี่ยง</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- ปีที่ประเมิน -->
              <div>
                <Label for="assessment_year">
                  ปีที่ประเมิน <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="assessment_year"
                  v-model="form.assessment_year"
                  type="number"
                  min="2000"
                  max="2100"
                  :class="{ 'border-red-500': form.errors.assessment_year }"
                />
                <p v-if="form.errors.assessment_year" class="text-sm text-red-500 mt-1">
                  {{ form.errors.assessment_year }}
                </p>
              </div>

              <!-- งวดการประเมิน -->
              <div>
                <Label for="assessment_period">
                  งวดการประเมิน <span class="text-red-500">*</span>
                </Label>
                <Select 
                  v-model="form.assessment_period"
                  @update:model-value="onAssessmentPeriodChange"
                >
                  <SelectTrigger class="w-full mt-1" :class="{ 'border-red-500': form.errors.assessment_period }">
                    <SelectValue placeholder="เลือกงวดการประเมิน" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="1">ครึ่งปีแรก (ม.ค.-มิ.ย.)</SelectItem>
                    <SelectItem value="2">ครึ่งปีหลัง (ก.ค.-ธ.ค.)</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.assessment_period" class="text-sm text-red-500 mt-1">
                  {{ form.errors.assessment_period }}
                </p>
              </div>
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
                  @click="toggleHelpLikelihood"
                >
                  <HelpCircleIcon class="h-4 w-4" />
                </Button>
              </Label>
              
              <!-- คำอธิบายสำหรับความเสี่ยงฝ่าย -->
              <div v-if="showHelpLikelihood" class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-900 p-2 rounded-md mb-1">
                เลือกความเสี่ยงระดับฝ่ายที่ต้องการประเมิน ซึ่งจะมีเกณฑ์เฉพาะของแต่ละความเสี่ยง<br>
                หากเลือกความเสี่ยงใหม่ ค่าการประเมินจะถูกรีเซ็ตเพื่อให้ใช้เกณฑ์ที่ถูกต้อง
              </div>
              
              <!-- ใช้ DivisionRiskCombobox แทน Select -->
              <DivisionRiskCombobox
                :division-risks="props.divisionRisks || []"
                v-model="selectedDivisionRisk"
                placeholder="เลือกความเสี่ยงฝ่ายที่ต้องการประเมิน..."
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
              <!-- ระดับโอกาสเกิด -->
              <div>
                <div class="flex items-center justify-between">
                  <Label for="likelihood_level">
                    ระดับโอกาสเกิด <span class="text-red-500">*</span>
                  </Label>
                  <Button 
                    type="button" 
                    variant="ghost" 
                    size="icon"
                    class="h-5 w-5 text-gray-500 hover:text-gray-700"
                    @click="toggleHelpLikelihood"
                  >
                    <HelpCircleIcon class="h-4 w-4" />
                  </Button>
                </div>
                
                <!-- ข้อความช่วยเหลือสำหรับโอกาสเกิด -->
                <div v-if="showHelpLikelihood && selectedLikelihoodCriteria.length > 0" class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-900 p-2 rounded-md mb-1">
                  <p class="font-medium mb-1">เกณฑ์การประเมินโอกาสเกิด:</p>
                  <ul class="list-disc pl-4">
                    <li v-for="criteria in selectedLikelihoodCriteria" :key="criteria.id">
                      <span class="font-medium">ระดับ {{ criteria.level }}: {{ criteria.name }}</span>
                      <p v-if="criteria.description" class="text-xs">{{ criteria.description }}</p>
                    </li>
                  </ul>
                </div>
                
                <Select 
                  v-model="form.likelihood_level"
                  @update:model-value="onLikelihoodChange"
                  :disabled="!selectedDivisionRisk"
                >
                  <SelectTrigger 
                    class="w-full mt-1" 
                    :class="{ 'border-red-500': form.errors.likelihood_level }"
                  >
                    <SelectValue placeholder="เลือกระดับโอกาสเกิด" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem 
                      v-for="criteria in selectedLikelihoodCriteria" 
                      :key="criteria.id"
                      :value="criteria.level.toString()"
                    >
                      {{ criteria.level }} - {{ criteria.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.likelihood_level" class="text-sm text-red-500 mt-1">
                  {{ form.errors.likelihood_level }}
                </p>
              </div>

              <!-- ระดับผลกระทบ -->
              <div>
                <div class="flex items-center justify-between">
                  <Label for="impact_level">
                    ระดับผลกระทบ <span class="text-red-500">*</span>
                  </Label>
                  <Button 
                    type="button" 
                    variant="ghost" 
                    size="icon"
                    class="h-5 w-5 text-gray-500 hover:text-gray-700"
                    @click="toggleHelpImpact"
                  >
                    <HelpCircleIcon class="h-4 w-4" />
                  </Button>
                </div>
                
                <!-- ข้อความช่วยเหลือสำหรับผลกระทบ -->
                <div v-if="showHelpImpact && selectedImpactCriteria.length > 0" class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-900 p-2 rounded-md mb-1">
                  <p class="font-medium mb-1">เกณฑ์การประเมินผลกระทบ:</p>
                  <ul class="list-disc pl-4">
                    <li v-for="criteria in selectedImpactCriteria" :key="criteria.id">
                      <span class="font-medium">ระดับ {{ criteria.level }}: {{ criteria.name }}</span>
                      <p v-if="criteria.description" class="text-xs">{{ criteria.description }}</p>
                    </li>
                  </ul>
                </div>
                
                <Select 
                  v-model="form.impact_level"
                  @update:model-value="onImpactChange"
                  :disabled="!selectedDivisionRisk"
                >
                  <SelectTrigger 
                    class="w-full mt-1" 
                    :class="{ 'border-red-500': form.errors.impact_level }"
                  >
                    <SelectValue placeholder="เลือกระดับผลกระทบ" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem 
                      v-for="criteria in selectedImpactCriteria" 
                      :key="criteria.id"
                      :value="criteria.level.toString()"
                    >
                      {{ criteria.level }} - {{ criteria.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.impact_level" class="text-sm text-red-500 mt-1">
                  {{ form.errors.impact_level }}
                </p>
              </div>
            </div>

            <!-- แสดงระดับความเสี่ยง -->
            <div v-if="form.likelihood_level && form.impact_level" class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300">ระดับความเสี่ยง:</p>
                  <h3 class="text-2xl font-bold" :class="riskLevel.color">
                    {{ riskLevel.text }} ({{ riskScore }})
                  </h3>
                </div>
                <Button variant="outline" size="sm" @click="toggleRiskMatrix" type="button">
                  <BarChart4Icon class="mr-1 h-4 w-4" />
                  {{ showMatrix ? 'ซ่อน' : 'แสดง' }}แผนภูมิความเสี่ยง
                </Button>
              </div>
              
              <!-- Risk Matrix -->
              <div v-if="showMatrix" class="mt-4">
                <RiskMatrix 
                  :likelihood="parseInt(form.likelihood_level)" 
                  :impact="parseInt(form.impact_level)" 
                  class="max-w-md mx-auto"
                />
              </div>
            </div>

            <!-- บันทึกเพิ่มเติม -->
            <div>
              <Label for="notes">บันทึกเพิ่มเติม</Label>
              <Textarea
                id="notes"
                v-model="form.notes"
                class="mt-1"
                rows="4"
                placeholder="บันทึกเพิ่มเติมเกี่ยวกับการประเมินความเสี่ยงนี้"
              />
              <p v-if="form.errors.notes" class="text-sm text-red-500 mt-1">
                {{ form.errors.notes }}
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
                : (isEditMode ? 'บันทึกการแก้ไข' : 'สร้างการประเมินใหม่') 
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
            {{ isEditMode ? 'กำลังบันทึกการแก้ไข กรุณารอสักครู่...' : 'กำลังสร้างการประเมินใหม่ กรุณารอสักครู่...' }}
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
