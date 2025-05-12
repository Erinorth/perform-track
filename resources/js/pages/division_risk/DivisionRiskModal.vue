<!-- 
  ไฟล์: resources/js/pages/division_risk/DivisionRiskModal.vue
  คำอธิบาย: Modal component สำหรับเพิ่ม/แก้ไขข้อมูลความเสี่ยงระดับฝ่าย
  ทำหน้าที่: แสดงฟอร์มสำหรับกรอกข้อมูลความเสี่ยง, อัปโหลดเอกสารแนบ
  หลักการ: ใช้ Dialog จาก shadcn-vue เป็นพื้นฐาน, แสดงฟอร์มและการจัดการเอกสารแนบ
  ใช้ร่วมกับ: DivisionRiskController.php ในฝั่ง Backend
-->

<script setup lang="ts">
// นำเข้าไลบรารีและคอมโพเนนต์ที่จำเป็น
import { computed, watch, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { toast } from 'vue-sonner'
import { SaveIcon, XIcon, UploadIcon, XCircleIcon, InfoIcon, Trash2Icon, HelpCircleIcon, Loader2Icon } from 'lucide-vue-next'
import type { DivisionRisk, OrganizationalRisk, CriteriaItem } from '@/types/types'
import { useDivisionRiskData } from '@/composables/useDivisionRiskData'

// กำหนด Types สำหรับฟอร์ม
type RiskFormData = {
  risk_name: string;
  description: string;
  organizational_risk_id?: number | null;
  attachments: File[] | null;
  likelihood_criteria: CriteriaItem[];
  impact_criteria: CriteriaItem[];
}

// กำหนด props และ events
const props = defineProps<{
  show: boolean; // สถานะแสดง/ซ่อน Modal
  risk?: DivisionRisk; // ข้อมูลความเสี่ยงสำหรับการแก้ไข
  initialRisks?: DivisionRisk[]; // ข้อมูลความเสี่ยงทั้งหมด
  organizationalRisks?: OrganizationalRisk[]; // ข้อมูลความเสี่ยงระดับองค์กรทั้งหมด
}>()

// เพิ่มตัวแปรเก็บสถานะการแสดงส่วนเกณฑ์การประเมิน
const showCriteriaSection = ref<boolean>(false);

// ฟังก์ชันสลับการแสดง/ซ่อนส่วนเกณฑ์การประเมิน
const toggleCriteriaSection = () => {
  showCriteriaSection.value = !showCriteriaSection.value;
}

const emit = defineEmits<{
  (e: 'update:show', value: boolean): void; // อัปเดตสถานะ Modal
  (e: 'saved'): void; // บันทึกข้อมูลสำเร็จ
}>()

// ใช้ composable เพื่อแยกการจัดการข้อมูลออกจาก UI
const { 
  existingAttachments, selectedFiles, fileNames,
  loadAttachments, submitForm, addSelectedFiles, removeSelectedFile, 
  markAttachmentForDeletion, openAttachment, validateFiles,
  getFileIcon, formatFileSize 
} = useDivisionRiskData(props.initialRisks, props.show)

// สถานะสำหรับแสดง tooltip ช่วยเหลือ
const showHelp = ref<boolean>(false)

// Computed Properties
const isEditing = computed(() => !!props.risk?.id)
const modalTitle = computed(() => isEditing.value ? 'แก้ไขความเสี่ยงระดับฝ่าย' : 'เพิ่มความเสี่ยงระดับฝ่าย')

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
  ],
  // เพิ่มค่าเริ่มต้นสำหรับเกณฑ์ผลกระทบ 4 ระดับ
  impact_criteria: [
    { level: 1, name: 'น้อยมาก', description: 'ผลกระทบต่อฝ่ายเล็กน้อย' },
    { level: 2, name: 'น้อย', description: 'ผลกระทบต่อฝ่ายพอสมควร' },
    { level: 3, name: 'ปานกลาง', description: 'ผลกระทบต่อฝ่ายค่อนข้างมาก' },
    { level: 4, name: 'สูง', description: 'ผลกระทบต่อฝ่ายอย่างรุนแรง' }
  ],
})

// Watchers
watch(() => props.show, (newVal) => {
  if (newVal && props.risk) {
    // โหลดข้อมูลสำหรับการแก้ไข
    console.log('กำลังโหลดข้อมูลสำหรับแก้ไข:', props.risk.risk_name)
    form.risk_name = props.risk.risk_name
    form.description = props.risk.description
    form.organizational_risk_id = props.risk.organizational_risk_id
    loadAttachments(props.risk)
    
    // โหลดข้อมูลเกณฑ์การประเมิน (ถ้ามี)
    if (props.risk.likelihood_criteria && props.risk.likelihood_criteria.length > 0) {
      form.likelihood_criteria = props.risk.likelihood_criteria;
    }
    if (props.risk.impact_criteria && props.risk.impact_criteria.length > 0) {
      form.impact_criteria = props.risk.impact_criteria;
    }
  } else if (newVal) {
    form.reset()
    loadAttachments()
  }
})

/**
 * ปิด Modal และรีเซ็ตสถานะ
 */
const closeModal = () => {
  emit('update:show', false)
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
      duration: 60000 // ตั้งเวลานานพอที่จะรอการประมวลผลเสร็จ
    })
    
    console.log('กำลังส่งข้อมูล, mode:', isEditing.value ? 'แก้ไข' : 'เพิ่ม', 'id:', props.risk?.id)
    
    await submitForm(
      { 
        risk_name: form.risk_name,
        description: form.description,
        organizational_risk_id: form.organizational_risk_id,
        likelihood_criteria: form.likelihood_criteria,
        impact_criteria: form.impact_criteria
      }, 
      isEditing.value ? props.risk?.id : undefined,
      closeModal
    )
    
    // เปลี่ยน toast เป็นแจ้งเตือนสำเร็จ
    toast.success('บันทึกข้อมูลเรียบร้อย', {
      id: 'saving-risk'
    })
    
    emit('saved')
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล:', error)
    
    // เปลี่ยน toast เป็นแจ้งเตือนข้อผิดพลาด
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
</script>

<template>
  <Dialog 
    :open="show" 
    @update:open="(val) => emit('update:show', val)"
  >
    <DialogContent class="sm:max-w-[550px] max-w-[95%] max-h-[85vh] overflow-y-auto">
      <!-- ส่วนหัวของ Modal -->
      <DialogHeader>
        <DialogTitle>{{ modalTitle }}</DialogTitle>
        <DialogDescription class="sr-only">กรอกข้อมูลความเสี่ยงระดับฝ่าย</DialogDescription>
      </DialogHeader>

      <!-- แบบฟอร์มกรอกข้อมูล -->
      <form @submit.prevent="handleSubmit" class="space-y-4 mt-4">
        <div class="grid gap-4 py-2">
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

          <!-- ฟิลด์ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง (เพิ่มใหม่) -->
          <div class="grid gap-2">
            <Label for="organizational_risk_id">ความเสี่ยงระดับองค์กรที่เกี่ยวข้อง</Label>
            <select
              id="organizational_risk_id"
              v-model="form.organizational_risk_id"
              class="rounded-md border border-input bg-background px-3 py-2"
            >
              <option :value="null">-- เลือกความเสี่ยงระดับองค์กร --</option>
              <option
                v-for="risk in props.organizationalRisks || []"
                :key="risk.id"
                :value="risk.id"
              >
                {{ risk.risk_name }}
              </option>
            </select>
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

          <!-- ส่วนของเกณฑ์การประเมินความเสี่ยง -->
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
            
            <!-- รายละเอียดเกณฑ์การประเมิน (แสดงเมื่อกดปุ่ม) -->
            <div v-if="showCriteriaSection" class="space-y-4 mt-2 animate-in fade-in-50 duration-300">
              <!-- 1. เกณฑ์โอกาสเกิด (Likelihood) -->
              <div class="border rounded-md p-4 bg-muted/30">
                <h3 class="font-medium mb-2">เกณฑ์โอกาสเกิด (Likelihood)</h3>
                <div class="space-y-3">
                  <div v-for="(criteria, index) in form.likelihood_criteria" :key="`likelihood-${index}`" class="grid gap-2">
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
              
              <!-- 2. เกณฑ์ผลกระทบ (Impact) -->
              <div class="border rounded-md p-4 bg-muted/30">
                <h3 class="font-medium mb-2">เกณฑ์ผลกระทบ (Impact)</h3>
                <div class="space-y-3">
                  <div v-for="(criteria, index) in form.impact_criteria" :key="`impact-${index}`" class="grid gap-2">
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
            
            <!-- คำแนะนำสำหรับเกณฑ์การประเมิน -->
            <div v-if="!showCriteriaSection" class="text-xs text-muted-foreground">
              คลิก "แสดงรายละเอียด" เพื่อกำหนดเกณฑ์การประเมินความเสี่ยงทั้ง 4 ระดับ
            </div>
          </div>

          <!-- ส่วนของเอกสารแนบ -->
          <div class="grid gap-2">
            <Label>เอกสารแนบ</Label>
            
            <!-- แสดงเอกสารแนบที่มีอยู่แล้ว (กรณีแก้ไข) -->
            <div v-if="existingAttachments.length > 0" class="mb-3">
              <p class="text-sm font-medium text-gray-700 mb-2">เอกสารแนบปัจจุบัน:</p>
              <ul class="space-y-2">
                <li 
                  v-for="attachment in existingAttachments" 
                  :key="attachment.id" 
                  class="flex flex-wrap items-center justify-between p-2 bg-gray-50 rounded-md text-sm border border-gray-200"
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

        <!-- ส่วนของปุ่มดำเนินการ -->
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
            <span>{{ form.processing ? 'กำลังบันทึก...' : (isEditing ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูล') }}</span>
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
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
