<!-- 
  ไฟล์: resources/js/pages/division_risk/DivisionRiskEdit.vue
  คำอธิบาย: หน้าสำหรับแก้ไขข้อมูลความเสี่ยงระดับฝ่าย
  ปรับปรุงฟิลด์ "ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง" ให้ใช้ Radix Vue Combobox ที่ search ได้
  ทำหน้าที่: แสดงฟอร์มสำหรับแก้ไขข้อมูลความเสี่ยง, อัปโหลดเอกสารแนบ
  หลักการ: ใช้ฟอร์มแบบเต็มหน้าจอ สำหรับการแก้ไขข้อมูลโดยเฉพาะ
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

// Combobox Radix Vue
import {
  ComboboxRoot, ComboboxAnchor, ComboboxInput, ComboboxTrigger, ComboboxContent,
  ComboboxViewport, ComboboxEmpty, ComboboxItem, ComboboxItemIndicator
} from 'radix-vue'

import { 
  SaveIcon, XIcon, UploadIcon, XCircleIcon, InfoIcon, 
  Trash2Icon, HelpCircleIcon, Loader2Icon, ArrowLeftIcon,
  ChevronDownIcon, CheckIcon
} from 'lucide-vue-next'

// ==================== นำเข้า Composables ====================
import { useDivisionRiskData } from '@/composables/useDivisionRiskData'

// ==================== กำหนด Breadcrumbs ====================
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'จัดการความเสี่ยงฝ่าย',
    href: route('division-risks.index'),
  },
  {
    title: 'แก้ไขความเสี่ยง',
    href: '#',
  }
];

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

// Type สำหรับ Combobox - ให้รองรับ OrganizationalRisk และ undefined
type ComboboxValue = OrganizationalRisk | undefined

// กำหนด props และ events
const props = defineProps<{
  risk: DivisionRisk; // ข้อมูลความเสี่ยงสำหรับการแก้ไข
  initialRisks?: DivisionRisk[]; // ข้อมูลความเสี่ยงทั้งหมด
  organizationalRisks?: OrganizationalRisk[]; // ข้อมูลความเสี่ยงระดับองค์กรทั้งหมด
}>()

// เพิ่มตัวแปรเก็บสถานะการแสดงส่วนเกณฑ์การประเมิน
const showCriteriaSection = ref<boolean>(true);

// ฟังก์ชันสลับการแสดง/ซ่อนส่วนเกณฑ์การประเมิน
const toggleCriteriaSection = () => {
  showCriteriaSection.value = !showCriteriaSection.value;
}

// เพิ่มตัวแปรเก็บสถานะการโหลดข้อมูล
const isLoading = ref<boolean>(true);

// ==================== Combobox State ====================
// ฟิลด์ combobox สำหรับความเสี่ยงระดับองค์กร
const organizationalSearch = ref('')
const selectedOrganizationalRisk = ref<ComboboxValue>(undefined)
const isComboboxOpen = ref(false)

// Ref สำหรับคำนวณขนาด dropdown
const comboboxAnchorRef = ref<HTMLElement>()
const dropdownWidth = ref('auto')

// ฟิลด์ combobox: filter option ตาม search - แสดงทั้งหมดเมื่อไม่มี search term
const filteredOrganizationalRisks = computed(() => {
  if (!props.organizationalRisks) return []
  
  // ถ้าไม่มี search term หรือ search term ว่าง ให้แสดงทั้งหมด
  if (!organizationalSearch.value || organizationalSearch.value.trim() === '') {
    return props.organizationalRisks
  }
  
  // กรองตาม search term
  return props.organizationalRisks.filter(risk =>
    risk.risk_name.toLowerCase().includes(organizationalSearch.value.toLowerCase())
  )
})

// ใช้ composable เพื่อแยกการจัดการข้อมูลออกจาก UI
const { 
  existingAttachments, selectedFiles, fileNames,
  loadAttachments, submitForm, addSelectedFiles, removeSelectedFile, 
  markAttachmentForDeletion, openAttachment, validateFiles,
  getFileIcon, formatFileSize 
} = useDivisionRiskData(props.initialRisks, true)

// สถานะสำหรับแสดง tooltip ช่วยเหลือ
const showHelp = ref<boolean>(false)

// สร้างฟอร์มด้วย Inertia useForm
const form = useForm<RiskFormData>({
  risk_name: props.risk?.risk_name ?? '',
  description: props.risk?.description ?? '',
  organizational_risk_id: props.risk?.organizational_risk_id ?? null,
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

// โหลดข้อมูลเมื่อคอมโพเนนต์ถูกโหลด
onMounted(() => {
  if (props.risk) {
    // โหลดข้อมูลสำหรับการแก้ไข
    console.log('กำลังโหลดข้อมูลสำหรับแก้ไข:', props.risk.risk_name)
    form.risk_name = props.risk.risk_name
    form.description = props.risk.description
    form.organizational_risk_id = props.risk.organizational_risk_id
    
    // sync combobox value - ปรับให้ไม่เป็น null
    selectedOrganizationalRisk.value = (props.organizationalRisks || []).find(
      r => r.id === props.risk?.organizational_risk_id
    ) || undefined
    organizationalSearch.value = selectedOrganizationalRisk.value?.risk_name || ''
    
    loadAttachments(props.risk)
    
    // โหลดข้อมูลเกณฑ์การประเมิน (ถ้ามี)
    if (props.risk.likelihood_criteria && props.risk.likelihood_criteria.length > 0) {
      // ใช้ type assertion เพื่อช่วยให้ TypeScript เข้าใจ type
      form.likelihood_criteria = [...props.risk.likelihood_criteria] as Record<string, any>[]
    }
    if (props.risk.impact_criteria && props.risk.impact_criteria.length > 0) {
      form.impact_criteria = [...props.risk.impact_criteria] as Record<string, any>[]
    }
  }
  
  isLoading.value = false
  
  // คำนวณขนาด dropdown เมื่อโหลดข้อมูลเสร็จ
  nextTick(() => {
    updateDropdownWidth()
  })
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
    toast.loading('กำลังบันทึกข้อมูล', {
      id: 'saving-risk',
      duration: 60000
    })
    
    console.log('กำลังส่งข้อมูล, mode: แก้ไข, id:', props.risk?.id)
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
      props.risk?.id,
      () => {
        // หลังจากบันทึกสำเร็จให้นำทางกลับไปยังหน้ารายการ
        setTimeout(() => {
          router.visit(route('division-risks.index'))
        }, 1000)
      }
    )
    
    toast.success('บันทึกข้อมูลเรียบร้อย', {
      id: 'saving-risk'
    })
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล:', error)
    
    toast.error('ไม่สามารถบันทึกข้อมูลได้', {
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

// ===============================
// Combobox: เมื่อเลือก option
// ===============================
function handleSelectOrganizationalRisk(risk: OrganizationalRisk) {
  console.log('เลือกความเสี่ยงระดับองค์กร:', risk)
  selectedOrganizationalRisk.value = risk
  organizationalSearch.value = risk.risk_name
  form.organizational_risk_id = risk.id
}

// ===============================
// Combobox: จัดการเมื่อเปิด/ปิด dropdown
// ===============================
function handleComboboxOpenChange(open: boolean) {
  isComboboxOpen.value = open
  console.log('combobox open state:', open)
  
  // เมื่อเปิด dropdown ให้รีเซ็ต search term เพื่อแสดงตัวเลือกทั้งหมด
  if (open) {
    organizationalSearch.value = ''
    // คำนวณขนาด dropdown ใหม่
    nextTick(() => {
      updateDropdownWidth()
    })
  } else {
    // เมื่อปิด dropdown ถ้ามีการเลือกแล้วให้แสดงชื่อที่เลือก
    if (selectedOrganizationalRisk.value) {
      organizationalSearch.value = selectedOrganizationalRisk.value.risk_name
    }
  }
}

// ===============================
// Combobox: จัดการการล้างค่า
// ===============================
function clearOrganizationalRisk() {
  console.log('ล้างการเลือกความเสี่ยงระดับองค์กร')
  selectedOrganizationalRisk.value = undefined
  organizationalSearch.value = ''
  form.organizational_risk_id = null
}

// ===============================
// คำนวณขนาด dropdown ให้เท่ากับ anchor
// ===============================
function updateDropdownWidth() {
  if (comboboxAnchorRef.value) {
    const anchorWidth = comboboxAnchorRef.value.offsetWidth
    dropdownWidth.value = `${anchorWidth}px`
    console.log('อัพเดทขนาด dropdown:', dropdownWidth.value)
  }
}

// ===============================
// Combobox: Display value function - แก้ไข type safety
// ===============================
const getDisplayValue = (value: ComboboxValue): string => {
  return value?.risk_name || ''
}
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head title="แก้ไขความเสี่ยงระดับฝ่าย" />

  <!-- ใช้ Layout หลักของแอปพลิเคชัน -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- พื้นที่หลักของหน้า -->
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- หัวข้อหน้า -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-2 gap-4">
        <div>
          <h1 class="text-2xl font-bold">แก้ไขความเสี่ยงระดับฝ่าย</h1>
          <p class="text-gray-500 mt-1">แก้ไขข้อมูลความเสี่ยงและเกณฑ์การประเมิน</p>
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
                  ชื่อความเสี่ยง
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
                />
                <p v-if="form.errors.risk_name" class="text-sm text-red-500">
                  {{ form.errors.risk_name }}
                </p>
              </div>

              <!-- ฟิลด์ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง (Combobox) -->
              <div class="grid gap-2">
                <Label for="organizational_risk_id">
                  ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง
                </Label>
                <!-- Combobox Radix Vue -->
                <ComboboxRoot
                  v-model="selectedOrganizationalRisk"
                  :display-value="getDisplayValue"
                  v-model:searchTerm="organizationalSearch"
                  @update:open="handleComboboxOpenChange"
                  class="w-full relative"
                >
                  <ComboboxAnchor
                    ref="comboboxAnchorRef"
                    class="relative flex items-center justify-between rounded border border-input bg-background px-3 h-10 w-full"
                  >
                    <ComboboxInput
                      id="organizational_risk_id"
                      class="!bg-transparent outline-none flex-1 text-base h-full selection:bg-gray-100 placeholder:text-gray-400"
                      :placeholder="selectedOrganizationalRisk ? selectedOrganizationalRisk.risk_name : 'ค้นหาหรือเลือกความเสี่ยงระดับองค์กร...'"
                      autocomplete="off"
                      aria-autocomplete="list"
                    />
                    <div class="flex items-center gap-1">
                      <!-- ปุ่มล้างค่า -->
                      <Button
                        v-if="selectedOrganizationalRisk"
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6 text-gray-400 hover:text-gray-600"
                        @click.stop="clearOrganizationalRisk"
                      >
                        <XIcon class="h-3 w-3" />
                      </Button>
                      <ComboboxTrigger>
                        <ChevronDownIcon class="h-4 w-4 text-gray-500" />
                      </ComboboxTrigger>
                    </div>
                  </ComboboxAnchor>
                  <ComboboxContent 
                    class="absolute z-10 mt-2 bg-white overflow-hidden rounded shadow-lg border border-gray-200"
                    :style="{ width: dropdownWidth }"
                  >
                    <ComboboxViewport class="p-1 max-h-60 overflow-y-auto">
                      <ComboboxEmpty class="text-gray-400 text-xs font-medium text-center py-2">
                        ไม่พบรายการ
                      </ComboboxEmpty>
                      <template v-for="risk in filteredOrganizationalRisks" :key="risk.id">
                        <ComboboxItem
                          :value="risk"
                          @select="handleSelectOrganizationalRisk(risk)"
                          class="text-base leading-none text-gray-700 rounded flex items-center h-10 px-3 pr-8 relative select-none data-[highlighted]:bg-primary/10 data-[highlighted]:text-primary cursor-pointer"
                        >
                          <span class="truncate">{{ risk.risk_name }}</span>
                          <ComboboxItemIndicator class="absolute right-2 inline-flex items-center">
                            <CheckIcon class="h-4 w-4 text-primary" />
                          </ComboboxItemIndicator>
                        </ComboboxItem>
                      </template>
                    </ComboboxViewport>
                  </ComboboxContent>
                </ComboboxRoot>
                <p v-if="form.errors.organizational_risk_id" class="text-sm text-red-500">
                  {{ form.errors.organizational_risk_id }}
                </p>
              </div>

              <!-- ฟิลด์รายละเอียด -->
              <div class="grid gap-2">
                <Label for="description">รายละเอียดความเสี่ยง</Label>
                <Textarea 
                  id="description" 
                  v-model="form.description" 
                  placeholder="ระบุรายละเอียดความเสี่ยง เช่น สาเหตุ ผลกระทบที่อาจเกิดขึ้น" 
                  :rows="6"
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
            <div v-if="existingAttachments.length > 0" class="mb-4">
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
            <span>{{ form.processing ? 'กำลังบันทึก...' : 'บันทึกการแก้ไข' }}</span>
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
          <p class="text-base font-medium">กำลังบันทึกข้อมูล กรุณารอสักครู่...</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
