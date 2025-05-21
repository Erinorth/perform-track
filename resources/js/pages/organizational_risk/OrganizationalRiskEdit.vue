<!-- 
  ไฟล์: resources/js/pages/organizational_risk/OrganizationalRiskEdit.vue
  คำอธิบาย: หน้าแก้ไขข้อมูลความเสี่ยงระดับองค์กรแบบเต็มหน้าจอ
  ทำหน้าที่: แสดงฟอร์มสำหรับแก้ไขข้อมูลความเสี่ยง และจัดการเอกสารแนบ
  หลักการ: ใช้ Layout หลักของแอปพลิเคชัน ร่วมกับ breadcrumb เพื่อการนำทาง
  ใช้ร่วมกับ: OrganizationalRiskController.php, useOrganizationalRiskData.ts
-->

<script setup lang="ts">
// ---------------------------------------------------
// นำเข้าไลบรารีและคอมโพเนนต์ที่จำเป็น
// ---------------------------------------------------
import { computed, onMounted, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

// UI Components 
import { Card, CardContent, CardFooter, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { toast } from 'vue-sonner'
import { 
  SaveIcon, ArrowLeftIcon, UploadIcon, XCircleIcon, 
  InfoIcon, Trash2Icon, HelpCircleIcon, Loader2Icon 
} from 'lucide-vue-next'

// Types และ Composables
import type { OrganizationalRisk } from '@/types/types'
import { useOrganizationalRiskData } from '@/composables/useOrganizationalRiskData'
import { type BreadcrumbItem } from '@/types';

// ---------------------------------------------------
// กำหนด Types สำหรับฟอร์ม
// ---------------------------------------------------
type RiskFormData = {
  risk_name: string;
  description: string;
  attachments: File[] | null;
}

// กำหนด props สำหรับรับข้อมูลจาก controller
const props = defineProps<{
  risk: OrganizationalRisk; // ข้อมูลความเสี่ยงสำหรับการแก้ไข
}>()

// ---------------------------------------------------
// Breadcrumbs สำหรับการนำทาง
// ---------------------------------------------------
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'จัดการความเสี่ยงองค์กร',
    href: route('organizational-risks.index'),
  },
  {
    title: props.risk.risk_name,
    href: route('organizational-risks.show', props.risk.id),
  },
  {
    title: 'แก้ไข',
    href: '#',
  },
];

// ---------------------------------------------------
// Reactive State & Composable Functions
// ---------------------------------------------------
// ใช้ composable เพื่อแยกการจัดการข้อมูลออกจาก UI
const { 
  existingAttachments, selectedFiles, fileNames,
  loadAttachments, submitForm, addSelectedFiles, removeSelectedFile, 
  markAttachmentForDeletion, openAttachment, validateFiles,
  getFileIcon, formatFileSize 
} = useOrganizationalRiskData(undefined, true)

// สถานะสำหรับการแสดงผล UI
const showHelp = ref<boolean>(false)
const isSubmitting = ref<boolean>(false)

// ---------------------------------------------------
// Computed Properties
// ---------------------------------------------------
const pageTitle = computed(() => `แก้ไขความเสี่ยงระดับองค์กร: ${props.risk.risk_name}`)

// สร้างฟอร์มด้วย Inertia useForm
const form = useForm<RiskFormData>({
  risk_name: props.risk?.risk_name ?? '',
  description: props.risk?.description ?? '',
  attachments: null,
})

// ---------------------------------------------------
// Lifecycle Hooks
// ---------------------------------------------------
onMounted(() => {
  console.log('กำลังโหลดข้อมูลสำหรับแก้ไข:', props.risk.risk_name)
  
  // กำหนดค่าเริ่มต้นให้กับฟอร์ม
  form.risk_name = props.risk.risk_name
  form.description = props.risk.description
  
  // โหลดรายการเอกสารแนบที่มีอยู่
  loadAttachments(props.risk)
})

// ---------------------------------------------------
// Methods
// ---------------------------------------------------
/**
 * กลับไปยังหน้ารายการความเสี่ยง
 */
const goBack = () => {
  router.get(route('organizational-risks.show', props.risk.id))
}

/**
 * จัดการไฟล์ที่อัปโหลด
 * @param event - Event จาก input type="file"
 */
const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement
  
  // เพิ่มไฟล์ที่เลือกเข้า state (ผ่าน composable)
  addSelectedFiles(input.files)
  
  // อัปเดตฟอร์ม
  if (input.files && input.files.length > 0) {
    form.attachments = Array.from(input.files)
  }
  
  // รีเซ็ต input หลังการเลือกไฟล์ (เพื่อให้สามารถเลือกไฟล์เดิมซ้ำได้)
  input.value = ''
}

/**
 * ตรวจสอบความถูกต้องของฟอร์มก่อนบันทึก
 * @returns {boolean} ผลการตรวจสอบ
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
  
  // แสดงข้อความแจ้งเตือนถ้าไม่ผ่านการตรวจสอบ
  if (!isValid) {
    toast.warning('กรุณาตรวจสอบข้อมูล', {
      icon: InfoIcon,
      description: errors.join(', ')
    })
  }
  
  return isValid
}

/**
 * ส่งข้อมูลไปยัง backend เพื่อบันทึก
 */
const handleSubmit = async () => {
  // ตรวจสอบความถูกต้องของฟอร์มก่อนส่ง
  if (!validateForm()) return
  
  try {
    isSubmitting.value = true
    
    // แสดง toast ทันทีที่กดบันทึก
    toast.loading('กำลังบันทึกข้อมูล', {
      id: 'saving-risk',
      duration: 60000 // ตั้งเวลานานพอที่จะรอการประมวลผลเสร็จ
    })
    
    console.log('กำลังส่งข้อมูล, mode: แก้ไข, id:', props.risk?.id)
    
    // เรียกใช้ฟังก์ชันบันทึกข้อมูลจาก composable
    await submitForm(
      { risk_name: form.risk_name, description: form.description }, 
      props.risk?.id,
      () => router.get(route('organizational-risks.show', props.risk.id)) // callback เมื่อสำเร็จ
    )
    
    // เปลี่ยน toast เป็นแจ้งเตือนสำเร็จ
    toast.success('บันทึกข้อมูลเรียบร้อย', {
      id: 'saving-risk'
    })
    
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล:', error)
    
    // เปลี่ยน toast เป็นแจ้งเตือนข้อผิดพลาด
    toast.error('ไม่สามารถบันทึกข้อมูลได้', {
      id: 'saving-risk',
      description: 'กรุณาลองใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ'
    })
  } finally {
    isSubmitting.value = false
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
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head :title="`แก้ไขความเสี่ยง - ${risk.risk_name}`" />

  <!-- ใช้ Layout หลักของแอปพลิเคชันพร้อมส่ง breadcrumbs เป็น prop -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
      <!-- การ์ดหลักสำหรับแก้ไขข้อมูล -->
      <Card>
        <CardHeader>
          <CardTitle>{{ pageTitle }}</CardTitle>
          <CardDescription>แก้ไขข้อมูลความเสี่ยงระดับองค์กร และจัดการเอกสารแนบ</CardDescription>
        </CardHeader>
        
        <CardContent>
          <form @submit.prevent="handleSubmit" class="space-y-4">
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
                  ชื่อความเสี่ยงควรระบุให้ชัดเจนและกระชับ แสดงถึงผลกระทบที่อาจเกิดขึ้นกับองค์กร
                  <br />ตัวอย่าง: "ความล่าช้าในการส่งมอบสินค้า", "การรั่วไหลของข้อมูลส่วนบุคคล"
                </div>
                
                <Input 
                  id="risk_name" 
                  v-model="form.risk_name" 
                  placeholder="ระบุชื่อความเสี่ยงระดับองค์กร"
                />
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
                  placeholder="ระบุรายละเอียดความเสี่ยง เช่น สาเหตุ ผลกระทบที่อาจเกิดขึ้น" 
                  :rows="4"
                />
                <p v-if="form.errors.description" class="text-sm text-red-500">
                  {{ form.errors.description }}
                </p>
              </div>

              <!-- ส่วนของเอกสารแนบ -->
              <div class="grid gap-2 mt-4">
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
                        <!-- ใช้ truncate และกำหนดขนาดสูงสุดตามหน้าจอ -->
                        <span class="truncate max-w-[200px] sm:max-w-[300px]">{{ attachment.file_name }}</span>
                        <span class="text-xs text-gray-500 flex-shrink-0">{{ formatFileSize(attachment.file_size || 0) }}</span>
                      </div>
                      
                      <!-- ปุ่มลบเอกสารแนบ -->
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
                      
                      <!-- ปุ่มลบไฟล์ที่เลือกไว้แต่ยังไม่ได้บันทึก -->
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
          </form>
        </CardContent>
        
        <CardFooter class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <!-- ปุ่มยกเลิก/กลับ -->
          <Button
            type="button"
            variant="outline"
            @click="goBack"
            class="w-full sm:w-auto flex items-center gap-2"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            <span>กลับไปยังรายการ</span>
          </Button>
          
          <!-- ปุ่มบันทึก -->
          <Button
            type="button"
            @click="handleSubmit"
            :disabled="isSubmitting"
            class="w-full sm:w-auto flex items-center gap-2"
          >
            <!-- แสดง Loading spinner เมื่อกำลังประมวลผล -->
            <Loader2Icon v-if="isSubmitting" class="h-4 w-4 animate-spin" />
            <SaveIcon v-else class="h-4 w-4" />
            
            <!-- เปลี่ยนข้อความเมื่อกำลังประมวลผล -->
            <span>{{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึกการแก้ไข' }}</span>
          </Button>
        </CardFooter>
      </Card>
      
      <!-- Loading overlay สำหรับแสดงระหว่างกำลังบันทึกข้อมูล -->
      <div 
        v-if="isSubmitting" 
        class="fixed inset-0 bg-white/70 flex items-center justify-center z-50"
      >
        <div class="flex flex-col items-center gap-2 bg-white p-6 rounded-lg shadow-lg">
          <Loader2Icon class="h-10 w-10 animate-spin text-primary" />
          <p class="text-md font-medium">กำลังบันทึกข้อมูล กรุณารอสักครู่...</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
