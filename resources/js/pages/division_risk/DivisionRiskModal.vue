<!-- 
  ไฟล์: resources/js/pages/division_risk/DivisionRiskModal.vue
  แก้ไข TypeScript type errors สำหรับ Radix Vue Combobox
  - กำหนด generic type สำหรับ ComboboxRoot
  - ปรับแต่ง type definition ให้ compatible
-->

<script setup lang="ts">
// ===============================
// Imports
// ===============================
import { ref, computed, watch, nextTick } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

// UI Components
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'

// Combobox Radix Vue
import {
  ComboboxRoot, ComboboxAnchor, ComboboxInput, ComboboxTrigger, ComboboxContent,
  ComboboxViewport, ComboboxEmpty, ComboboxItem, ComboboxItemIndicator
} from 'radix-vue'

// Icons
import {
  SaveIcon, XIcon, UploadIcon, XCircleIcon, InfoIcon,
  Trash2Icon, HelpCircleIcon, Loader2Icon, ChevronDownIcon, CheckIcon
} from 'lucide-vue-next'

// Types และ Composables
import type { DivisionRisk, OrganizationalRisk } from '@/types/types'
import { useDivisionRiskData } from '@/composables/useDivisionRiskData'

// ===============================
// Interface & Type Definitions
// ===============================
interface CriteriaItem {
  level: number;
  name: string;
  description: string | null;
}

type RiskFormData = {
  risk_name: string;
  description: string;
  organizational_risk_id?: number | null;
  attachments: File[] | null;
  likelihood_criteria: Record<string, any>[];
  impact_criteria: Record<string, any>[];
}

// Type สำหรับ Combobox - ให้รองรับ OrganizationalRisk และ undefined
type ComboboxValue = OrganizationalRisk | undefined

// ===============================
// Props & Emits
// ===============================
const props = defineProps<{
  show: boolean;
  risk?: DivisionRisk;
  initialRisks?: DivisionRisk[];
  organizationalRisks?: OrganizationalRisk[];
}>()

const emit = defineEmits<{
  (e: 'update:show', value: boolean): void;
  (e: 'saved'): void;
}>()

// ===============================
// State & Reactive
// ===============================
const showCriteriaSection = ref<boolean>(false)
const showHelp = ref<boolean>(false)

// ฟิลด์ combobox สำหรับความเสี่ยงระดับองค์กร - ปรับ type ให้ compatible
const organizationalSearch = ref('')
const selectedOrganizationalRisk = ref<ComboboxValue>(undefined)
const isComboboxOpen = ref(false)

// Ref สำหรับคำนวณขนาด dropdown
const comboboxAnchorRef = ref<HTMLElement>()
const dropdownWidth = ref('auto')

// ฟิลด์ combobox: filter option ตาม search - แก้ไขให้แสดงทั้งหมดเมื่อไม่มี search term
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

// ===============================
// Composable สำหรับจัดการข้อมูล
// ===============================
const {
  existingAttachments, selectedFiles, fileNames,
  loadAttachments, submitForm, addSelectedFiles, removeSelectedFile,
  markAttachmentForDeletion, openAttachment, validateFiles,
  getFileIcon, formatFileSize
} = useDivisionRiskData(props.initialRisks, props.show)

// ===============================
// Computed Properties
// ===============================
const isEditing = computed(() => !!props.risk?.id)
const modalTitle = computed(() =>
  isEditing.value ? 'แก้ไขความเสี่ยงระดับฝ่าย' : 'เพิ่มความเสี่ยงระดับฝ่าย'
)

// ===============================
// Form Setup
// ===============================
const form = useForm<RiskFormData>({
  risk_name: '',
  description: '',
  organizational_risk_id: null,
  attachments: null,
  likelihood_criteria: [
    { level: 1, name: 'น้อยมาก', description: 'โอกาสเกิดน้อยกว่า 25%' },
    { level: 2, name: 'น้อย', description: 'โอกาสเกิด 25-50%' },
    { level: 3, name: 'ปานกลาง', description: 'โอกาสเกิด 51-75%' },
    { level: 4, name: 'สูง', description: 'โอกาสเกิดมากกว่า 75%' }
  ] as Record<string, any>[],
  impact_criteria: [
    { level: 1, name: 'น้อยมาก', description: 'ผลกระทบต่อฝ่ายเล็กน้อย' },
    { level: 2, name: 'น้อย', description: 'ผลกระทบต่อฝ่ายพอสมควร' },
    { level: 3, name: 'ปานกลาง', description: 'ผลกระทบต่อฝ่ายค่อนข้างมาก' },
    { level: 4, name: 'สูง', description: 'ผลกระทบต่อฝ่ายอย่างรุนแรง' }
  ] as Record<string, any>[],
})

// ===============================
// Watchers
// ===============================
watch(() => props.show, (newVal) => {
  if (newVal && props.risk) {
    // โหลดข้อมูลสำหรับการแก้ไข
    form.risk_name = props.risk.risk_name
    form.description = props.risk.description
    form.organizational_risk_id = props.risk.organizational_risk_id
    // sync combobox value - ปรับให้ไม่เป็น null
    selectedOrganizationalRisk.value = (props.organizationalRisks || []).find(
      r => r.id === props.risk?.organizational_risk_id
    ) || undefined
    organizationalSearch.value = selectedOrganizationalRisk.value?.risk_name || ''
    loadAttachments(props.risk)
    if (props.risk.likelihood_criteria && props.risk.likelihood_criteria.length > 0) {
      form.likelihood_criteria = [...props.risk.likelihood_criteria] as Record<string, any>[]
    }
    if (props.risk.impact_criteria && props.risk.impact_criteria.length > 0) {
      form.impact_criteria = [...props.risk.impact_criteria] as Record<string, any>[]
    }
  } else if (newVal) {
    // รีเซ็ตฟอร์มสำหรับการเพิ่มใหม่
    form.reset()
    selectedOrganizationalRisk.value = undefined
    organizationalSearch.value = ''
    loadAttachments()
  }
  
  // คำนวณขนาด dropdown เมื่อเปิด modal
  if (newVal) {
    nextTick(() => {
      updateDropdownWidth()
    })
  }
})

// ===============================
// Methods / Functions
// ===============================
const closeModal = () => {
  emit('update:show', false)
}

const toggleCriteriaSection = () => {
  showCriteriaSection.value = !showCriteriaSection.value;
}
const toggleHelp = () => {
  showHelp.value = !showHelp.value
}

const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement
  addSelectedFiles(input.files)
  if (input.files && input.files.length > 0) {
    form.attachments = Array.from(input.files)
  }
  input.value = ''
}

const validateForm = (): boolean => {
  let isValid = true
  const errors: string[] = []
  if (!form.risk_name.trim()) {
    errors.push('กรุณาระบุชื่อความเสี่ยง')
    isValid = false
  }
  if (!form.description.trim()) {
    errors.push('กรุณาระบุรายละเอียดความเสี่ยง')
    isValid = false
  }
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

const handleSubmit = async () => {
  if (!validateForm()) return
  try {
    toast.loading('กำลังบันทึกข้อมูล', {
      id: 'saving-risk',
      duration: 60000
    })
    // log
    console.log('submit division risk', {
      ...form,
      organizational_risk_id: selectedOrganizationalRisk.value?.id || null
    })
    // เตรียมข้อมูลสำหรับส่ง
    const formData = {
      risk_name: form.risk_name,
      description: form.description,
      organizational_risk_id: selectedOrganizationalRisk.value?.id || null,
      likelihood_criteria: JSON.parse(JSON.stringify(form.likelihood_criteria)),
      impact_criteria: JSON.parse(JSON.stringify(form.impact_criteria))
    }
    await submitForm(
      formData,
      isEditing.value ? props.risk?.id : undefined,
      closeModal
    )
    toast.success('บันทึกข้อมูลเรียบร้อย', { id: 'saving-risk' })
    emit('saved')
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล:', error)
    toast.error('ไม่สามารถบันทึกข้อมูลได้', {
      id: 'saving-risk',
      description: 'กรุณาลองใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ'
    })
  }
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
  <Dialog :open="show" @update:open="(val) => emit('update:show', val)">
    <DialogContent class="sm:max-w-[550px] max-w-[95%] max-h-[85vh] overflow-y-auto">
      <!-- ==================== ส่วนหัวของ Modal ==================== -->
      <DialogHeader>
        <DialogTitle>{{ modalTitle }}</DialogTitle>
        <DialogDescription class="sr-only">
          กรอกข้อมูลความเสี่ยงระดับฝ่าย
        </DialogDescription>
      </DialogHeader>

      <!-- ==================== แบบฟอร์มหลัก ==================== -->
      <form @submit.prevent="handleSubmit" class="space-y-4 mt-4">
        <div class="grid gap-4 py-2">
          <!-- ฟิลด์ชื่อความเสี่ยง -->
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
            <!-- Combobox Radix Vue - แก้ไข type ให้ compatible -->
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
              :rows="4"
            />
            <p v-if="form.errors.description" class="text-sm text-red-500">
              {{ form.errors.description }}
            </p>
          </div>

          <!-- ==================== ส่วนเกณฑ์การประเมิน ==================== -->
          <div class="grid gap-2 border-t border-gray-200 pt-4 mt-4">
            <div class="flex items-center justify-between">
              <Label class="text-base font-medium flex items-center gap-1">
                เกณฑ์การประเมินความเสี่ยง
                <Button 
                  type="button" 
                  variant="ghost" 
                  size="icon"
                  class="h-5 w-5 text-gray-500 hover:text-gray-700"
                  @click="toggleCriteriaSection"
                >
                  <HelpCircleIcon class="h-4 w-4" />
                </Button>
              </Label>
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
            <div v-if="showCriteriaSection" class="space-y-4 mt-2 animate-in fade-in-50 duration-300">
              <!-- เกณฑ์โอกาสเกิด (Likelihood) -->
              <div class="border rounded-md p-4 bg-muted/30">
                <h3 class="font-medium mb-2">เกณฑ์โอกาสเกิด (Likelihood)</h3>
                <div class="space-y-3">
                  <div 
                    v-for="(criteria, index) in form.likelihood_criteria" 
                    :key="`likelihood-${index}`" 
                    class="grid gap-2"
                  >
                    <div class="flex items-center gap-2">
                      <div class="bg-primary/10 text-primary font-medium rounded-full w-6 h-6 flex items-center justify-center text-xs">
                        {{ criteria.level }}
                      </div>
                      <Input
                        v-model="criteria.name" 
                        :placeholder="`ชื่อระดับ ${criteria.level}`"
                        class="max-w-[150px]"
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
              <!-- เกณฑ์ผลกระทบ (Impact) -->
              <div class="border rounded-md p-4 bg-muted/30">
                <h3 class="font-medium mb-2">เกณฑ์ผลกระทบ (Impact)</h3>
                <div class="space-y-3">
                  <div 
                    v-for="(criteria, index) in form.impact_criteria" 
                    :key="`impact-${index}`" 
                    class="grid gap-2"
                  >
                    <div class="flex items-center gap-2">
                      <div class="bg-primary/10 text-primary font-medium rounded-full w-6 h-6 flex items-center justify-center text-xs">
                        {{ criteria.level }}
                      </div>
                      <Input
                        v-model="criteria.name" 
                        :placeholder="`ชื่อระดับ ${criteria.level}`"
                        class="max-w-[150px]"
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
            <div v-if="!showCriteriaSection" class="text-xs text-muted-foreground">
              คลิก "แสดงรายละเอียด" เพื่อกำหนดเกณฑ์การประเมินความเสี่ยงทั้ง 4 ระดับ
            </div>
          </div>

          <!-- ==================== ส่วนเอกสารแนบ ==================== -->
          <div class="grid gap-2">
            <Label>เอกสารแนบ</Label>
            <div v-if="existingAttachments.length > 0" class="mb-3">
              <p class="text-sm font-medium text-gray-700 mb-2">เอกสารแนบปัจจุบัน:</p>
              <ul class="space-y-2">
                <li 
                  v-for="attachment in existingAttachments" 
                  :key="attachment.id" 
                  class="flex flex-wrap items-center justify-between p-2 bg-gray-50 rounded-md text-sm border border-gray-200"
                >
                  <div 
                    class="flex items-center gap-2 flex-1 min-w-0 overflow-hidden cursor-pointer" 
                    @click="openAttachment(attachment.url)"
                  >
                    <component :is="getFileIcon(attachment.file_name)" class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate max-w-[200px] sm:max-w-[300px]">{{ attachment.file_name }}</span>
                    <span class="text-xs text-gray-500 flex-shrink-0">
                      {{ formatFileSize(attachment.file_size || 0) }}
                    </span>
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
            <div v-if="fileNames.length > 0" class="mb-3">
              <p class="text-sm font-medium text-gray-700 mb-2">ไฟล์ที่เลือกไว้:</p>
              <ul class="space-y-2">
                <li 
                  v-for="(fileName, index) in fileNames" 
                  :key="index"
                  class="flex items-center justify-between p-2 bg-gray-50 rounded-md text-sm border border-gray-200"
                >
                  <div class="flex items-center gap-2 flex-1 overflow-hidden">
                    <component :is="getFileIcon(fileName)" class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate">{{ fileName }}</span>
                    <span class="text-xs text-gray-500 flex-shrink-0">
                      {{ formatFileSize(selectedFiles[index].size) }}
                    </span>
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
            <div class="flex flex-col">
              <div class="flex flex-wrap items-center gap-2">
                <label for="file-upload" class="flex items-center gap-2 cursor-pointer px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                <p class="text-xs text-gray-500">
                  รองรับไฟล์ประเภท PDF, Word, Excel, รูปภาพ (ขนาดไม่เกิน 10MB)
                </p>
              </div>
              <p v-if="form.errors.attachments" class="text-sm text-red-500 mt-1">
                {{ form.errors.attachments }}
              </p>
            </div>
          </div>
        </div>

        <!-- ==================== ปุ่มดำเนินการ ==================== -->
        <DialogFooter class="flex flex-col-reverse sm:flex-row items-center justify-end space-y-2 sm:space-y-0 sm:space-x-2 space-y-reverse">
          <Button
            type="button"
            variant="outline"
            @click="closeModal"
            class="w-full sm:w-auto flex items-center gap-2"
          >
            <XIcon class="h-4 w-4" />
            <span>ยกเลิก</span>
          </Button>
          <Button
            type="submit"
            :disabled="form.processing"
            class="w-full sm:w-auto flex items-center gap-2"
          >
            <Loader2Icon v-if="form.processing" class="h-4 w-4 animate-spin" />
            <SaveIcon v-else class="h-4 w-4" />
            <span>
              {{ form.processing ? 'กำลังบันทึก...' : (isEditing ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูล') }}
            </span>
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
    <!-- Loading Overlay -->
    <div 
      v-if="form.processing" 
      class="absolute inset-0 bg-white/70 flex items-center justify-center rounded-lg z-50"
    >
      <div class="flex flex-col items-center gap-2">
        <Loader2Icon class="h-8 w-8 animate-spin text-primary" />
        <p class="text-sm font-medium">กำลังบันทึกข้อมูล กรุณารอสักครู่...</p>
      </div>
    </div>
  </Dialog>
</template>
