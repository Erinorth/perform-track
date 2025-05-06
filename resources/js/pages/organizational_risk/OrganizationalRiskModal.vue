<!-- 
  ไฟล์: resources/js/pages/organizational_risk/OrganizationalRiskModal.vue
  คำอธิบาย: Modal component สำหรับเพิ่ม แก้ไข และจัดการเอกสารแนบของความเสี่ยงระดับองค์กร
  ใช้งานร่วมกับ: OrganizationalRiskController.php ในฝั่ง Backend
  ใช้งานเมื่อ: ผู้ใช้ต้องการเพิ่มหรือแก้ไขข้อมูลความเสี่ยงระดับองค์กร
-->

<script setup lang="ts">
// นำเข้า libraries และ components ที่จำเป็น
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { toast } from 'vue-sonner';
import { SaveIcon, XIcon, UploadIcon, XCircleIcon, InfoIcon, Trash2Icon } from 'lucide-vue-next';
import type { OrganizationalRisk } from '@/types/types';

// นำเข้า composable สำหรับจัดการข้อมูลความเสี่ยง
import { useOrganizationalRiskData } from '@/composables/useOrganizationalRiskData';

// สร้าง type สำหรับฟอร์ม
type RiskFormData = {
  risk_name: string;
  description: string;
  attachments: File[] | null;
}

// กำหนด props ที่รับจาก parent component
const props = defineProps<{
  show: boolean;                          // ควบคุมการแสดง/ซ่อน Modal
  risk?: OrganizationalRisk;              // ข้อมูลความเสี่ยงในกรณีที่เป็นการแก้ไข
  initialRisks?: OrganizationalRisk[];    // รายการความเสี่ยงทั้งหมดที่มีอยู่
}>();

// กำหนด events ที่จะส่งกลับไปยัง parent component
const emit = defineEmits<{
  (e: 'update:show', value: boolean): void;  // event สำหรับอัปเดตสถานะการแสดง Modal
  (e: 'saved'): void;                        // event แจ้งเมื่อบันทึกข้อมูลสำเร็จ
}>();

// เรียกใช้ composable เพื่อจัดการข้อมูลและการส่งข้อมูลไปยัง backend
// ส่ง props.show เป็น trigger เพื่อให้ตรวจจับการเปลี่ยนแปลง
const {
  // ข้อมูลเอกสารแนบ
  existingAttachments,
  selectedFiles,
  fileNames,
  
  // ฟังก์ชันจัดการข้อมูล
  loadAttachments,
  submitForm,
  
  // ฟังก์ชันจัดการเอกสารแนบ
  addSelectedFiles,
  removeSelectedFile,
  markAttachmentForDeletion,
  openAttachment,
  validateFiles,

  // ฟังก์ชันช่วยเหลือ UI
  getFileIcon,
  formatFileSize
} = useOrganizationalRiskData(props.initialRisks || [], props.show);

// สร้าง computed properties
const isEditing = computed(() => !!props.risk?.id);
const modalTitle = computed(() => isEditing.value ? 'แก้ไขความเสี่ยงองค์กร' : 'เพิ่มความเสี่ยงองค์กร');

// สร้าง form object สำหรับจัดการข้อมูลและการตรวจสอบความถูกต้อง
const form = useForm<RiskFormData>({
  risk_name: props.risk?.risk_name ?? '',
  description: props.risk?.description ?? '',
  attachments: null, // กำหนดค่าเริ่มต้นเป็น null
});

// ใช้ watch เพื่อรีเซ็ตฟอร์มเมื่อ Modal เปิดขึ้น
watch(() => props.show, (newVal) => {
  if (newVal && props.risk) {
    // กรณีโหมดแก้ไข
    console.log('🔄 โหลดข้อมูลสำหรับแก้ไข:', props.risk.risk_name);
    form.risk_name = props.risk.risk_name || '';
    form.description = props.risk.description || '';
    loadAttachments(props.risk);  // โหลดเอกสารแนบจาก composable
  } else if (newVal) {
    // กรณีโหมดเพิ่มใหม่
    form.reset();
    loadAttachments();  // รีเซ็ตข้อมูลเอกสารแนบ
  }
});

// ฟังก์ชันสำหรับปิด Modal
const closeModal = () => {
  emit('update:show', false);
};

// ฟังก์ชันสำหรับจัดการการอัปโหลดเอกสารแนบ
const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement;
  
  // ใช้ฟังก์ชันจาก composable เพื่อจัดการไฟล์
  addSelectedFiles(input.files);
  
  // อัปเดตฟิลด์ในฟอร์มด้วย เพื่อให้ Inertia จัดการ validation errors
  if (input.files && input.files.length > 0) {
    form.attachments = Array.from(input.files);
  }
  
  // รีเซ็ต input
  input.value = '';
};

// ฟังก์ชันตรวจสอบความถูกต้องของข้อมูลก่อนส่ง
const validateForm = () => {
  let isValid = true;
  const errors = [];

  // ตรวจสอบชื่อความเสี่ยง
  if (!form.risk_name.trim()) {
    errors.push('กรุณาระบุชื่อความเสี่ยง');
    isValid = false;
  }
  
  // ตรวจสอบรายละเอียดความเสี่ยง
  if (!form.description.trim()) {
    errors.push('กรุณาระบุรายละเอียดความเสี่ยง');
    isValid = false;
  }

  // ตรวจสอบไฟล์ที่เลือก (ใช้ฟังก์ชันจาก composable)
  if (selectedFiles.value.length > 0) {
    const fileValidation = validateFiles(selectedFiles.value);
    if (!fileValidation.valid) {
      isValid = false;
      errors.push(...fileValidation.errors);
    }
  }

  // แสดงข้อความแจ้งเตือนถ้ามีข้อผิดพลาด
  if (!isValid) {
    toast.warning('กรุณากรอกข้อมูลให้ครบถ้วน', {
      icon: InfoIcon,
      description: errors.join(', ')
    });
  }

  return isValid;
};

// ฟังก์ชันสำหรับส่งฟอร์มไปยัง backend
const handleSubmit = async () => {
  if (!validateForm()) return;
  
  try {
    console.log('📝 กำลังส่งข้อมูลไปยัง backend:', {
      mode: isEditing.value ? 'แก้ไข' : 'เพิ่มใหม่',
      id: props.risk?.id
    });

    await submitForm(
      {
        risk_name: form.risk_name,
        description: form.description,
      },
      isEditing.value ? props.risk?.id : undefined,
      () => {
        closeModal();
        emit('saved');  // แจ้ง parent component ว่าบันทึกสำเร็จแล้ว
      }
    );
  } catch (error) {
    console.error('❌ เกิดข้อผิดพลาดระหว่างการบันทึกข้อมูล:', error);
    toast.error('เกิดข้อผิดพลาด', {
      description: 'ไม่สามารถบันทึกข้อมูลได้ โปรดลองอีกครั้งในภายหลัง'
    });
  }
};
</script>

<template>
  <!-- Dialog component จาก shadcn-vue สำหรับแสดง Modal -->
  <Dialog :open="show" @update:open="(val) => emit('update:show', val)">
    <DialogContent class="sm:max-w-[550px]">
      <!-- ส่วนหัวของ Modal -->
      <DialogHeader>
        <DialogTitle>{{ modalTitle }}</DialogTitle>
        <DialogDescription class="sr-only">รายละเอียดฟอร์มสำหรับการจัดการความเสี่ยงองค์กร</DialogDescription>
      </DialogHeader>
      
      <!-- ฟอร์มสำหรับกรอกข้อมูล -->
      <form @submit.prevent="handleSubmit" class="space-y-4 mt-4">
        <div class="grid gap-4 py-2">
                    <!-- ฟิลด์สำหรับชื่อความเสี่ยง -->
                    <div class="grid gap-2">
            <Label for="risk_name">ชื่อความเสี่ยง</Label>
            <Input 
              id="risk_name" 
              v-model="form.risk_name" 
              placeholder="ระบุชื่อความเสี่ยงองค์กร"
            />
            <!-- แสดงข้อความแจ้งเตือนถ้ามีข้อผิดพลาด -->
            <p v-if="form.errors.risk_name" class="text-sm text-red-500">{{ form.errors.risk_name }}</p>
          </div>
          
          <!-- ฟิลด์สำหรับรายละเอียดความเสี่ยง -->
          <div class="grid gap-2">
            <Label for="description">รายละเอียด</Label>
            <Textarea 
              id="description" 
              v-model="form.description" 
              placeholder="รายละเอียดความเสี่ยง"
              rows="4"
            />
            <!-- แสดงข้อความแจ้งเตือนถ้ามีข้อผิดพลาด -->
            <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</p>
          </div>
          
          <!-- ส่วนจัดการเอกสารแนบ -->
          <div class="grid gap-2">
            <Label>เอกสารแนบ</Label>
            
            <!-- ส่วนแสดงเอกสารแนบที่มีอยู่แล้ว (กรณีแก้ไข) -->
            <div v-if="existingAttachments.length > 0" class="mb-3">
              <p class="text-sm font-medium text-gray-700 mb-2">เอกสารแนบปัจจุบัน:</p>
              <ul class="space-y-2">
                <!-- วนลูปแสดงรายการเอกสารแนบ -->
                <li v-for="attachment in existingAttachments" :key="attachment.id" 
                    class="flex items-center justify-between p-2 bg-gray-50 rounded-md text-sm">
                  <div class="flex items-center gap-2 flex-1 overflow-hidden" 
                       @click="openAttachment(attachment.url)" 
                       style="cursor: pointer">
                    <component :is="getFileIcon(attachment.file_name)" class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate">{{ attachment.file_name }}</span>
                    <span class="text-xs text-gray-500 flex-shrink-0">
                      {{ formatFileSize(attachment.file_size || 0) }}
                    </span>
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
            
            <!-- ส่วนแสดงไฟล์ที่เลือกแล้วแต่ยังไม่ได้อัปโหลด -->
            <div v-if="fileNames.length > 0" class="mb-3">
              <p class="text-sm font-medium text-gray-700 mb-2">ไฟล์ที่เลือกเพื่ออัปโหลด:</p>
              <ul class="space-y-2">
                <!-- วนลูปแสดงรายการไฟล์ที่เลือก -->
                <li v-for="(fileName, index) in fileNames" :key="index" 
                    class="flex items-center justify-between p-2 bg-gray-50 rounded-md text-sm">
                  <div class="flex items-center gap-2 flex-1 overflow-hidden">
                    <component :is="getFileIcon(fileName)" class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate">{{ fileName }}</span>
                    <span class="text-xs text-gray-500 flex-shrink-0">
                      {{ formatFileSize(selectedFiles[index].size) }}
                    </span>
                  </div>
                  <!-- ปุ่มยกเลิกการเลือกไฟล์ -->
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
            
            <!-- ส่วนอัปโหลดไฟล์ใหม่ -->
            <div class="flex flex-col">
              <div class="flex flex-wrap items-center gap-2">
                <!-- ปุ่มเลือกไฟล์ -->
                <label 
                  for="file-upload" 
                  class="flex items-center gap-2 cursor-pointer px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  <UploadIcon class="h-4 w-4" />
                  <span>เลือกไฟล์</span>
                </label>
                <!-- Input ซ่อนไว้สำหรับเลือกไฟล์ -->
                <input 
                  id="file-upload" 
                  type="file" 
                  multiple 
                  class="hidden" 
                  @change="handleFileUpload"
                  accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                />
                <!-- คำอธิบายประเภทไฟล์ที่รองรับ -->
                <p class="text-xs text-gray-500">
                  รองรับไฟล์ PDF, Word, Excel, รูปภาพ (สูงสุด 10MB ต่อไฟล์)
                </p>
              </div>
              <!-- แสดงข้อความแจ้งเตือนถ้ามีข้อผิดพลาด -->
              <p v-if="form.errors.attachments" class="text-sm text-red-500 mt-1">
                {{ form.errors.attachments }}
              </p>
            </div>
          </div>
        </div>
        
        <!-- ส่วนท้ายของ Modal -->
        <DialogFooter class="flex flex-col sm:flex-row items-center justify-end space-y-2 sm:space-y-0 sm:space-x-2">
          <!-- ปุ่มยกเลิก -->
          <Button type="button" variant="outline" @click="closeModal" class="w-full sm:w-auto flex items-center gap-2">
            <XIcon class="h-4 w-4" />
            <span>ยกเลิก</span>
          </Button>
          <!-- ปุ่มบันทึก/เพิ่ม -->
          <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto flex items-center gap-2">
            <SaveIcon class="h-4 w-4" />
            <span>{{ isEditing ? 'บันทึก' : 'เพิ่ม' }}</span>
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

