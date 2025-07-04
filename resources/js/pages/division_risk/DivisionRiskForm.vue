<!-- 
  ไฟล์: resources/js/pages/division_risk/DivisionRiskForm.vue
  คำอธิบาย: หน้าฟอร์มสำหรับสร้างและแก้ไขข้อมูลความเสี่ยงระดับฝ่าย
  รองรับทั้ง create และ edit mode ตามข้อมูลที่ส่งมา
  ทำหน้าที่: แสดงฟอร์มสำหรับจัดการข้อมูลความเสี่ยง, อัปโหลดเอกสารแนบ
  หลักการ: ใช้ฟอร์มแบบเต็มหน้าจอ สำหรับการจัดการข้อมูลโดยเฉพาะ
  ใช้ร่วมกับ: DivisionRiskController.php ในฝั่ง Backend
-->

<script setup lang="ts">
// ==================== นำเข้า Layout และ Navigation ====================
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

// ==================== นำเข้า Types และ Data ====================
import { type BreadcrumbItem } from '@/types';
import type { DivisionRisk, OrganizationalRisk, CriteriaItem } from '@/types/types'

// นำเข้าไลบรารีและคอมโพเนนต์ที่จำเป็น
import { computed, watch, ref, onMounted, nextTick } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { toast } from 'vue-sonner'

// เพิ่ม import OrganizationalRiskCombobox
import OrganizationalRiskCombobox from '@/components/forms/OrganizationalRiskCombobox.vue'

import { 
  SaveIcon, XIcon, UploadIcon, XCircleIcon, InfoIcon, 
  Trash2Icon, HelpCircleIcon, Loader2Icon, ArrowLeftIcon,
  PlusIcon, EditIcon
} from 'lucide-vue-next'

// ==================== นำเข้า Composables ====================
import { useDivisionRiskData } from '@/composables/useDivisionRiskData'

interface FormCriteriaItem {
  level: number;
  name: string;
  description: string | null;
  // id, division_risk_id, created_at, updated_at เป็น optional
  // จะถูกกำหนดโดย backend
  id?: number;
  division_risk_id?: number;
  created_at?: string;
  updated_at?: string;
}

// กำหนด Types สำหรับฟอร์ม
type RiskFormData = {
  risk_name: string;
  description: string;
  organizational_risk_id?: number | null;
  attachments: File[] | null;
  // เปลี่ยนจาก CriteriaItem[] เป็น Record<string, any>[]
  // เพื่อให้สอดคล้องกับ FormDataConvertible
  likelihood_criteria: Record<string, any>[];
  impact_criteria: Record<string, any>[];
}

// กำหนด props และ events - ปรับให้ risk เป็น optional
const props = defineProps<{
  risk?: DivisionRisk; // ข้อมูลความเสี่ยงสำหรับการแก้ไข (optional สำหรับ create mode)
  initialRisks?: DivisionRisk[]; // ข้อมูลความเสี่ยงทั้งหมด
  organizationalRisks?: OrganizationalRisk[]; // ข้อมูลความเสี่ยงระดับองค์กรทั้งหมด
}>()

// ตรวจสอบ mode การทำงาน
const isEditMode = computed(() => !!props.risk?.id)
const isCreateMode = computed(() => !props.risk?.id)

// กำหนด Breadcrumbs แบบ dynamic ตาม mode
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  {
    title: 'จัดการความเสี่ยงฝ่าย',
    href: route('division-risks.index'),
  },
  {
    title: isEditMode.value ? 'แก้ไขความเสี่ยง' : 'เพิ่มความเสี่ยงใหม่',
    href: '#',
  }
])

// หัวข้อหน้าแบบ dynamic
const pageTitle = computed(() => 
  isEditMode.value ? 'แก้ไขความเสี่ยงระดับฝ่าย' : 'เพิ่มความเสี่ยงระดับฝ่ายใหม่'
)

const pageDescription = computed(() => 
  isEditMode.value 
    ? 'แก้ไขข้อมูลความเสี่ยงและเกณฑ์การประเมิน' 
    : 'สร้างข้อมูลความเสี่ยงใหม่และกำหนดเกณฑ์การประเมิน'
)

// เพิ่มตัวแปรเก็บสถานะการแสดงส่วนเกณฑ์การประเมิน
const showCriteriaSection = ref<boolean>(true);

// ฟังก์ชันสลับการแสดง/ซ่อนส่วนเกณฑ์การประเมิน
const toggleCriteriaSection = () => {
  showCriteriaSection.value = !showCriteriaSection.value;
}

// เพิ่มตัวแปรเก็บสถานะการโหลดข้อมูล
const isLoading = ref<boolean>(true);

// ==================== OrganizationalRisk State ====================
// เปลี่ยนจาก combobox state หลายตัวเป็นตัวแปรเดียว
const selectedOrganizationalRisk = ref<OrganizationalRisk | null>(null)

// ใช้ composable เพื่อแยกการจัดการข้อมูลออกจาก UI
const { 
  existingAttachments, selectedFiles, fileNames,
  loadAttachments, submitForm, addSelectedFiles, removeSelectedFile, 
  markAttachmentForDeletion, openAttachment, validateFiles,
  getFileIcon, formatFileSize 
} = useDivisionRiskData(props.initialRisks, isEditMode.value)

// สถานะสำหรับแสดง tooltip ช่วยเหลือ
const showHelp = ref<boolean>(false)

// สร้างฟอร์มด้วย Inertia useForm
const form = useForm<RiskFormData>({
  risk_name: '',
  description: '',
  organizational_risk_id: null,
  attachments: null,
  
  // เพิ่มค่าเริ่มต้นสำหรับเกณฑ์โอกาสเกิด 4 ระดับ
  likelihood_criteria: [
    { level: 1, name: 'น้อยมาก', description: 'โอกาสเกิดน้อยกว่า 25%' },
    { level: 2, name: 'น้อย', description: 'โอกาสเกิด 25-50%' },
    { level: 3, name: 'ปานกลาง', description: 'โอกาสเกิด 51-75%' },
    { level: 4, name: 'สูง', description: 'โอกาสเกิดมากกว่า 75%' }
  ] as Record<string, any>[],
  
  // เพิ่มค่าเริ่มต้นสำหรับเกณฑ์ผลกระทบ 4 ระดับ
  impact_criteria: [
    { level: 1, name: 'น้อยมาก', description: 'ผลกระทบต่อฝ่ายเล็กน้อย' },
    { level: 2, name: 'น้อย', description: 'ผลกระทบต่อฝ่ายพอสมควร' },
    { level: 3, name: 'ปานกลาง', description: 'ผลกระทบต่อฝ่ายค่อนข้างมาก' },
    { level: 4, name: 'สูง', description: 'ผลกระทบต่อฝ่ายอย่างรุนแรง' }
  ] as Record<string, any>[],
})

// ===============================
// Event Handlers สำหรับ OrganizationalRiskCombobox
// ===============================

/**
 * จัดการการเลือกความเสี่ยงองค์กร
 */
const handleOrganizationalRiskSelect = (risk: OrganizationalRisk) => {
  console.log('เลือกความเสี่ยงระดับองค์กร:', risk)
  selectedOrganizationalRisk.value = risk
  form.organizational_risk_id = risk.id
  
  toast.success(`เลือกความเสี่ยงองค์กร: ${risk.risk_name}`, {
    duration: 2000
  })
}

/**
 * จัดการการล้างค่าความเสี่ยงองค์กร
 */
const handleOrganizationalRiskClear = () => {
  console.log('ล้างความเสี่ยงระดับองค์กร')
  selectedOrganizationalRisk.value = null
  form.organizational_risk_id = null
}

/**
 * หาความเสี่ยงองค์กรจาก ID
 */
const findOrganizationalRiskById = (id: number | null): OrganizationalRisk | null => {
  if (!id || !props.organizationalRisks) return null
  return props.organizationalRisks.find(risk => risk.id === id) || null
}

// โหลดข้อมูลเมื่อคอมโพเนนต์ถูกโหลด
onMounted(() => {
  if (isEditMode.value && props.risk) {
    // โหลดข้อมูลสำหรับการแก้ไข
    console.log('กำลังโหลดข้อมูลสำหรับแก้ไข:', props.risk.risk_name)
    form.risk_name = props.risk.risk_name
    form.description = props.risk.description
    form.organizational_risk_id = props.risk.organizational_risk_id
    
    // ตั้งค่า selectedOrganizationalRisk จาก ID
    selectedOrganizationalRisk.value = findOrganizationalRiskById(props.risk.organizational_risk_id)
    
    loadAttachments(props.risk)
    
    // โหลดข้อมูลเกณฑ์การประเมิน (ถ้ามี)
    if (props.risk.likelihood_criteria && props.risk.likelihood_criteria.length > 0) {
      // ใช้ type assertion เพื่อช่วยให้ TypeScript เข้าใจ type
      form.likelihood_criteria = [...props.risk.likelihood_criteria] as Record<string, any>[]
    }
    if (props.risk.impact_criteria && props.risk.impact_criteria.length > 0) {
      form.impact_criteria = [...props.risk.impact_criteria] as Record<string, any>[]
    }
  } else {
    // Create mode - ใช้ค่าเริ่มต้นที่กำหนดไว้แล้วในฟอร์ม
    console.log('เริ่มต้นสำหรับการสร้างใหม่')
  }
  
  isLoading.value = false
})

/**
 * นำทางกลับไปยังหน้ารายการความเสี่ยง
 */
const navigateBack = () => {
  router.visit(route('division-risks.index'))
}

/**
 * จัดการไฟล์ที่อัปโหลด
 */
const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement
  addSelectedFiles(input.files)
  
  if (input.files && input.files.length > 0) {
    form.attachments = Array.from(input.files)
  }
  input.value = '' // รีเซ็ต input หลังการเลือกไฟล์
}

/**
 * ตรวจสอบความถูกต้องของฟอร์ม
 */
const validateForm = () => {
  let isValid = true
  const errors: string[] = []
  
  // ตรวจสอบข้อมูลสำคัญ
  if (!form.risk_name.trim()) {
    errors.push('กรุณาระบุชื่อความเสี่ยง')
    isValid = false
  }
  
  if (!form.description.trim()) {
    errors.push('กรุณาระบุรายละเอียดความเสี่ยง')
    isValid = false
  }
  
  // ตรวจสอบไฟล์แนบ
  if (selectedFiles.value.length > 0) {
    const fileValidation = validateFiles(selectedFiles.value)
    if (!fileValidation.valid) {
      isValid = false
      errors.push(...fileValidation.errors)
    }
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
 * ส่งข้อมูลไปยัง backend
 */
const handleSubmit = async () => {
  if (!validateForm()) return
  
  try {
    // แสดง toast ทันทีที่กดบันทึก
    const loadingMessage = isEditMode.value ? 'กำลังบันทึกการแก้ไข' : 'กำลังสร้างความเสี่ยงใหม่'
    toast.loading(loadingMessage, {
      id: 'saving-risk',
      duration: 60000
    })
    
    console.log(`กำลังส่งข้อมูล, mode: ${isEditMode.value ? 'แก้ไข' : 'สร้างใหม่'}${isEditMode.value ? ', id: ' + props.risk?.id : ''}`)
    console.log('organizational_risk_id:', selectedOrganizationalRisk.value?.id || null)
    
    // แปลงข้อมูลเกณฑ์การประเมินเป็น plain objects ผ่าน JSON
    const formData = {
      risk_name: form.risk_name,
      description: form.description,
      organizational_risk_id: selectedOrganizationalRisk.value?.id || null,
      likelihood_criteria: JSON.parse(JSON.stringify(form.likelihood_criteria)),
      impact_criteria: JSON.parse(JSON.stringify(form.impact_criteria))
    }
    
    await submitForm(
      formData,
      isEditMode.value ? props.risk?.id : undefined, // ส่ง id เฉพาะใน edit mode
      () => {
        // หลังจากบันทึกสำเร็จให้นำทางกลับไปยังหน้ารายการ
        setTimeout(() => {
          router.visit(route('division-risks.index'))
        }, 1000)
      }
    )
    
    const successMessage = isEditMode.value ? 'บันทึกการแก้ไขเรียบร้อย' : 'เพิ่มความเสี่ยงใหม่เรียบร้อย'
    toast.success(successMessage, {
      id: 'saving-risk'
    })
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล:', error)
    
    const errorMessage = isEditMode.value ? 'ไม่สามารถบันทึกการแก้ไขได้' : 'ไม่สามารถสร้างความเสี่ยงใหม่ได้'
    toast.error(errorMessage, {
      id: 'saving-risk',
      description: 'กรุณาลองใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ'
    })
  }
}

/**
 * สลับการแสดงข้อความช่วยเหลือ
 */
const toggleHelp = () => {
  showHelp.value = !showHelp.value
}

// กำหนดชื่อหน้าแบบ dynamic
const headTitle = computed(() => 
  isEditMode.value ? 'แก้ไขความเสี่ยงระดับฝ่าย' : 'เพิ่มความเสี่ยงระดับฝ่ายใหม่'
)
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
      <form @submit.prevent="handleSubmit" class="space-y-6" v-else>
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
          <!-- ส่วนข้อมูลหลัก -->
          <div class="p-6 space-y-6">
            <h2 class="text-lg font-medium border-b pb-2">ข้อมูลความเสี่ยง</h2>
            
            <div class="grid grid-cols-1 gap-6">
              <!-- ฟิลด์ชื่อความเสี่ยง พร้อมปุ่มช่วยเหลือ -->
              <div class="grid gap-2">
                <Label for="risk_name" class="flex items-center gap-1">
                  ชื่อความเสี่ยง <span class="text-red-500">*</span>
                  <Button 
                    type="button" 
                    variant="ghost" 
                    size="icon"
                    class="h-5 w-5 text-gray-500 hover:text-gray-700"
                    @click="toggleHelp"
                  >
                    <HelpCircleIcon class="h-4 w-4" />
                  </Button>
                </Label>
                
                <!-- ข้อความช่วยเหลือ - แสดงเมื่อคลิกปุ่มช่วยเหลือ -->
                <div v-if="showHelp" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
                  ชื่อความเสี่ยงควรระบุให้ชัดเจนและกระชับ แสดงถึงผลกระทบที่อาจเกิดขึ้นกับฝ่าย
                  <br />ตัวอย่าง: "ความล่าช้าในการส่งมอบงาน", "การเข้าถึงข้อมูลสำคัญโดยไม่ได้รับอนุญาต"
                </div>
                
                <Input 
                  id="risk_name" 
                  v-model="form.risk_name" 
                  placeholder="ระบุชื่อความเสี่ยงระดับฝ่าย"
                  required
                />
                <p v-if="form.errors.risk_name" class="text-sm text-red-500">
                  {{ form.errors.risk_name }}
                </p>
              </div>

              <!-- ฟิลด์เลือกความเสี่ยงองค์กร -->
              <div class="grid gap-2">
                <Label class="flex items-center gap-1">
                  ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง
                  <Button 
                    type="button" 
                    variant="ghost" 
                    size="icon"
                    class="h-5 w-5 text-gray-500 hover:text-gray-700"
                    @click="toggleHelp"
                  >
                    <HelpCircleIcon class="h-4 w-4" />
                  </Button>
                </Label>
                
                <!-- คำอธิบายสำหรับความเสี่ยงองค์กร -->
                <div v-if="showHelp" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
                  เลือกความเสี่ยงระดับองค์กรที่เกี่ยวข้องกับความเสี่ยงระดับฝ่ายนี้ (ถ้ามี)
                  <br />การเชื่อมโยงนี้จะช่วยในการติดตามและจัดการความเสี่ยงแบบบูรณาการ
                </div>
                
                <!-- ใช้ OrganizationalRiskCombobox -->
                <OrganizationalRiskCombobox
                  :organizational-risks="props.organizationalRisks || []"
                  v-model="selectedOrganizationalRisk"
                  placeholder="ค้นหาหรือเลือกความเสี่ยงระดับองค์กร..."
                  :disabled="form.processing"
                  @select="handleOrganizationalRiskSelect"
                  @clear="handleOrganizationalRiskClear"
                >
                  <template #error>
                    <p v-if="form.errors.organizational_risk_id" class="text-sm text-red-500 mt-1">
                      {{ form.errors.organizational_risk_id }}
                    </p>
                  </template>
                  
                  <template #help>
                    <p v-if="selectedOrganizationalRisk" class="text-xs text-muted-foreground mt-1">
                      <strong>รายละเอียด:</strong> {{ selectedOrganizationalRisk.description }}
                    </p>
                  </template>
                </OrganizationalRiskCombobox>
              </div>

              <!-- ฟิลด์รายละเอียด -->
              <div class="grid gap-2">
                <Label for="description">
                  รายละเอียดความเสี่ยง <span class="text-red-500">*</span>
                </Label>
                <Textarea 
                  id="description" 
                  v-model="form.description" 
                  placeholder="ระบุรายละเอียดความเสี่ยง เช่น สาเหตุ ผลกระทบที่อาจเกิดขึ้น" 
                  :rows="6"
                  required
                />
                <p v-if="form.errors.description" class="text-sm text-red-500">
                  {{ form.errors.description }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- ส่วนของเกณฑ์การประเมินความเสี่ยง -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
          <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-medium">เกณฑ์การประเมินความเสี่ยง</h2>
              
              <Button 
                type="button"
                variant="outline"
                size="sm"
                @click="toggleCriteriaSection"
                class="text-xs"
              >
                {{ showCriteriaSection ? 'ซ่อนรายละเอียด' : 'แสดงรายละเอียด' }}
              </Button>
            </div>
            
            <!-- รายละเอียดเกณฑ์การประเมิน -->
            <div v-if="showCriteriaSection" class="space-y-6 mt-4 animate-in fade-in-50 duration-300">
              <!-- 1. เกณฑ์โอกาสเกิด (Likelihood) -->
              <div class="border rounded-md p-4 bg-muted/30">
                <h3 class="font-medium mb-4">เกณฑ์โอกาสเกิด (Likelihood)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div v-for="(criteria, index) in form.likelihood_criteria" :key="`likelihood-${index}`" class="grid gap-2">
                    <div class="flex items-center gap-2">
                      <div class="bg-primary/10 text-primary font-medium rounded-full w-6 h-6 flex items-center justify-center text-xs">
                        {{ criteria.level }}
                      </div>
                      <Input
                        v-model="criteria.name" 
                        :placeholder="`ชื่อระดับ ${criteria.level}`"
                        class="max-w-full"
                      />
                    </div>
                    <Textarea
                      v-model="criteria.description"
                      :placeholder="`คำอธิบายระดับ ${criteria.level}`"
                      rows="2"
                      class="text-sm"
                    />
                  </div>
                </div>
              </div>
              
              <!-- 2. เกณฑ์ผลกระทบ (Impact) -->
              <div class="border rounded-md p-4 bg-muted/30">
                <h3 class="font-medium mb-4">เกณฑ์ผลกระทบ (Impact)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div v-for="(criteria, index) in form.impact_criteria" :key="`impact-${index}`" class="grid gap-2">
                    <div class="flex items-center gap-2">
                      <div class="bg-primary/10 text-primary font-medium rounded-full w-6 h-6 flex items-center justify-center text-xs">
                        {{ criteria.level }}
                      </div>
                      <Input
                        v-model="criteria.name" 
                        :placeholder="`ชื่อระดับ ${criteria.level}`"
                        class="max-w-full"
                      />
                    </div>
                    <Textarea
                      v-model="criteria.description"
                      :placeholder="`คำอธิบายระดับ ${criteria.level}`"
                      rows="2"
                      class="text-sm"
                    />
                  </div>
                </div>
              </div>
            </div>
            
            <!-- คำแนะนำสำหรับเกณฑ์การประเมิน -->
            <div v-if="!showCriteriaSection" class="text-sm text-muted-foreground p-4 bg-muted/20 rounded-md">
              <div class="flex items-center">
                <InfoIcon class="h-5 w-5 mr-2 text-blue-500" />
                <span>คลิก "แสดงรายละเอียด" เพื่อดูและแก้ไขเกณฑ์การประเมินความเสี่ยงทั้ง 4 ระดับ</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ส่วนของเอกสารแนบ -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
          <div class="p-6 space-y-4">
            <h2 class="text-lg font-medium">เอกสารแนบ</h2>
            
            <!-- แสดงเอกสารแนบที่มีอยู่แล้ว (กรณีแก้ไข) -->
            <div v-if="isEditMode && existingAttachments.length > 0" class="mb-4">
              <p class="text-sm font-medium text-gray-700 mb-2">เอกสารแนบปัจจุบัน:</p>
              <ul class="space-y-2">
                <li 
                  v-for="attachment in existingAttachments" 
                  :key="attachment.id" 
                  class="flex flex-wrap items-center justify-between p-3 bg-gray-50 rounded-md text-sm border border-gray-200"
                >
                  <!-- ส่วนแสดงข้อมูลไฟล์ - สามารถคลิกเพื่อเปิดดู -->
                  <div 
                    class="flex items-center gap-2 flex-1 min-w-0 overflow-hidden" 
                    @click="openAttachment(attachment.url)" 
                    style="cursor: pointer"
                  >
                    <component :is="getFileIcon(attachment.file_name)" class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate max-w-[200px] sm:max-w-[300px]">{{ attachment.file_name }}</span>
                    <span class="text-xs text-gray-500 flex-shrink-0">{{ formatFileSize(attachment.file_size || 0) }}</span>
                  </div>
                  
                  <Button 
                    type="button" 
                    variant="ghost" 
                    size="sm" 
                    @click="markAttachmentForDeletion(attachment.id)"
                    class="text-red-500 hover:text-red-700 hover:bg-red-50 ml-1 flex-shrink-0"
                    aria-label="ลบเอกสาร"
                  >
                    <Trash2Icon class="h-4 w-4" />
                  </Button>
                </li>
              </ul>
            </div>
            
            <!-- แสดงไฟล์ที่เพิ่งอัปโหลด (ยังไม่ได้บันทึก) -->
            <div v-if="fileNames.length > 0" class="mb-4">
              <p class="text-sm font-medium text-gray-700 mb-2">ไฟล์ที่เลือกไว้:</p>
              <ul class="space-y-2">
                <li 
                  v-for="(fileName, index) in fileNames" 
                  :key="index"
                  class="flex items-center justify-between p-3 bg-gray-50 rounded-md text-sm border border-gray-200"
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
                    class="text-red-500 hover:text-red-700 hover:bg-red-50 ml-1 flex-shrink-0"
                  >
                    <XCircleIcon class="h-4 w-4" />
                  </Button>
                </li>
              </ul>
            </div>

            <!-- ปุ่มและคำแนะนำการอัปโหลดไฟล์ -->
            <div class="flex flex-col">
              <div class="flex flex-wrap items-center gap-2">
                <label for="file-upload" class="flex items-center gap-2 cursor-pointer px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
            :disabled="form.processing"
            class="w-full sm:w-auto flex items-center justify-center gap-2"
          >
            <Loader2Icon v-if="form.processing" class="h-4 w-4 animate-spin" />
            <SaveIcon v-else class="h-4 w-4" />
            <span>
              {{ form.processing 
                ? (isEditMode ? 'กำลังบันทึกการแก้ไข...' : 'กำลังสร้าง...') 
                : (isEditMode ? 'บันทึกการแก้ไข' : 'สร้างความเสี่ยงใหม่') 
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
        <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col items-center gap-3">
          <Loader2Icon class="h-10 w-10 animate-spin text-primary" />
          <p class="text-base font-medium">
            {{ isEditMode ? 'กำลังบันทึกการแก้ไข กรุณารอสักครู่...' : 'กำลังสร้างความเสี่ยงใหม่ กรุณารอสักครู่...' }}
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
