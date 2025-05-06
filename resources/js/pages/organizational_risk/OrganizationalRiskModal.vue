<!-- 
  ไฟล์: resources\js\pages\organizational_risk\OrganizationalRiskModal.vue
  Modal สำหรับเพิ่มและแก้ไขข้อมูลความเสี่ยงระดับองค์กร พร้อมการจัดการเอกสารแนบ
  ใช้ shadcn-vue components และ Inertia.js สำหรับการทำงานกับ Laravel backend
-->

<script setup lang="ts">
// นำเข้า APIs จาก Vue และ libraries ที่จำเป็น
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { toast } from 'vue-sonner';
import { SaveIcon, XIcon, FileIcon, Trash2Icon, UploadIcon, XCircleIcon, AlertCircleIcon, InfoIcon } from 'lucide-vue-next';
import type { OrganizationalRisk, Attachment } from '@/types/types';

// เพิ่มการนำเข้า composable
import { useOrganizationalRiskData } from '@/composables/useOrganizationalRiskData';

// กำหนด props ที่รับจาก parent component
const props = defineProps<{
  show: boolean;
  risk?: OrganizationalRisk;
  initialRisks?: OrganizationalRisk[];
}>();

// กำหนด events ที่จะส่งไปยัง parent component
const emit = defineEmits<{
  (e: 'update:show', value: boolean): void;
  (e: 'saved'): void;
}>();

// เรียกใช้ composable เพื่อจัดการข้อมูล
const { submitForm } = useOrganizationalRiskData(props.initialRisks || []);

// สร้าง computed properties สำหรับตรวจสอบโหมดการทำงานและตั้งชื่อ Modal
const isEditing = computed(() => !!props.risk?.id);
const modalTitle = computed(() => isEditing.value ? 'แก้ไขความเสี่ยงองค์กร' : 'เพิ่มความเสี่ยงองค์กร');

// สร้างตัวแปรสำหรับเก็บข้อมูลเอกสารแนบที่มีอยู่
const existingAttachments = ref<Attachment[]>([]);
// สร้างตัวแปรสำหรับเก็บรายการไอดีของเอกสารแนบที่ต้องการลบ
const attachmentsToDelete = ref<number[]>([]);
// สร้างตัวแปรสำหรับเก็บชื่อเอกสารที่กำลังจะอัปโหลด
const fileNames = ref<string[]>([]);

// สร้าง form สำหรับจัดการข้อมูลและการส่งไปยัง backend
const form = useForm({
  risk_name: props.risk?.risk_name ?? '',
  description: props.risk?.description ?? '',
  attachments: [] as File[],                   // เอกสารแนบใหม่ที่จะอัปโหลด
  attachments_to_delete: [] as number[],       // รายการไอดีของเอกสารแนบที่ต้องการลบ
});

// ใช้ watch เพื่อรีเซ็ตฟอร์มเมื่อ Modal เปิดขึ้น
watch(() => props.show, (newVal) => {
  if (newVal && props.risk) {
    // กรณี Modal เปิดและมีข้อมูลความเสี่ยงส่งมา (โหมดแก้ไข)
    form.risk_name = props.risk.risk_name;
    form.description = props.risk.description;
    
    // รีเซ็ตรายการเอกสารแนบที่ต้องการลบ
    attachmentsToDelete.value = [];
    form.attachments_to_delete = [];
    
    // ถ้ามีเอกสารแนบ ให้เพิ่มลงในตัวแปร existingAttachments
    if (props.risk.attachments) {
      existingAttachments.value = [...props.risk.attachments];
    } else {
      existingAttachments.value = [];
    }
    
    // รีเซ็ตรายการไฟล์ที่จะอัปโหลด
    form.attachments = [];
    fileNames.value = [];
  } else if (newVal) {
    // กรณี Modal เปิดแต่ไม่มีข้อมูลความเสี่ยงส่งมา (โหมดเพิ่มใหม่)
    form.reset();
    
    // รีเซ็ตข้อมูลเอกสารแนบทั้งหมด
    existingAttachments.value = [];
    attachmentsToDelete.value = [];
    form.attachments = [];
    form.attachments_to_delete = [];
    fileNames.value = [];
  }
});

// ฟังก์ชันสำหรับปิด Modal
const closeModal = () => {
  emit('update:show', false);
};

// ฟังก์ชันสำหรับจัดการการอัปโหลดเอกสารแนบ
const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files.length > 0) {
    // เพิ่มไฟล์ใหม่เข้าไปในรายการที่จะอัปโหลด
    for (let i = 0; i < input.files.length; i++) {
      const file = input.files[i];
      form.attachments.push(file);
      fileNames.value.push(file.name);
    }
    
    // รีเซ็ต input เพื่อให้สามารถเลือกไฟล์เดิมซ้ำได้
    input.value = '';
  }
};

// ฟังก์ชันสำหรับลบไฟล์ที่เพิ่งเลือกแต่ยังไม่ได้อัปโหลด
const removeSelectedFile = (index: number) => {
  // สร้างอาร์เรย์ใหม่โดยกรองไฟล์ที่ต้องการลบออก
  const newAttachments: File[] = [];
  const newFileNames: string[] = [];
  
  for (let i = 0; i < form.attachments.length; i++) {
    if (i !== index) {
      newAttachments.push(form.attachments[i]);
      newFileNames.push(fileNames.value[i]);
    }
  }
  
  // อัปเดตข้อมูล
  form.attachments = newAttachments;
  fileNames.value = newFileNames;
};

// ฟังก์ชันสำหรับเพิ่มเอกสารแนบที่ต้องการลบลงในรายการ
const markAttachmentForDeletion = (attachmentId: number) => {
  // เพิ่มไอดีของเอกสารแนบลงในรายการที่ต้องการลบ
  attachmentsToDelete.value.push(attachmentId);
  form.attachments_to_delete = [...attachmentsToDelete.value];
  
  // อัปเดตรายการเอกสารแนบที่แสดงในหน้าจอโดยซ่อนรายการที่จะลบ
  existingAttachments.value = existingAttachments.value.filter(
    attachment => !attachmentsToDelete.value.includes(attachment.id)
  );
  
  // แสดงข้อความแจ้งเตือน
  toast.success('เอกสารถูกมาร์คให้ลบเมื่อบันทึกข้อมูล');
};

// ฟังก์ชันตรวจสอบข้อมูลก่อนส่ง
const validateForm = () => {
  let isValid = true;
  const errors = [];

  if (!form.risk_name.trim()) {
    errors.push('กรุณาระบุชื่อความเสี่ยง');
    isValid = false;
  }
  
  if (!form.description.trim()) {
    errors.push('กรุณาระบุรายละเอียดความเสี่ยง');
    isValid = false;
  }

  // ตรวจสอบนามสกุลของไฟล์ที่อัปโหลด (ยอมรับเฉพาะ PDF, Word, Excel, Image)
  const allowedFileTypes = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.jpg', '.jpeg', '.png'];
  
  for (const file of form.attachments) {
    const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase();
    if (!allowedFileTypes.includes(fileExtension)) {
      errors.push(`ไฟล์ "${file.name}" มีนามสกุลไม่ถูกต้อง (รองรับ pdf, word, excel, รูปภาพ)`);
      isValid = false;
      break;
    }
    
    // ตรวจสอบขนาดไฟล์ (ไม่เกิน 10MB)
    if (file.size > 10 * 1024 * 1024) {
      errors.push(`ไฟล์ "${file.name}" มีขนาดใหญ่เกินไป (ไม่เกิน 10MB)`);
      isValid = false;
      break;
    }
  }

  if (!isValid) {
    toast.warning('กรุณากรอกข้อมูลให้ครบถ้วน', {
      icon: InfoIcon,
      description: errors.join(', ')
    });
  }

  return isValid;
};

// ฟังก์ชันสำหรับส่งฟอร์ม
const handleSubmit = async () => {
  // ตรวจสอบความถูกต้องของข้อมูลก่อนส่ง
  if (!validateForm()) return;
  
  // เตรียมข้อมูลสำหรับส่งไปยัง server
  form.transform((data) => ({
    ...data,
    attachments_to_delete: attachmentsToDelete.value
  }));
  
  try {
    // กำหนด options สำหรับการส่งข้อมูลแบบ multipart/form-data
    const options = {
      forceFormData: true,
      onSuccess: () => {
        closeModal();
        emit('saved');
        toast.success(isEditing.value ? 'บันทึกข้อมูลเรียบร้อยแล้ว' : 'เพิ่มข้อมูลเรียบร้อยแล้ว');
      },
      onError: (errors: Record<string, string>) => {
        const errorMessages = Object.values(errors).join(', ');
        toast.error('เกิดข้อผิดพลาด', {
          description: errorMessages
        });
      }
    };
    
    // ส่งข้อมูลไปยัง backend
    if (isEditing.value) {
      form.put(route('organizational-risks.update', props.risk?.id), options);
    } else {
      form.post(route('organizational-risks.store'), options);
    }
  } catch (error) {
    console.error('เกิดข้อผิดพลาดระหว่างการบันทึกข้อมูล:', error);
    toast.error('เกิดข้อผิดพลาด', {
      description: 'ไม่สามารถบันทึกข้อมูลได้ โปรดลองอีกครั้งในภายหลัง'
    });
  }
};

// คำนวณขนาดไฟล์ให้อยู่ในรูปแบบที่อ่านได้ง่าย (KB, MB)
const formatFileSize = (bytes: number): string => {
  if (bytes < 1024) {
    return bytes + ' B';
  } else if (bytes < 1024 * 1024) {
    return (bytes / 1024).toFixed(1) + ' KB';
  } else {
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }
};

// ฟังก์ชันสำหรับเปิดไฟล์เอกสารแนบ
const openAttachment = (url: string) => {
  window.open(url, '_blank');
};

// ฟังก์ชันเพื่อดึงไอคอนตามประเภทไฟล์
const getFileIcon = (fileName: string) => {
  return FileIcon; // ใช้ไอคอนเดียวกันสำหรับทุกประเภทไฟล์
};
</script>

<template>
  <Dialog :open="show" @update:open="(val) => emit('update:show', val)">
    <DialogContent class="sm:max-w-[550px]">
      <DialogHeader>
        <DialogTitle>{{ modalTitle }}</DialogTitle>
        <DialogDescription class="sr-only">รายละเอียดฟอร์มสำหรับการจัดการความเสี่ยงองค์กร</DialogDescription>
      </DialogHeader>
      
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
            <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</p>
          </div>
          
          <!-- ส่วนจัดการเอกสารแนบ -->
          <div class="grid gap-2">
            <Label>เอกสารแนบ</Label>
            
            <!-- ส่วนแสดงเอกสารแนบที่มีอยู่แล้ว (กรณีแก้ไข) -->
            <div v-if="existingAttachments.length > 0" class="mb-3">
              <p class="text-sm font-medium text-gray-700 mb-2">เอกสารแนบปัจจุบัน:</p>
              <ul class="space-y-2">
                <li v-for="attachment in existingAttachments" :key="attachment.id" 
                    class="flex items-center justify-between p-2 bg-gray-50 rounded-md text-sm">
                  <div class="flex items-center gap-2 flex-1 overflow-hidden" 
                       @click="openAttachment(attachment.url)" 
                       style="cursor: pointer">
                    <component :is="getFileIcon(attachment.file_name)" class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate">{{ attachment.file_name }}</span>
                    <span class="text-xs text-gray-500 flex-shrink-0">{{ formatFileSize(attachment.file_size || 0) }}</span>
                  </div>
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
                <li v-for="(fileName, index) in fileNames" :key="index" 
                    class="flex items-center justify-between p-2 bg-gray-50 rounded-md text-sm">
                  <div class="flex items-center gap-2 flex-1 overflow-hidden">
                    <component :is="getFileIcon(fileName)" class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate">{{ fileName }}</span>
                    <span class="text-xs text-gray-500 flex-shrink-0">
                      {{ formatFileSize(form.attachments[index].size) }}
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
            
            <!-- ส่วนอัปโหลดไฟล์ใหม่ -->
            <div class="flex flex-col">
              <div class="flex flex-wrap items-center gap-2">
                <label 
                  for="file-upload" 
                  class="flex items-center gap-2 cursor-pointer px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  <UploadIcon class="h-4 w-4" />
                  <span>เลือกไฟล์</span>
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
                  รองรับไฟล์ PDF, Word, Excel, รูปภาพ (สูงสุด 10MB ต่อไฟล์)
                </p>
              </div>
              <p v-if="form.errors.attachments" class="text-sm text-red-500 mt-1">{{ form.errors.attachments }}</p>
            </div>
          </div>
        </div>
        
        <!-- ส่วนท้ายของ Modal แสดงปุ่มดำเนินการ -->
        <DialogFooter class="flex flex-col sm:flex-row items-center justify-end space-y-2 sm:space-y-0 sm:space-x-2">
          <Button type="button" variant="outline" @click="closeModal" class="w-full sm:w-auto flex items-center gap-2">
            <XIcon class="h-4 w-4" />
            <span>ยกเลิก</span>
          </Button>
          <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto flex items-center gap-2">
            <SaveIcon class="h-4 w-4" />
            <span>{{ isEditing ? 'บันทึก' : 'เพิ่ม' }}</span>
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
