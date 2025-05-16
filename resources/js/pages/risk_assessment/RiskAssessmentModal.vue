<!-- 
  ไฟล์: resources/js/pages/risk_assessment/RiskAssessmentModal.vue
  คำอธิบาย: Modal component สำหรับเพิ่ม/แก้ไขข้อมูลการประเมินความเสี่ยง
  ทำหน้าที่: แสดงฟอร์มสำหรับกรอกข้อมูลการประเมินความเสี่ยง, อัปโหลดเอกสารแนบ
  หลักการ: ใช้ Dialog จาก shadcn-vue เป็นพื้นฐาน, แสดงฟอร์มและการจัดการเอกสารแนบ
  ใช้ร่วมกับ: RiskAssessmentController.php ในฝั่ง Backend
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
import type { RiskAssessment, DivisionRisk } from '@/types/types'
import { useRiskAssessmentData } from '@/composables/useRiskAssessmentData'

// กำหนด Types สำหรับฟอร์ม
type AssessmentFormData = {
  assessment_date: string;
  likelihood_level: number;
  impact_level: number;
  risk_score?: number;
  division_risk_id?: number | null;
  notes?: string;
  attachments: File[] | null;
}

// กำหนด props และ events
const props = defineProps<{
  show: boolean; // สถานะแสดง/ซ่อน Modal
  assessment?: RiskAssessment; // ข้อมูลการประเมินความเสี่ยงสำหรับการแก้ไข
  initialAssessments?: RiskAssessment[]; // ข้อมูลการประเมินความเสี่ยงทั้งหมด
  divisionRisks?: DivisionRisk[]; // ข้อมูลความเสี่ยงระดับฝ่ายทั้งหมด
}>()

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
} = useRiskAssessmentData(props.initialAssessments, props.show)

// สถานะสำหรับแสดง tooltip ช่วยเหลือ
const showHelp = ref<boolean>(false)

// Computed Properties
const isEditing = computed(() => !!props.assessment?.id)
const modalTitle = computed(() => isEditing.value ? 'แก้ไขการประเมินความเสี่ยง' : 'เพิ่มการประเมินความเสี่ยง')

// คำนวณคะแนนความเสี่ยงอัตโนมัติ
const calculatedRiskScore = computed(() => {
  if (!form.likelihood_level || !form.impact_level) return 0;
  return form.likelihood_level * form.impact_level;
})

// สร้างฟอร์มด้วย Inertia useForm
const form = useForm<AssessmentFormData>({
  assessment_date: props.assessment?.assessment_date ?? new Date().toISOString().split('T')[0],
  likelihood_level: props.assessment?.likelihood_level ?? 1,
  impact_level: props.assessment?.impact_level ?? 1,
  risk_score: props.assessment?.risk_score ?? 1,
  division_risk_id: props.assessment?.division_risk_id ?? null,
  notes: props.assessment?.notes ?? '',
  attachments: null,
})

// Watchers
watch(() => props.show, (newVal) => {
  if (newVal && props.assessment) {
    // โหลดข้อมูลสำหรับการแก้ไข
    console.log('กำลังโหลดข้อมูลสำหรับแก้ไข:', new Date(props.assessment.assessment_date).toLocaleDateString('th-TH'))
    form.assessment_date = props.assessment.assessment_date
    form.likelihood_level = props.assessment.likelihood_level
    form.impact_level = props.assessment.impact_level
    form.risk_score = props.assessment.risk_score
    form.division_risk_id = props.assessment.division_risk_id
    form.notes = props.assessment.notes ?? ''
    loadAttachments(props.assessment)
  } else if (newVal) {
    form.reset()
    form.assessment_date = new Date().toISOString().split('T')[0] // กำหนดวันที่เป็นวันปัจจุบันสำหรับการเพิ่มใหม่
    loadAttachments()
  }
})

// อัปเดตคะแนนความเสี่ยงอัตโนมัติเมื่อมีการเปลี่ยนค่า likelihood หรือ impact
watch([() => form.likelihood_level, () => form.impact_level], () => {
  form.risk_score = calculatedRiskScore.value
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
  if (!form.assessment_date) {
    errors.push('กรุณาระบุวันที่ประเมิน')
    isValid = false
  }
  
  if (!form.likelihood_level || form.likelihood_level < 1 || form.likelihood_level > 4) {
    errors.push('กรุณาระบุระดับโอกาสเกิดที่ถูกต้อง (1-4)')
    isValid = false
  }
  
  if (!form.impact_level || form.impact_level < 1 || form.impact_level > 4) {
    errors.push('กรุณาระบุระดับผลกระทบที่ถูกต้อง (1-4)')
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
      id: 'saving-assessment',
      duration: 60000 // ตั้งเวลานานพอที่จะรอการประมวลผลเสร็จ
    })
    
    console.log('กำลังส่งข้อมูล, mode:', isEditing.value ? 'แก้ไข' : 'เพิ่ม', 'id:', props.assessment?.id)
    
    await submitForm(
      { 
        assessment_date: form.assessment_date,
        likelihood_level: form.likelihood_level,
        impact_level: form.impact_level,
        risk_score: form.risk_score,
        division_risk_id: form.division_risk_id,
        notes: form.notes
      }, 
      isEditing.value ? props.assessment?.id : undefined,
      closeModal
    )
    
    // เปลี่ยน toast เป็นแจ้งเตือนสำเร็จ
    toast.success('บันทึกข้อมูลเรียบร้อย', {
      id: 'saving-assessment'
    })
    
    emit('saved')
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล:', error)
    
    // เปลี่ยน toast เป็นแจ้งเตือนข้อผิดพลาด
    toast.error('ไม่สามารถบันทึกข้อมูลได้', {
      id: 'saving-assessment',
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

// ฟังก์ชันสำหรับแปลงค่าระดับโอกาสเป็นข้อความ
const getLikelihoodLevelName = (level: number): string => {
  switch (level) {
    case 1: return 'น้อยมาก';
    case 2: return 'น้อย';
    case 3: return 'ปานกลาง';
    case 4: return 'สูง';
    default: return 'ไม่ระบุ';
  }
}

// ฟังก์ชันสำหรับแปลงค่าระดับผลกระทบเป็นข้อความ
const getImpactLevelName = (level: number): string => {
  switch (level) {
    case 1: return 'น้อยมาก';
    case 2: return 'น้อย';
    case 3: return 'ปานกลาง';
    case 4: return 'สูง';
    default: return 'ไม่ระบุ';
  }
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
        <DialogDescription class="sr-only">กรอกข้อมูลการประเมินความเสี่ยง</DialogDescription>
      </DialogHeader>

      <!-- แบบฟอร์มกรอกข้อมูล -->
      <form @submit.prevent="handleSubmit" class="space-y-4 mt-4">
        <div class="grid gap-4 py-2">
          <!-- ฟิลด์วันที่ประเมิน -->
          <div class="grid gap-2">
            <Label for="assessment_date" class="flex items-center gap-1">
              วันที่ประเมิน
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
              ระบุวันที่ทำการประเมินความเสี่ยงนี้ เพื่อติดตามการเปลี่ยนแปลงของระดับความเสี่ยงตามระยะเวลา
            </div>
            
            <Input 
              id="assessment_date" 
              v-model="form.assessment_date" 
              type="date"
            />
            <p v-if="form.errors.assessment_date" class="text-sm text-red-500">
              {{ form.errors.assessment_date }}
            </p>
          </div>

          <!-- ฟิลด์ความเสี่ยงฝ่ายที่เกี่ยวข้อง -->
          <div class="grid gap-2">
            <Label for="division_risk_id">ความเสี่ยงฝ่ายที่ประเมิน</Label>
            <select
              id="division_risk_id"
              v-model="form.division_risk_id"
              class="rounded-md border border-input bg-background px-3 py-2"
            >
              <option :value="null">-- เลือกความเสี่ยงฝ่าย --</option>
              <option
                v-for="risk in props.divisionRisks || []"
                :key="risk.id"
                :value="risk.id"
              >
                {{ risk.risk_name }}
              </option>
            </select>
            <p v-if="form.errors.division_risk_id" class="text-sm text-red-500">
              {{ form.errors.division_risk_id }}
            </p>
          </div>

          <!-- การประเมินความเสี่ยง - แสดงเป็น Grid 2 คอลัมน์ -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- โอกาสเกิด (Likelihood) -->
            <div class="grid gap-2">
              <Label for="likelihood_level">
              ระดับโอกาสเกิด
              <span class="text-sm text-gray-500">(1-4)</span>
            </Label>
            <div>
              <input
                type="range"
                min="1"
                max="4"
                step="1"
                v-model="form.likelihood_level"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
              />
              <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>1 - น้อยมาก</span>
                <span>2 - น้อย</span>
                <span>3 - ปานกลาง</span>
                <span>4 - สูง</span>
              </div>
              <div class="text-center mt-2 text-sm font-medium">
                {{ getLikelihoodLevelName(form.likelihood_level) }} ({{ form.likelihood_level }})
              </div>
            </div>
            <p v-if="form.errors.likelihood_level" class="text-sm text-red-500">
              {{ form.errors.likelihood_level }}
            </p>
          </div>

          <!-- ฟิลด์ระดับผลกระทบ (Impact) -->
          <div class="grid gap-2">
            <Label for="impact_level">
              ระดับผลกระทบ
              <span class="text-sm text-gray-500">(1-4)</span>
            </Label>
            <div>
              <input
                type="range"
                min="1"
                max="4"
                step="1"
                v-model="form.impact_level"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
              />
              <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>1 - น้อยมาก</span>
                <span>2 - น้อย</span>
                <span>3 - ปานกลาง</span>
                <span>4 - สูง</span>
              </div>
              <div class="text-center mt-2 text-sm font-medium">
                {{ getImpactLevelName(form.impact_level) }} ({{ form.impact_level }})
              </div>
            </div>
            <p v-if="form.errors.impact_level" class="text-sm text-red-500">
              {{ form.errors.impact_level }}
            </p>
          </div>
        </div>

        <!-- แสดงคะแนนความเสี่ยง (Risk Score) ที่คำนวณอัตโนมัติ -->
        <div class="grid gap-2">
          <Label for="risk_score">คะแนนความเสี่ยง (คำนวณอัตโนมัติ)</Label>
          <div class="flex gap-2 items-center">
            <Input id="risk_score" v-model="form.risk_score" type="text" readonly />
            <div 
              :class="{
                'px-3 py-1 rounded-full text-xs inline-flex items-center': true,
                'bg-green-100 text-green-800': form.risk_score <= 3,
                'bg-yellow-100 text-yellow-800': form.risk_score > 3 && form.risk_score <= 6,
                'bg-orange-100 text-orange-800': form.risk_score > 6 && form.risk_score <= 9,
                'bg-red-100 text-red-800': form.risk_score > 9,
              }"
            >
              {{ form.risk_score <= 3 ? 'ต่ำ' : form.risk_score <= 6 ? 'ปานกลาง' : form.risk_score <= 9 ? 'สูง' : 'สูงมาก' }}
            </div>
          </div>
          <p v-if="form.errors.risk_score" class="text-sm text-red-500">
            {{ form.errors.risk_score }}
          </p>
        </div>

        <!-- บันทึกเพิ่มเติม (Notes) -->
        <div class="grid gap-2">
          <Label for="notes">บันทึกเพิ่มเติม</Label>
          <Textarea
            id="notes"
            v-model="form.notes"
            placeholder="ระบุบันทึกเพิ่มเติมหรือรายละเอียดของการประเมินความเสี่ยง..."
            rows="3"
          />
          <p v-if="form.errors.notes" class="text-sm text-red-500">
            {{ form.errors.notes }}
          </p>
        </div>

        <!-- ส่วนเอกสารแนบ -->
        <div class="grid gap-2">
          <Label>เอกสารแนบ</Label>
          
          <!-- ปุ่มอัปโหลดไฟล์ -->
          <div class="flex flex-col gap-2">
            <div
              class="border border-dashed border-gray-300 rounded-md p-4 flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-primary"
              @click="$refs.fileInput.click()"
            >
              <UploadIcon class="h-6 w-6 text-gray-400" />
              <div class="text-sm text-center">
                <span class="font-medium text-primary">อัปโหลดไฟล์</span>
                <p class="text-gray-500 text-xs mt-1">
                  รองรับ PDF, Word, Excel และรูปภาพ (สูงสุด 10MB)
                </p>
              </div>
            </div>
            <input
              ref="fileInput"
              type="file"
              multiple
              class="hidden"
              @change="handleFileUpload"
              accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
            />
          </div>

          <!-- แสดงรายการไฟล์ที่เลือก -->
          <div v-if="selectedFiles.length > 0" class="mt-2">
            <div class="text-sm font-medium mb-2">ไฟล์ที่เลือก ({{ selectedFiles.length }} ไฟล์)</div>
            <ul class="space-y-2">
              <li
                v-for="(file, index) in selectedFiles"
                :key="`new-${index}`"
                class="flex justify-between items-center p-2 bg-gray-50 rounded-md"
              >
                <div class="flex items-center gap-2 overflow-hidden">
                  <component :is="getFileIcon(file.name)" class="h-4 w-4 text-gray-500" />
                  <span class="text-sm truncate">{{ file.name }}</span>
                  <span class="text-xs text-gray-500">({{ formatFileSize(file.size) }})</span>
                </div>
                <Button
                  variant="ghost"
                  size="icon"
                  class="h-6 w-6 hover:bg-gray-200"
                  @click="removeSelectedFile(index)"
                >
                  <XCircleIcon class="h-4 w-4 text-gray-500" />
                </Button>
              </li>
            </ul>
          </div>

          <!-- แสดงรายการไฟล์ที่มีอยู่แล้ว -->
          <div v-if="existingAttachments.length > 0" class="mt-2">
            <div class="text-sm font-medium mb-2">เอกสารแนบที่มีอยู่ ({{ existingAttachments.length }} ไฟล์)</div>
            <ul class="space-y-2">
              <li
                v-for="attachment in existingAttachments"
                :key="`existing-${attachment.id}`"
                class="flex justify-between items-center p-2 bg-gray-50 rounded-md"
              >
                <div class="flex items-center gap-2 overflow-hidden cursor-pointer" @click="openAttachment(attachment.url)">
                  <component :is="getFileIcon(attachment.file_name)" class="h-4 w-4 text-gray-500" />
                  <span class="text-sm truncate">{{ attachment.file_name }}</span>
                  <span class="text-xs text-gray-500">({{ formatFileSize(attachment.file_size) }})</span>
                </div>
                <Button
                  variant="ghost"
                  size="icon"
                  class="h-6 w-6 hover:bg-gray-200"
                  @click="markAttachmentForDeletion(attachment.id)"
                >
                  <Trash2Icon class="h-4 w-4 text-gray-500" />
                </Button>
              </li>
            </ul>
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
            :disabled="form.processing"
            :class="{'opacity-50 cursor-not-allowed': form.processing}"
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