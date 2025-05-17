<!-- 
  ไฟล์: resources/js/pages/risk_assessment/RiskAssessmentModal.vue
  คำอธิบาย: Modal component สำหรับเพิ่ม/แก้ไขข้อมูลการประเมินความเสี่ยง
  ใช้: แสดงฟอร์มสำหรับกรอกข้อมูลการประเมินความเสี่ยง และจัดการเอกสารแนบ
  การทำงาน: รองรับการแสดงระดับความเสี่ยงจากฐานข้อมูลและคำนวณคะแนนอัตโนมัติ
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

// สถานะสำหรับแสดง tooltip ช่วยเหลือ (ตำแหน่งเดียวกับที่ใช้จริง)
const showTooltip = ref<string | null>(null)

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

/**
 * ระดับความเสี่ยงและการแสดงผล
 * กำหนดระดับความเสี่ยงตามคะแนน พร้อมสีและคำอธิบาย
 */
const getRiskLevelDisplay = computed(() => {
  const score = form.risk_score || 0;
  if (score <= 3) return { text: 'ต่ำ', color: 'bg-green-100 text-green-800' };
  if (score <= 6) return { text: 'ปานกลาง', color: 'bg-yellow-100 text-yellow-800' };
  if (score <= 9) return { text: 'สูง', color: 'bg-orange-100 text-orange-800' };
  return { text: 'สูงมาก', color: 'bg-red-100 text-red-800' };
})

/**
 * ความเสี่ยงระดับฝ่ายที่เลือก
 * สำหรับเข้าถึงข้อมูลเกณฑ์
 */
const selectedDivisionRisk = computed(() => {
  if (!form.division_risk_id) return null;
  return props.divisionRisks?.find(risk => risk.id === form.division_risk_id);
})

/**
 * เกณฑ์การให้คะแนนโอกาสเกิด
 * ดึงจากฐานข้อมูลหรือใช้ค่าเริ่มต้น
 */
const likelihoodCriteria = computed(() => {
  // ตรวจสอบทั้ง camelCase และ snake_case ในข้อมูลจาก API
  return selectedDivisionRisk.value?.likelihoodCriteria || 
         selectedDivisionRisk.value?.likelihood_criteria || 
         [];
})

/**
 * เกณฑ์การให้คะแนนผลกระทบ
 * ดึงจากฐานข้อมูลหรือใช้ค่าเริ่มต้น
 */
const impactCriteria = computed(() => {
  // ตรวจสอบทั้ง camelCase และ snake_case ในข้อมูลจาก API
  return selectedDivisionRisk.value?.impactCriteria || 
         selectedDivisionRisk.value?.impact_criteria || 
         [];
})

/**
 * ดึงชื่อระดับความเสี่ยงจากเกณฑ์ในฐานข้อมูล
 * @param level ระดับความเสี่ยง (1-4)
 * @param criteriaList รายการเกณฑ์จากฐานข้อมูล
 * @returns ชื่อระดับความเสี่ยงที่มาจากฐานข้อมูล หรือค่าเริ่มต้น
 */
function getCriteriaName(level: number, criteriaList: any[]): string {
  // แปลงระดับเป็นตัวเลขเพื่อเปรียบเทียบค่าได้ถูกต้อง
  const numericLevel = Number(level);
  
  // ค้นหาเกณฑ์ที่ตรงกับระดับที่ต้องการ
  const criteria = criteriaList.find(c => Number(c.level) === numericLevel);
  
  // กรณีพบเกณฑ์ในฐานข้อมูล ใช้ชื่อจากฐานข้อมูล
  if (criteria) {
    return criteria.name;
  }
  
  // กรณีไม่พบ ใช้ค่าเริ่มต้น
  return getDefaultLevelName(level);
}

/**
 * ชื่อระดับโอกาสเกิดตามค่าที่เลือก
 */
function getLikelihoodLevelName(level: number): string {
  return getCriteriaName(level, likelihoodCriteria.value);
}

/**
 * ชื่อระดับผลกระทบตามค่าที่เลือก
 */
function getImpactLevelName(level: number): string {
  return getCriteriaName(level, impactCriteria.value);
}

/**
 * ชื่อระดับความเสี่ยงเริ่มต้น (กรณีไม่พบในฐานข้อมูล)
 */
function getDefaultLevelName(level: number): string {
  switch (Number(level)) {
    case 1: return 'น้อยมาก';
    case 2: return 'น้อย';
    case 3: return 'ปานกลาง';
    case 4: return 'สูง';
    default: return 'ไม่ระบุ';
  }
}

// โหลดข้อมูลเมื่อ Modal เปิด
watch(() => props.show, (newVal) => {
  if (newVal && props.assessment) {
    // โหลดข้อมูลสำหรับการแก้ไข
    
    // แปลงรูปแบบวันที่ให้เป็น YYYY-MM-DD เสมอ
    const dateObj = new Date(props.assessment.assessment_date)
    const formattedDate = dateObj.toISOString().split('T')[0]
    
    // บันทึกข้อมูลเพื่อตรวจสอบการแปลงวันที่
    console.log('วันที่ที่ได้รับ:', props.assessment.assessment_date)
    console.log('วันที่หลังแปลงรูปแบบ:', formattedDate)
    
    // กำหนดค่าให้ฟอร์ม
    form.assessment_date = formattedDate
    form.likelihood_level = props.assessment.likelihood_level
    form.impact_level = props.assessment.impact_level
    form.risk_score = props.assessment.risk_score
    form.division_risk_id = props.assessment.division_risk_id
    form.notes = props.assessment.notes ?? ''
    loadAttachments(props.assessment)
  } else if (newVal) {
    // รีเซ็ตฟอร์มสำหรับการเพิ่มใหม่
    form.reset()
    form.assessment_date = new Date().toISOString().split('T')[0]
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
  if (!form.assessment_date) {
    errors.push('กรุณาระบุวันที่ประเมิน')
    isValid = false
  }
  
  if (!form.division_risk_id) {
    errors.push('กรุณาเลือกความเสี่ยงระดับฝ่าย')
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
                @click="toggleTooltip('assessment_date')"
              >
                <HelpCircleIcon class="h-4 w-4" />
              </Button>
            </Label>
            
            <!-- ข้อความช่วยเหลือ - แสดงเฉพาะเมื่อคลิกปุ่มช่วยเหลือ -->
            <div v-if="showTooltip === 'assessment_date'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
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
            <Label for="division_risk_id" class="flex items-center gap-1">
              ความเสี่ยงฝ่ายที่ประเมิน
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
              เลือกความเสี่ยงระดับฝ่ายที่ต้องการประเมิน ซึ่งจะมีเกณฑ์เฉพาะของแต่ละความเสี่ยง
            </div>
            
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
              <Label for="likelihood_level" class="flex items-center gap-1">
                ระดับโอกาสเกิด
                <span class="text-sm text-gray-500">(1-4)</span>
                <Button 
                  type="button" 
                  variant="ghost" 
                  size="icon"
                  class="h-5 w-5 text-gray-500 hover:text-gray-700"
                  @click="toggleTooltip('likelihood')"
                >
                  <HelpCircleIcon class="h-4 w-4" />
                </Button>
              </Label>
              
              <!-- คำอธิบายเกณฑ์โอกาสเกิด -->
              <div v-if="showTooltip === 'likelihood'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
                <div v-if="likelihoodCriteria.length > 0">
                  <div v-for="criteria in likelihoodCriteria" :key="'l'+criteria.id" class="mb-1">
                    <strong>{{ criteria.level }} - {{ criteria.name }}:</strong> {{ criteria.description || 'ไม่มีคำอธิบาย' }}
                  </div>
                </div>
                <div v-else>
                  <div>1 - น้อยมาก: โอกาสเกิดเหตุการณ์น้อยมาก</div>
                  <div>2 - น้อย: โอกาสเกิดเหตุการณ์น้อย</div>
                  <div>3 - ปานกลาง: โอกาสเกิดเหตุการณ์ปานกลาง</div>
                  <div>4 - สูง: โอกาสเกิดเหตุการณ์สูง</div>
                </div>
              </div>
                
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
                  <span>1</span>
                  <span>2</span>
                  <span>3</span>
                  <span>4</span>
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
              <Label for="impact_level" class="flex items-center gap-1">
                ระดับผลกระทบ
                <span class="text-sm text-gray-500">(1-4)</span>
                <Button 
                  type="button" 
                  variant="ghost" 
                  size="icon"
                  class="h-5 w-5 text-gray-500 hover:text-gray-700"
                  @click="toggleTooltip('impact')"
                >
                  <HelpCircleIcon class="h-4 w-4" />
                </Button>
              </Label>
              
              <!-- คำอธิบายเกณฑ์ผลกระทบ -->
              <div v-if="showTooltip === 'impact'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
                <div v-if="impactCriteria.length > 0">
                  <div v-for="criteria in impactCriteria" :key="'i'+criteria.id" class="mb-1">
                    <strong>{{ criteria.level }} - {{ criteria.name }}:</strong> {{ criteria.description || 'ไม่มีคำอธิบาย' }}
                  </div>
                </div>
                <div v-else>
                  <div>1 - น้อยมาก: ผลกระทบต่อองค์กรน้อยมาก</div>
                  <div>2 - น้อย: ผลกระทบต่อองค์กรน้อย</div>
                  <div>3 - ปานกลาง: ผลกระทบต่อองค์กรปานกลาง</div>
                  <div>4 - สูง: ผลกระทบต่อองค์กรสูง</div>
                </div>
              </div>
              
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
                  <span>1</span>
                  <span>2</span>
                  <span>3</span>
                  <span>4</span>
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
            <Label for="risk_score" class="flex items-center gap-1">
              คะแนนความเสี่ยง
              <Button 
                type="button" 
                variant="ghost" 
                size="icon"
                class="h-5 w-5 text-gray-500 hover:text-gray-700"
                @click="toggleTooltip('risk_score')"
              >
                <HelpCircleIcon class="h-4 w-4" />
              </Button>
            </Label>
            
            <!-- คำอธิบายคะแนนความเสี่ยง -->
            <div v-if="showTooltip === 'risk_score'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
              คะแนนความเสี่ยงคำนวณจาก ระดับโอกาสเกิด × ระดับผลกระทบ แบ่งเป็น 4 ระดับ:<br>
              1-3: ความเสี่ยงต่ำ | 4-6: ความเสี่ยงปานกลาง | 7-9: ความเสี่ยงสูง | 10-16: ความเสี่ยงสูงมาก
            </div>
            
            <!-- แสดงคะแนนและระดับความเสี่ยง -->
            <div class="flex gap-2 items-center">
              <!-- แสดงคะแนน -->
              <div class="flex-1 border rounded-md px-3 py-2 bg-muted/50">
                {{ form.risk_score }}
              </div>
              
              <!-- แสดงระดับความเสี่ยงด้วยแถบสี -->
              <div 
                :class="[
                  'px-3 py-2 rounded-md text-sm font-medium',
                  getRiskLevelDisplay.color
                ]"
              >
                {{ getRiskLevelDisplay.text }}
              </div>
            </div>
          </div>

          <!-- บันทึกเพิ่มเติม (Notes) -->
          <div class="grid gap-2">
            <Label for="notes" class="flex items-center gap-1">
              บันทึกเพิ่มเติม
              <Button 
                type="button" 
                variant="ghost" 
                size="icon"
                class="h-5 w-5 text-gray-500 hover:text-gray-700"
                @click="toggleTooltip('notes')"
              >
                <HelpCircleIcon class="h-4 w-4" />
              </Button>
            </Label>
            
            <!-- คำอธิบายสำหรับช่องบันทึกเพิ่มเติม -->
            <div v-if="showTooltip === 'notes'" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
              ระบุรายละเอียดการประเมินหรือข้อมูลเพิ่มเติมที่เกี่ยวข้องกับการประเมินความเสี่ยงครั้งนี้
            </div>
            
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
              แนบไฟล์เอกสารที่เกี่ยวข้องกับการประเมินความเสี่ยง เช่น รายงาน หลักฐาน หรือเอกสารอ้างอิงอื่นๆ
            </div>
            
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
