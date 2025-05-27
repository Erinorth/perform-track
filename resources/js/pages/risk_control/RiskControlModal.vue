<!-- 
  ไฟล์: resources/js/pages/risk_control/RiskControlModal.vue
  คำอธิบาย: Modal component สำหรับเพิ่ม/แก้ไขข้อมูลการควบคุมความเสี่ยง
  ใช้: แสดงฟอร์มสำหรับกรอกข้อมูลการควบคุมความเสี่ยง และจัดการเอกสารแนบ
  การทำงาน: รองรับการเลือกความเสี่ยงระดับฝ่ายและกำหนดรายละเอียดการควบคุม
  แก้ไข: ใช้ DivisionRiskCombobox แทน select element
-->

<script setup lang="ts">
// ==================== นำเข้าไลบรารีและคอมโพเนนต์ที่จำเป็น ====================
import { computed, watch, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog'
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
import { toast } from 'vue-sonner'

// ==================== นำเข้า Icons ====================
import { 
  SaveIcon, 
  XIcon, 
  UploadIcon, 
  XCircleIcon, 
  InfoIcon, 
  Trash2Icon, 
  HelpCircleIcon, 
  Loader2Icon,
  ShieldIcon,
  UserIcon,
  SettingsIcon,
  CheckCircleIcon,
  AlertCircleIcon
} from 'lucide-vue-next'

// ==================== นำเข้า Types ====================
import type { RiskControl, DivisionRisk, RiskControlAttachment } from '@/types/risk-control'
import { useRiskControlData } from '@/composables/useRiskControlData'

// ==================== นำเข้า DivisionRiskCombobox ====================
import DivisionRiskCombobox from '@/components/forms/DivisionRiskCombobox.vue'

// ==================== กำหนด Types สำหรับฟอร์ม ====================
type ControlFormData = {
  control_name: string;
  description: string;
  owner: string;
  status: 'active' | 'inactive';
  control_type: 'preventive' | 'detective' | 'corrective' | 'compensating' | '';
  implementation_details: string;
  division_risk_id: number | null;
  attachments: File[] | null;
}

// ==================== กำหนด Props และ Events ====================
const props = defineProps<{
  show: boolean; // สถานะแสดง/ซ่อน Modal
  control?: RiskControl; // ข้อมูลการควบคุมความเสี่ยงสำหรับการแก้ไข
  initialControls?: RiskControl[]; // ข้อมูลการควบคุมความเสี่ยงทั้งหมด
  divisionRisks: DivisionRisk[]; // ข้อมูลความเสี่ยงระดับฝ่ายทั้งหมด
  isEdit?: boolean; // โหมดแก้ไขหรือไม่
}>()

const emit = defineEmits<{
  (e: 'update:show', value: boolean): void; // อัปเดตสถานะ Modal
  (e: 'saved'): void; // บันทึกข้อมูลสำเร็จ
  (e: 'cancel'): void; // ยกเลิกการแก้ไข
}>()

// ==================== ใช้ Composable สำหรับจัดการข้อมูล ====================
const { 
  existingAttachments, 
  selectedFiles, 
  fileNames,
  loadAttachments, 
  submitForm, 
  addSelectedFiles, 
  removeSelectedFile, 
  markAttachmentForDeletion, 
  openAttachment, 
  validateFiles,
  getFileIcon, 
  formatFileSize 
} = useRiskControlData(props.initialControls, props.show)

// ==================== สถานะสำหรับแสดง Tooltip ช่วยเหลือ ====================
const showTooltip = ref<string | null>(null)

// ==================== State สำหรับ DivisionRisk Object ====================
const selectedDivisionRisk = ref<DivisionRisk | null>(null)

// ==================== Computed Properties ====================
const isEditing = computed(() => props.isEdit && !!props.control?.id)
const modalTitle = computed(() => 
  isEditing.value ? 'แก้ไขการควบคุมความเสี่ยง' : 'เพิ่มการควบคุมความเสี่ยง'
)

// ==================== สร้างฟอร์มด้วย Inertia useForm ====================
const form = useForm<ControlFormData>({
  control_name: props.control?.control_name ?? '',
  description: props.control?.description ?? '',
  owner: props.control?.owner ?? '',
  status: props.control?.status ?? 'active',
  control_type: props.control?.control_type ?? '',
  implementation_details: props.control?.implementation_details ?? '',
  division_risk_id: props.control?.division_risk_id ?? null,
  attachments: null,
})

// ==================== Helper Functions ====================
/**
 * หาความเสี่ยงหน่วยงานจาก ID
 */
const findDivisionRiskById = (id: number | null): DivisionRisk | null => {
  if (!id || !props.divisionRisks) return null
  return props.divisionRisks.find(risk => risk.id === id) || null
}

/**
 * ป้ายกำกับประเภทการควบคุม
 */
const getControlTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    'preventive': 'การป้องกัน',
    'detective': 'การตรวจจับ',
    'corrective': 'การแก้ไข',
    'compensating': 'การชดเชย'
  }
  return labels[type] || 'ไม่ระบุ'
}

/**
 * ป้ายกำกับสถานะการควบคุม
 */
const getStatusLabel = (status: string): string => {
  return status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน'
}

/**
 * ไอคอนสำหรับประเภทการควบคุม
 */
const getControlTypeIcon = (type: string) => {
  const icons: Record<string, any> = {
    'preventive': ShieldIcon,
    'detective': CheckCircleIcon,
    'corrective': SettingsIcon,
    'compensating': AlertCircleIcon
  }
  return icons[type] || ShieldIcon
}

// ==================== Event Handlers ====================
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
  form.division_risk_id = risk.id
  
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
  form.division_risk_id = null
}

/**
 * จัดการการเปลี่ยนสถานะการควบคุม
 */
const onStatusChange = (value: unknown) => {
  const stringValue = value === null || value === undefined ? 'active' : String(value)
  form.status = stringValue as 'active' | 'inactive'
  console.log('เลือกสถานะการควบคุม:', stringValue)
}

/**
 * จัดการการเปลี่ยนประเภทการควบคุม
 */
const onControlTypeChange = (value: unknown) => {
  const stringValue = value === null || value === undefined ? '' : String(value)
  form.control_type = stringValue as 'preventive' | 'detective' | 'corrective' | 'compensating' | ''
  console.log('เลือกประเภทการควบคุม:', stringValue)
}

// ==================== Watchers ====================
// โหลดข้อมูลเมื่อ Modal เปิด
watch(() => props.show, (newVal) => {
  if (newVal && props.control && isEditing.value) {
    // โหลดข้อมูลสำหรับการแก้ไข
    console.log('กำลังโหลดข้อมูล control สำหรับแก้ไข:', props.control);
    
    // แสดงข้อมูลเกี่ยวกับเอกสารแนบที่มีอยู่
    console.log('ข้อมูลเอกสารแนบที่มากับ control:', {
      hasAttachments: !!props.control.attachments,
      attachmentsCount: props.control.attachments?.length || 0,
      keys: Object.keys(props.control)
    });
    
    // กำหนดค่าให้ฟอร์ม
    form.control_name = props.control.control_name;
    form.description = props.control.description || '';
    form.owner = props.control.owner || '';
    form.status = props.control.status;
    form.control_type = props.control.control_type || '';
    form.implementation_details = props.control.implementation_details || '';
    form.division_risk_id = props.control.division_risk_id;
    
    // ตั้งค่า selectedDivisionRisk
    selectedDivisionRisk.value = findDivisionRiskById(props.control.division_risk_id)
    
    // โหลดเอกสารแนบ
    loadAttachments(props.control);
  } else if (newVal) {
    // รีเซ็ตฟอร์มสำหรับการเพิ่มใหม่
    form.reset();
    form.status = 'active';
    form.control_type = '';
    selectedDivisionRisk.value = null;
    loadAttachments();
  }
});

/**
 * ปิด Modal และรีเซ็ตสถานะ
 */
const closeModal = () => {
  emit('update:show', false)
  emit('cancel')
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
 * แสดงหรือซ่อน tooltip ช่วยเหลือ
 */
const toggleTooltip = (name: string) => {
  showTooltip.value = showTooltip.value === name ? null : name
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
    const loadingMessage = isEditing.value ? 'กำลังบันทึกการแก้ไข' : 'กำลังสร้างการควบคุมใหม่'
    toast.loading(loadingMessage, {
      id: 'saving-control',
      duration: 60000
    })
    
    await submitForm(
      { 
        control_name: form.control_name,
        description: form.description,
        owner: form.owner,
        status: form.status,
        control_type: form.control_type,
        implementation_details: form.implementation_details,
        division_risk_id: form.division_risk_id
      }, 
      isEditing.value ? props.control?.id : undefined,
      closeModal
    )
    
    // เปลี่ยน toast เป็นแจ้งเตือนสำเร็จ
    const successMessage = isEditing.value 
      ? 'บันทึกการแก้ไขเรียบร้อยแล้ว' 
      : 'สร้างการควบคุมความเสี่ยงเรียบร้อยแล้ว'
    
    toast.success(successMessage, {
      id: 'saving-control'
    })
    
    emit('saved')
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล:', error)
    
    const errorMessage = isEditing.value 
      ? 'เกิดข้อผิดพลาดในการบันทึกการแก้ไข' 
      : 'เกิดข้อผิดพลาดในการสร้างการควบคุม'
    
    toast.error(errorMessage, {
      id: 'saving-control',
      description: 'กรุณาลองใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ'
    })
  }
}
</script>

<template>
  <Dialog 
    :open="show" 
    @update:open="(val) => emit('update:show', val)"
  >
    <DialogContent class="sm:max-w-[600px] max-w-[95%] max-h-[90vh] overflow-y-auto">
      <!-- ส่วนหัวของ Modal -->
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <ShieldIcon class="h-5 w-5" />
          {{ modalTitle }}
        </DialogTitle>
        <DialogDescription class="sr-only">กรอกข้อมูลการควบคุมความเสี่ยง</DialogDescription>
      </DialogHeader>

      <!-- แบบฟอร์มกรอกข้อมูล -->
      <form @submit.prevent="handleSubmit" class="space-y-4 mt-4">
        <div class="grid gap-4 py-2">
          <!-- ฟิลด์ชื่อการควบคุมความเสี่ยง -->
          <div class="grid gap-2">
            <Label for="control_name" class="flex items-center gap-1">
              ชื่อการควบคุมความเสี่ยง <span class="text-red-500">*</span>
              <Button 
                type="button" 
                variant="ghost" 
                size="icon"
                class="h-5 w-5 text-gray-500 hover:text-gray-700"
                @click="toggleTooltip('control_name')"
              >
                <HelpCircleIcon class="h-4 w-4" />
              </Button>
            </Label>
            
            <!-- ข้อความช่วยเหลือ -->
            <div v-if="showTooltip === 'control_name'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
              ระบุชื่อการควบคุมความเสี่ยงที่ชัดเจนและเข้าใจง่าย
            </div>
            
            <Input 
              id="control_name" 
              v-model="form.control_name" 
              type="text"
              placeholder="ระบุชื่อการควบคุมความเสี่ยง"
              :class="{ 'border-red-500': form.errors.control_name }"
            />
            <p v-if="form.errors.control_name" class="text-sm text-red-500">
              {{ form.errors.control_name }}
            </p>
          </div>

          <!-- ความเสี่ยงระดับฝ่าย -->
          <div class="grid gap-2">
            <Label class="flex items-center gap-1">
              ความเสี่ยงระดับฝ่าย <span class="text-red-500">*</span>
              <Button 
                type="button" 
                variant="ghost" 
                size="icon"
                class="h-5 w-5 text-gray-500 hover:text-gray-700"
                @click="toggleTooltip('division_risk')"
              >
                <HelpCircleIcon class="h-4 w-4" />
              </Button>
            </Label>
            
            <!-- คำอธิบายสำหรับความเสี่ยงฝ่าย -->
            <div v-if="showTooltip === 'division_risk'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
              เลือกความเสี่ยงระดับฝ่ายที่ต้องการสร้างการควบคุม<br>
              การควบคุมจะเชื่อมโยงกับความเสี่ยงที่เลือก
            </div>
            
            <!-- ใช้ DivisionRiskCombobox -->
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

          <!-- Grid 2 คอลัมน์สำหรับประเภทการควบคุมและสถานะ -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- ประเภทการควบคุม -->
            <div class="grid gap-2">
              <Label for="control_type" class="flex items-center gap-1">
                ประเภทการควบคุม
                <Button 
                  type="button" 
                  variant="ghost" 
                  size="icon"
                  class="h-5 w-5 text-gray-500 hover:text-gray-700"
                  @click="toggleTooltip('control_type')"
                >
                  <HelpCircleIcon class="h-4 w-4" />
                </Button>
              </Label>
              
              <!-- ข้อความช่วยเหลือสำหรับประเภทการควบคุม -->
              <div v-if="showTooltip === 'control_type'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
                <p class="font-medium mb-1">ประเภทการควบคุมความเสี่ยง:</p>
                <ul class="list-disc pl-4 space-y-1">
                  <li><span class="font-medium">การป้องกัน:</span> ป้องกันไม่ให้เหตุการณ์เสี่ยงเกิดขึ้น</li>
                  <li><span class="font-medium">การตรวจจับ:</span> ตรวจจับเหตุการณ์เสี่ยงหลังจากเกิดขึ้น</li>
                  <li><span class="font-medium">การแก้ไข:</span> แก้ไขเหตุการณ์เสี่ยงหลังจากเกิดขึ้น</li>
                  <li><span class="font-medium">การชดเชย:</span> ชดเชยเมื่อการควบคุมหลักไม่สามารถทำงานได้</li>
                </ul>
              </div>
              
              <Select 
                v-model="form.control_type"
                @update:model-value="onControlTypeChange"
              >
                <SelectTrigger 
                  class="w-full" 
                  :class="{ 'border-red-500': form.errors.control_type }"
                >
                  <SelectValue placeholder="เลือกประเภทการควบคุม" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="preventive">
                    <div class="flex items-center gap-2">
                      <ShieldIcon class="h-4 w-4" />
                      การป้องกัน (Preventive)
                    </div>
                  </SelectItem>
                  <SelectItem value="detective">
                    <div class="flex items-center gap-2">
                      <CheckCircleIcon class="h-4 w-4" />
                      การตรวจจับ (Detective)
                    </div>
                  </SelectItem>
                  <SelectItem value="corrective">
                    <div class="flex items-center gap-2">
                      <SettingsIcon class="h-4 w-4" />
                      การแก้ไข (Corrective)
                    </div>
                  </SelectItem>
                  <SelectItem value="compensating">
                    <div class="flex items-center gap-2">
                      <AlertCircleIcon class="h-4 w-4" />
                      การชดเชย (Compensating)
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.control_type" class="text-sm text-red-500">
                {{ form.errors.control_type }}
              </p>
            </div>

            <!-- สถานะการควบคุม -->
            <div class="grid gap-2">
              <Label for="status" class="flex items-center gap-1">
                สถานะการควบคุม <span class="text-red-500">*</span>
                <Button 
                  type="button" 
                  variant="ghost" 
                  size="icon"
                  class="h-5 w-5 text-gray-500 hover:text-gray-700"
                  @click="toggleTooltip('status')"
                >
                  <HelpCircleIcon class="h-4 w-4" />
                </Button>
              </Label>
              
              <!-- ข้อความช่วยเหลือสำหรับสถานะ -->
              <div v-if="showTooltip === 'status'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
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
                  class="w-full" 
                  :class="{ 'border-red-500': form.errors.status }"
                >
                  <SelectValue placeholder="เลือกสถานะการควบคุม" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">
                    <div class="flex items-center gap-2">
                      <CheckCircleIcon class="h-4 w-4 text-green-600" />
                      ใช้งาน
                    </div>
                  </SelectItem>
                  <SelectItem value="inactive">
                    <div class="flex items-center gap-2">
                      <XIcon class="h-4 w-4 text-red-600" />
                      ไม่ใช้งาน
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.status" class="text-sm text-red-500">
                {{ form.errors.status }}
              </p>
            </div>
          </div>

          <!-- ผู้รับผิดชอบ -->
          <div class="grid gap-2">
            <Label for="owner" class="flex items-center gap-1">
              <UserIcon class="h-4 w-4" />
              ผู้รับผิดชอบ
              <Button 
                type="button" 
                variant="ghost" 
                size="icon"
                class="h-5 w-5 text-gray-500 hover:text-gray-700"
                @click="toggleTooltip('owner')"
              >
                <HelpCircleIcon class="h-4 w-4" />
              </Button>
            </Label>
            
            <!-- คำอธิบายสำหรับผู้รับผิดชอบ -->
            <div v-if="showTooltip === 'owner'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
              ระบุชื่อผู้รับผิดชอบหลักในการดำเนินการควบคุมความเสี่ยงนี้
            </div>
            
            <Input
              id="owner"
              v-model="form.owner"
              type="text"
              placeholder="ระบุชื่อผู้รับผิดชอบการควบคุมนี้"
              :class="{ 'border-red-500': form.errors.owner }"
            />
            <p v-if="form.errors.owner" class="text-sm text-red-500">
              {{ form.errors.owner }}
            </p>
          </div>

          <!-- คำอธิบายการควบคุม -->
          <div class="grid gap-2">
            <Label for="description" class="flex items-center gap-1">
              คำอธิบายการควบคุม
              <Button 
                type="button" 
                variant="ghost" 
                size="icon"
                class="h-5 w-5 text-gray-500 hover:text-gray-700"
                @click="toggleTooltip('description')"
              >
                <HelpCircleIcon class="h-4 w-4" />
              </Button>
            </Label>
            
            <!-- คำอธิบายสำหรับช่องคำอธิบาย -->
            <div v-if="showTooltip === 'description'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
              อธิบายรายละเอียดของการควบคุมความเสี่ยงนี้ วัตถุประสงค์ และขอบเขตการทำงาน
            </div>
            
            <Textarea
              id="description"
              v-model="form.description"
              placeholder="อธิบายรายละเอียดของการควบคุมความเสี่ยงนี้"
              rows="3"
              :class="{ 'border-red-500': form.errors.description }"
            />
            <p v-if="form.errors.description" class="text-sm text-red-500">
              {{ form.errors.description }}
            </p>
          </div>

          <!-- รายละเอียดการดำเนินการ -->
          <div class="grid gap-2">
            <Label for="implementation_details" class="flex items-center gap-1">
              <SettingsIcon class="h-4 w-4" />
              รายละเอียดการดำเนินการ
              <Button 
                type="button" 
                variant="ghost" 
                size="icon"
                class="h-5 w-5 text-gray-500 hover:text-gray-700"
                @click="toggleTooltip('implementation_details')"
              >
                <HelpCircleIcon class="h-4 w-4" />
              </Button>
            </Label>
            
            <!-- คำอธิบายสำหรับรายละเอียดการดำเนินการ -->
            <div v-if="showTooltip === 'implementation_details'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
              อธิบายวิธีการดำเนินการ ขั้นตอน เครื่องมือ และแนวทางการปฏิบัติงานของการควบคุมนี้
            </div>
            
            <Textarea
              id="implementation_details"
              v-model="form.implementation_details"
              placeholder="อธิบายวิธีการดำเนินการและขั้นตอนของการควบคุมนี้"
              rows="4"
              :class="{ 'border-red-500': form.errors.implementation_details }"
            />
            <p v-if="form.errors.implementation_details" class="text-sm text-red-500">
              {{ form.errors.implementation_details }}
            </p>
          </div>

          <!-- ส่วนของเอกสารแนบ -->
          <div class="grid gap-2">
            <Label class="flex items-center gap-1">
              เอกสารแนบ
              <Button 
                type="button" 
                variant="ghost" 
                size="icon"
                class="h-5 w-5 text-gray-500 hover:text-gray-700"
                @click="toggleTooltip('attachments')"
              >
                <HelpCircleIcon class="h-4 w-4" />
              </Button>
            </Label>
            
            <!-- คำอธิบายสำหรับเอกสารแนบ -->
            <div v-if="showTooltip === 'attachments'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
              แนบไฟล์เอกสารที่เกี่ยวข้องกับการควบคุมความเสี่ยง เช่น คู่มือ แนวทางปฏิบัติ หรือเอกสารอ้างอิงอื่นๆ
            </div>
            
            <!-- แสดงเอกสารแนบที่มีอยู่แล้ว (กรณีแก้ไข) -->
            <div v-if="existingAttachments.length > 0" class="mb-3">
              <p class="text-sm font-medium text-gray-700 mb-2">เอกสารแนบปัจจุบัน ({{ existingAttachments.length }}):</p>
              <ul class="space-y-2">
                <li 
                  v-for="attachment in existingAttachments" 
                  :key="attachment.id" 
                  class="flex flex-wrap items-center justify-between p-2 bg-gray-50 rounded-md text-sm border border-gray-200"
                >
                  <!-- ส่วนแสดงข้อมูลไฟล์ - สามารถคลิกเพื่อเปิดดู -->
                  <div 
                    class="flex items-center gap-2 flex-1 min-w-0 overflow-hidden cursor-pointer" 
                    @click="openAttachment(attachment.url)"
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
                    class="text-red-500 hover:text-red-700 hover:bg-red-50 ml-1 flex-shrink-0"
                    aria-label="ลบเอกสาร"
                  >
                    <Trash2Icon class="h-4 w-4" />
                  </Button>
                </li>
              </ul>
            </div>
            <div v-else-if="isEditing" class="text-sm text-gray-500 bg-gray-50 p-3 rounded-md mb-3">
              <div class="flex items-center gap-1">
                <InfoIcon class="h-4 w-4" />
                <span>ไม่มีเอกสารแนบสำหรับการควบคุมนี้</span>
              </div>
            </div>
            
            <!-- แสดงไฟล์ที่เพิ่งอัปโหลด (ยังไม่ได้บันทึก) -->
            <div v-if="selectedFiles.length > 0" class="mb-3">
              <p class="text-sm font-medium text-gray-700 mb-2">ไฟล์ที่เลือกไว้:</p>
              <ul class="space-y-2">
                <li 
                  v-for="(file, index) in selectedFiles" 
                  :key="index"
                  class="flex items-center justify-between p-2 bg-gray-50 rounded-md text-sm border border-gray-200"
                >
                  <div class="flex items-center gap-2 flex-1 overflow-hidden">
                    <component :is="getFileIcon(file.name)" class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate">{{ file.name }}</span>
                    <span class="text-xs text-gray-500 flex-shrink-0">{{ formatFileSize(file.size) }}</span>
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
                
                <p class="text-xs text-gray-500">รองรับไฟล์ประเภท PDF, Word, Excel, รูปภาพ (ขนาดไม่เกิน 10MB)</p>
              </div>
              
              <p v-if="form.errors.attachments" class="text-sm text-red-500 mt-1">
                {{ form.errors.attachments }}
              </p>
            </div>
          </div>
        </div>

        <!-- ปุ่มบันทึกและยกเลิก -->
        <DialogFooter>
          <div class="flex justify-end gap-2 mt-4">
            <Button 
              type="button" 
              variant="secondary" 
              @click="closeModal"
            >
              <XIcon class="h-4 w-4 mr-2" />
              ยกเลิก
            </Button>
            <Button 
              type="submit" 
              :disabled="form.processing || !selectedDivisionRisk"
              :class="{'opacity-50 cursor-not-allowed': form.processing || !selectedDivisionRisk}"
            >
              <SaveIcon class="h-4 w-4 mr-2" />
              {{ isEditing ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูล' }}
              <Loader2Icon v-if="form.processing" class="animate-spin h-4 w-4 ml-2" />
            </Button>
          </div>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<style scoped>
/* Animation สำหรับ loading */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* สไตล์สำหรับ hover effects */
.cursor-pointer:hover {
  background-color: rgba(59, 130, 246, 0.05);
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .grid-cols-1 {
    gap: 1rem;
  }
  
  .sm\\:grid-cols-2 {
    grid-template-columns: 1fr;
  }
}
</style>
