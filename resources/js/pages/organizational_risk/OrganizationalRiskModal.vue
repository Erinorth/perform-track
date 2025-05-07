<!-- 
  ไฟล์: resources/js/pages/organizational_risk/OrganizationalRiskModal.vue
  คำอธิบาย: Modal component สำหรับเพิ่ม/แก้ไขข้อมูลความเสี่ยงระดับองค์กร
  ทำหน้าที่: แสดงฟอร์มสำหรับกรอกข้อมูลความเสี่ยง, อัปโหลดเอกสารแนบ
  หลักการ: ใช้ Dialog จาก shadcn-vue เป็นพื้นฐาน, แสดงฟอร์มและการจัดการเอกสารแนบ
  ใช้ร่วมกับ: OrganizationalRiskController.php ในฝั่ง Backend
-->

<script setup lang="ts">
// นำเข้าไลบรารีและคอมโพเนนต์ที่จำเป็น
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { toast } from 'vue-sonner'
import { SaveIcon, XIcon, UploadIcon, XCircleIcon, InfoIcon, Trash2Icon } from 'lucide-vue-next'

// นำเข้า types และ composable functions
import type { OrganizationalRisk } from '@/types/types'
import { useOrganizationalRiskData } from '@/composables/useOrganizationalRiskData'

// กำหนด type สำหรับฟอร์ม
type RiskFormData = {
  risk_name: string;
  description: string;
  attachments: File[] | null;
}

// กำหนด props ที่รับจาก parent component
const props = defineProps<{
  show: boolean; // สถานะแสดง/ซ่อน Modal
  risk?: OrganizationalRisk; // ข้อมูลความเสี่ยงสำหรับการแก้ไข (optional)
  initialRisks?: OrganizationalRisk[]; // ข้อมูลความเสี่ยงทั้งหมด (optional)
}>()

// กำหนด events ที่ส่งไปยัง parent component
const emit = defineEmits<{
  (e: 'update:show', value: boolean): void; // event เมื่อสถานะ Modal เปลี่ยน
  (e: 'saved'): void; // event เมื่อบันทึกข้อมูลสำเร็จ
}>()

// ใช้ composable function เพื่อจัดการข้อมูลและการทำงานกับ backend
// ส่ง props.show เป็น trigger เพื่อให้เมื่อ Modal เปิด ให้โหลดข้อมูลใหม่
const { 
  existingAttachments, selectedFiles, fileNames, 
  loadAttachments, submitForm, addSelectedFiles, removeSelectedFile, 
  markAttachmentForDeletion, openAttachment, validateFiles,
  // ส่วนของ UI helpers
  getFileIcon, formatFileSize 
} = useOrganizationalRiskData(props.initialRisks, props.show)

// คำนวณค่าต่างๆ จาก props
const isEditing = computed(() => !!props.risk?.id)
const modalTitle = computed(() => isEditing.value ? 'แก้ไขความเสี่ยงระดับองค์กร' : 'เพิ่มความเสี่ยงระดับองค์กร')

// สร้าง form object ด้วย Inertia useForm
const form = useForm<RiskFormData>({
  risk_name: props.risk?.risk_name ?? '',
  description: props.risk?.description ?? '',
  attachments: null,
})

// สังเกตการเปลี่ยนแปลงของ Modal และดำเนินการตามความเหมาะสม
watch(() => props.show, (newVal) => {
  if (newVal && props.risk) {
    // กรณีเปิด Modal สำหรับแก้ไขข้อมูล
    console.log('กำลังโหลดข้อมูลสำหรับแก้ไข:', props.risk.risk_name)
    form.risk_name = props.risk.risk_name
    form.description = props.risk.description
    loadAttachments(props.risk) // โหลดเอกสารแนบจาก composable
  } else if (newVal) {
    // กรณีเปิด Modal สำหรับเพิ่มข้อมูล
    form.reset()
    loadAttachments() // รีเซ็ตข้อมูลเอกสารแนบใน composable
  }
})

// ฟังก์ชันปิด Modal
const closeModal = () => {
  emit('update:show', false)
}

// ฟังก์ชันจัดการไฟล์ที่อัปโหลด
const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement
  // ส่งไฟล์ไปยัง composable เพื่อจัดการ
  addSelectedFiles(input.files)
  
  // สำหรับ Inertia validation errors
  if (input.files && input.files.length > 0) {
    form.attachments = Array.from(input.files)
  }
  // รีเซ็ต input
  input.value = ''
}

// ตรวจสอบความถูกต้องของฟอร์ม
const validateForm = () => {
  let isValid = true
  const errors: string[] = []
  
  // ตรวจสอบชื่อความเสี่ยง
  if (!form.risk_name.trim()) {
    errors.push('กรุณาระบุชื่อความเสี่ยง')
    isValid = false
  }
  
  // ตรวจสอบรายละเอียด
  if (!form.description.trim()) {
    errors.push('กรุณาระบุรายละเอียดความเสี่ยง')
    isValid = false
  }
  
  // ตรวจสอบไฟล์แนบด้วย composable (ถ้ามี)
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
    return isValid
  }
  
  return isValid
}

// ฟังก์ชันส่งข้อมูลไปยัง backend
const handleSubmit = async () => {
  // ตรวจสอบความถูกต้องของฟอร์มก่อนส่ง
  if (!validateForm()) return
  
  try {
    console.log('กำลังส่งข้อมูลไปยัง backend, mode:', isEditing.value ? 'แก้ไข' : 'เพิ่ม', 'id:', props.risk?.id)
    
    // ใช้ composable function ในการส่งข้อมูล
    await submitForm(
      { risk_name: form.risk_name, description: form.description }, 
      isEditing.value ? props.risk?.id : undefined,
      closeModal
    )
    
    // แจ้ง parent component ว่าบันทึกสำเร็จ
    emit('saved')
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล:', error)
    toast.error('ไม่สามารถบันทึกข้อมูลได้', {
      description: 'กรุณาลองใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ'
    })
  }
}
</script>

<template>
  <!-- Dialog component จาก shadcn-vue ใช้เป็น Modal -->
  <Dialog 
    :open="show" 
    @update:open="(val) => emit('update:show', val)"
  >
    <DialogContent class="sm:max-w-[550px]">
      <!-- ส่วนหัวของ Modal -->
      <DialogHeader>
        <DialogTitle>{{ modalTitle }}</DialogTitle>
        <DialogDescription class="sr-only">กรอกข้อมูลความเสี่ยงระดับองค์กร</DialogDescription>
      </DialogHeader>

      <!-- ส่วนของฟอร์ม -->
      <form @submit.prevent="handleSubmit" class="space-y-4 mt-4">
        <div class="grid gap-4 py-2">
          <!-- ฟิลด์ชื่อความเสี่ยง -->
          <div class="grid gap-2">
            <Label for="risk_name">ชื่อความเสี่ยง</Label>
            <Input 
              id="risk_name" 
              v-model="form.risk_name" 
              placeholder="ระบุชื่อความเสี่ยงระดับองค์กร"
            />
            <!-- แสดงข้อความ error จาก validation -->
            <p v-if="form.errors.risk_name" class="text-sm text-red-500">
              {{ form.errors.risk_name }}
            </p>
          </div>

          <!-- ฟิลด์รายละเอียด -->
          <div class="grid gap-2">
            <Label for="description">รายละเอียดความเสี่ยง</Label>
            <Textarea 
              id="description" 
              v-model="form.description" 
              placeholder="ระบุรายละเอียดความเสี่ยง" 
              :rows="4"
            />
            <!-- แสดงข้อความ error จาก validation -->
            <p v-if="form.errors.description" class="text-sm text-red-500">
              {{ form.errors.description }}
            </p>
          </div>

          <!-- ส่วนของเอกสารแนบ -->
          <div class="grid gap-2">
            <Label>เอกสารแนบ</Label>
            
            <!-- แสดงเอกสารแนบที่มีอยู่แล้ว (กรณีแก้ไข) -->
            <div v-if="existingAttachments.length > 0" class="mb-3">
              <p class="text-sm font-medium text-gray-700 mb-2">เอกสารแนบปัจจุบัน:</p>
              <ul class="space-y-2">
                <!-- วนลูปแสดงเอกสารแนบที่มีอยู่แล้ว -->
                <li 
                  v-for="attachment in existingAttachments" 
                  :key="attachment.id" 
                  class="flex items-center justify-between p-2 bg-gray-50 rounded-md text-sm border border-gray-200"
                >
                  <div 
                    class="flex items-center gap-2 flex-1 overflow-hidden" 
                    @click="openAttachment(attachment.url)" 
                    style="cursor: pointer"
                  >
                    <component :is="getFileIcon(attachment.file_name)" class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate">{{ attachment.file_name }}</span>
                    <span class="text-xs text-gray-500 flex-shrink-0">{{ formatFileSize(attachment.file_size || 0) }}</span>
                  </div>
                  
                  <!-- ปุ่มลบเอกสารแนบ -->
                  <Button 
                    type="button" 
                    variant="ghost" 
                    size="sm" 
                    @click="markAttachmentForDeletion(attachment.id)"
                    class="text-red-500 hover:text-red-700 hover:bg-red-50 ml-1 flex-shrink-0"
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
                <!-- วนลูปแสดงไฟล์ที่เพิ่งอัปโหลด -->
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
                  
                  <!-- ปุ่มลบไฟล์ที่เพิ่งอัปโหลด -->
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

            <!-- ปุ่มอัปโหลดไฟล์ -->
            <div class="flex flex-col">
              <div class="flex flex-wrap items-center gap-2">
                <!-- ปุ่มเลือกไฟล์ -->
                <label for="file-upload" class="flex items-center gap-2 cursor-pointer px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                  <UploadIcon class="h-4 w-4" />
                  <span>เลือกไฟล์แนบ</span>
                </label>
                
                <!-- Input สำหรับเลือกไฟล์ (ซ่อนไว้) -->
                <input 
                  id="file-upload" 
                  type="file" 
                  multiple
                  class="hidden" 
                  @change="handleFileUpload"
                  accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                />
                
                <!-- ข้อความแสดงประเภทไฟล์ที่รองรับ -->
                <p class="text-xs text-gray-500">รองรับไฟล์ประเภท PDF, Word, Excel, รูปภาพ (ขนาดไม่เกิน 10MB)</p>
              </div>
              
              <!-- แสดงข้อความ error จาก validation -->
              <p v-if="form.errors.attachments" class="text-sm text-red-500 mt-1">
                {{ form.errors.attachments }}
              </p>
            </div>
          </div>
        </div>

        <!-- ส่วนของปุ่มดำเนินการ -->
        <DialogFooter class="flex flex-col sm:flex-row items-center justify-end space-y-2 sm:space-y-0 sm:space-x-2">
          <!-- ปุ่มยกเลิก -->
          <Button
            type="button"
            variant="outline"
            @click="closeModal"
            class="w-full sm:w-auto flex items-center gap-2"
          >
            <XIcon class="h-4 w-4" />
            <span>ยกเลิก</span>
          </Button>
          
          <!-- ปุ่มบันทึก -->
          <Button
            type="submit"
            :disabled="form.processing"
            class="w-full sm:w-auto flex items-center gap-2"
          >
            <SaveIcon class="h-4 w-4" />
            <span>{{ isEditing ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูล' }}</span>
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
