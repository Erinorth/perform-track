<!-- 
  ไฟล์: resources/js/pages/organizational_risk/OrganizationalRiskForm.vue
  คำอธิบาย: ฟอร์มสำหรับการสร้างและแก้ไขข้อมูลความเสี่ยงระดับองค์กร
  ทำหน้าที่: แสดงฟอร์มที่สามารถใช้ได้ทั้ง create และ edit mode ในหน้าเดียว
  หลักการ: ตรวจสอบ route name หรือ props เพื่อกำหนด mode และปรับ UI/logic ตามโหมด
  ใช้ร่วมกับ: OrganizationalRiskController.php, useOrganizationalRiskForm.ts
-->

<script setup lang="ts">
// ---------------------------------------------------
// นำเข้าไลบรารีและคอมโพเนนต์ที่จำเป็น
// ---------------------------------------------------
import { computed, onMounted, ref } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
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
  InfoIcon, Trash2Icon, HelpCircleIcon, Loader2Icon,
  PlusIcon, EditIcon
} from 'lucide-vue-next'

// Types และ Composables
import type { OrganizationalRisk } from '@/types/types'
import { useOrganizationalRiskForm } from '@/composables/useOrganizationalRiskForm'
import { type BreadcrumbItem } from '@/types'

// ---------------------------------------------------
// กำหนด Types สำหรับฟอร์ม
// ---------------------------------------------------
type RiskFormData = {
  risk_name: string;
  description: string;
  attachments: File[] | null;
}

type FormMode = 'create' | 'edit'

// กำหนด props สำหรับรับข้อมูลจาก controller (optional)
const props = defineProps<{
  risk?: OrganizationalRisk; // ข้อมูลความเสี่ยง (มีเฉพาะในโหมดแก้ไข)
}>()

// ---------------------------------------------------
// ตรวจสอบ Mode จาก Route Name
// ---------------------------------------------------
const page = usePage()

// กำหนด mode โดยตรวจสอบจาก route name หรือการมีอยู่ของ props.risk
const formMode = computed<FormMode>(() => {
  const routeName = page.props.ziggy?.route
  
  // ตรวจสอบจาก route name ก่อน
  if (routeName?.includes('create')) {
    return 'create'
  } else if (routeName?.includes('edit')) {
    return 'edit'
  }
  
  // หากไม่มี route name ให้ตรวจสอบจาก props
  return props.risk ? 'edit' : 'create'
})

// กำหนดสถานะตาม mode
const isEditMode = computed(() => formMode.value === 'edit')
const isCreateMode = computed(() => formMode.value === 'create')

// ---------------------------------------------------
// Breadcrumbs สำหรับการนำทาง (แตกต่างกันตาม mode)
// ---------------------------------------------------
const breadcrumbs = computed<BreadcrumbItem[]>(() => {
  const baseBreadcrumb: BreadcrumbItem = {
    title: 'จัดการความเสี่ยงองค์กร',
    href: route('organizational-risks.index'),
  }

  if (isCreateMode.value) {
    return [
      baseBreadcrumb,
      {
        title: 'เพิ่มความเสี่ยงใหม่',
        href: '#',
      },
    ]
  }

  // Edit mode
  return [
    baseBreadcrumb,
    {
      title: props.risk?.risk_name || 'แก้ไขข้อมูล',
      href: props.risk ? route('organizational-risks.show', props.risk.id) : '#',
    },
    {
      title: 'แก้ไข',
      href: '#',
    },
  ]
})

// ---------------------------------------------------
// Page Titles และ Descriptions (ปรับตาม Mode)
// ---------------------------------------------------
const pageTitle = computed(() => {
  if (isCreateMode.value) {
    return 'เพิ่มความเสี่ยงระดับองค์กรใหม่'
  }
  return `แก้ไขความเสี่ยงระดับองค์กร: ${props.risk?.risk_name || ''}`
})

const pageDescription = computed(() => {
  if (isCreateMode.value) {
    return 'สร้างข้อมูลความเสี่ยงระดับองค์กรใหม่ พร้อมแนบเอกสารประกอบ'
  }
  return 'แก้ไขข้อมูลความเสี่ยงระดับองค์กร และจัดการเอกสารแนับ'
})

const headTitle = computed(() => {
  if (isCreateMode.value) {
    return 'เพิ่มความเสี่ยงใหม่ - ระบบประเมินความเสี่ยง'
  }
  return `แก้ไขความเสี่ยง: ${props.risk?.risk_name || ''} - ระบบประเมินความเสี่ยง`
})

// ---------------------------------------------------
// Reactive State & Composable Functions
// ---------------------------------------------------
// ใช้ composable เพื่อแยกการจัดการข้อมูลออกจาก UI
const { 
  existingAttachments, selectedFiles, fileNames,
  loadAttachments, submitForm, addSelectedFiles, removeSelectedFile, 
  markAttachmentForDeletion, openAttachment, validateFiles,
  getFileIcon, formatFileSize 
} = useOrganizationalRiskForm(formMode.value)

// สถานะสำหรับการแสดงผล UI
const showHelp = ref<boolean>(false)
const isSubmitting = ref<boolean>(false)

// ---------------------------------------------------
// Form Setup พร้อมค่าเริ่มต้นตาม Mode
// ---------------------------------------------------
const form = useForm<RiskFormData>({
  risk_name: props.risk?.risk_name ?? '',
  description: props.risk?.description ?? '',
  attachments: null,
})

// ---------------------------------------------------
// Lifecycle Hooks
// ---------------------------------------------------
onMounted(() => {
  console.log(`โหลดฟอร์ม - Mode: ${formMode.value}`, {
    route: page.props.ziggy?.route,
    hasRisk: !!props.risk,
    riskName: props.risk?.risk_name
  })
  
  // ตั้งค่าเริ่มต้นสำหรับโหมดแก้ไข
  if (isEditMode.value && props.risk) {
    // กำหนดค่าให้กับฟอร์ม
    form.risk_name = props.risk.risk_name
    form.description = props.risk.description
    
    // โหลดรายการเอกสารแนบที่มีอยู่
    loadAttachments(props.risk)
    
    console.log('โหลดข้อมูลสำหรับการแก้ไข:', {
      risk_id: props.risk.id,
      attachments_count: props.risk.attachments?.length || 0
    })
  }
})

// ---------------------------------------------------
// Methods
// ---------------------------------------------------
/**
 * กลับไปยังหน้าที่เหมาะสมตาม mode
 */
const goBack = () => {
  if (isEditMode.value && props.risk) {
    // กลับไปยังหน้ารายละเอียดความเสี่ยง
    router.get(route('organizational-risks.show', props.risk.id))
  } else {
    // กลับไปยังหน้ารายการความเสี่ยง
    router.get(route('organizational-risks.index'))
  }
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
    console.log('เพิ่มไฟล์ใหม่:', input.files.length, 'ไฟล์')
  }
  
  // รีเซ็ต input เพื่อให้สามารถเลือกไฟล์เดิมซ้ำได้
  input.value = ''
}

/**
 * ตรวจสอบความถูกต้องของฟอร์มก่อนบันทึก
 * @returns {boolean} ผลการตรวจสอบ
 */
const validateForm = (): boolean => {
  let isValid = true
  const errors: string[] = []
  
  // ตรวจสอบข้อมูลสำคัญ
  if (!form.risk_name.trim()) {
    errors.push('กรุณาระบุชื่อความเสี่ยง')
    isValid = false
  }
  
  if (form.risk_name.trim().length < 3) {
    errors.push('ชื่อความเสี่ยงต้องมีอย่างน้อย 3 ตัวอักษร')
    isValid = false
  }
  
  if (!form.description.trim()) {
    errors.push('กรุณาระบุรายละเอียดความเสี่ยง')
    isValid = false
  }
  
  if (form.description.trim().length < 10) {
    errors.push('รายละเอียดความเสี่ยงต้องมีอย่างน้อย 10 ตัวอักษร')
    isValid = false
  }
  
  // ตรวจสอบไฟล์แนับ
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
    const loadingMessage = isCreateMode.value ? 'กำลังสร้างข้อมูล...' : 'กำลังบันทึกข้อมูล...'
    toast.loading(loadingMessage, {
      id: 'saving-risk',
      duration: 60000
    })
    
    console.log(`ส่งข้อมูล - Mode: ${formMode.value}`, {
      risk_id: isEditMode.value ? props.risk?.id : 'new',
      files_count: selectedFiles.value.length,
      attachments_to_delete: isEditMode.value ? 'จาก composable' : 'ไม่มี'
    })
    
    // กำหนด callback สำหรับการ redirect หลังบันทึกสำเร็จ
    const successCallback = () => {
      // ล้าง toast loading
      toast.dismiss('saving-risk')
      
      // แสดงข้อความสำเร็จ
      const successMessage = isCreateMode.value ? 'สร้างข้อมูลเรียบร้อย' : 'บันทึกข้อมูลเรียบร้อย'
      toast.success(successMessage, {
        duration: 3000
      })
      
      // Redirect ตาม mode
      if (isCreateMode.value) {
        router.get(route('organizational-risks.index'))
      } else if (props.risk) {
        router.get(route('organizational-risks.show', props.risk.id))
      }
    }
    
    // เรียกใช้ฟังก์ชันบันทึกข้อมูลจาก composable
    await submitForm(
      { 
        risk_name: form.risk_name.trim(), 
        description: form.description.trim() 
      },
      isEditMode.value ? props.risk?.id : undefined,
      successCallback
    )
    
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล:', error)
    
    // ล้าง toast loading และแสดงข้อความข้อผิดพลาด
    toast.dismiss('saving-risk')
    
    const errorMessage = isCreateMode.value ? 'ไม่สามารถสร้างข้อมูลได้' : 'ไม่สามารถบันทึกข้อมูลได้'
    toast.error(errorMessage, {
      description: 'กรุณาลองใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ',
      duration: 5000
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
  <Head :title="headTitle" />

  <!-- ใช้ Layout หลักของแอปพลิเคชันพร้อมส่ง breadcrumbs เป็น prop -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
      <!-- การ์ดหลักสำหรับฟอร์ม -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <!-- ไอคอนแตกต่างกันตาม mode -->
            <PlusIcon v-if="isCreateMode" class="h-5 w-5 text-green-600" />
            <EditIcon v-else class="h-5 w-5 text-blue-600" />
            
            <!-- แสดงสถานะ mode -->
            <span class="text-base">{{ pageTitle }}</span>
            
            <!-- Badge แสดง mode -->
            <span 
              :class="[
                'px-2 py-1 text-xs font-medium rounded-full',
                isCreateMode 
                  ? 'bg-green-100 text-green-800 border border-green-200' 
                  : 'bg-blue-100 text-blue-800 border border-blue-200'
              ]"
            >
              {{ isCreateMode ? 'สร้างใหม่' : 'แก้ไข' }}
            </span>
          </CardTitle>
          <CardDescription>{{ pageDescription }}</CardDescription>
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
                
                <!-- ข้อความช่วยเหลือ -->
                <div v-if="showHelp" class="text-xs text-gray-500 bg-gray-50 p-2 rounded-md mb-1">
                  ชื่อความเสี่ยงควรระบุให้ชัดเจนและกระชับ แสดงถึงผลกระทบที่อาจเกิดขึ้นกับองค์กร
                  <br />ตัวอย่าง: "ความล่าช้าในการส่งมอบสินค้า", "การรั่วไหลของข้อมูลส่วนบุคคล"
                </div>
                
                <Input 
                  id="risk_name" 
                  v-model="form.risk_name" 
                  placeholder="ระบุชื่อความเสี่ยงระดับองค์กร"
                  :class="form.errors.risk_name ? 'border-red-500 focus:border-red-500' : ''"
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
                  placeholder="ระบุรายละเอียดความเสี่ยง เช่น สาเหตุ ผลกระทบที่อาจเกิดขึ้น และมาตรการป้องกัน" 
                  :rows="4"
                  :class="form.errors.description ? 'border-red-500 focus:border-red-500' : ''"
                />
                <p v-if="form.errors.description" class="text-sm text-red-500">
                  {{ form.errors.description }}
                </p>
              </div>

              <!-- ส่วนของเอกสารแนบ -->
              <div class="grid gap-2 mt-4">
                <Label class="flex items-center gap-2">
                  เอกสารแนบ
                  <span class="text-xs text-gray-500">(ไม่บังคับ)</span>
                </Label>
                
                <!-- แสดงเอกสารแนบที่มีอยู่แล้ว (กรณีแก้ไข) -->
                <div v-if="isEditMode && existingAttachments.length > 0" class="mb-3">
                  <p class="text-sm font-medium text-gray-700 mb-2">
                    เอกสารแนบปัจจุบัน ({{ existingAttachments.length }} ไฟล์):
                  </p>
                  <ul class="space-y-2">
                    <li 
                      v-for="attachment in existingAttachments" 
                      :key="attachment.id" 
                      class="flex flex-wrap items-center justify-between p-2 bg-gray-50 rounded-md text-sm border border-gray-200 hover:bg-gray-100 transition-colors"
                    >
                      <!-- ส่วนแสดงข้อมูลไฟล์ -->
                      <div 
                        class="flex items-center gap-2 flex-1 min-w-0 overflow-hidden cursor-pointer" 
                        @click="openAttachment(attachment.url)"
                        title="คลิกเพื่อเปิดดูไฟล์"
                      >
                        <component :is="getFileIcon(attachment.file_name)" class="h-4 w-4 flex-shrink-0" />
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
                        title="ลบเอกสาร"
                      >
                        <Trash2Icon class="h-4 w-4" />
                      </Button>
                    </li>
                  </ul>
                </div>
                
                <!-- แสดงไฟล์ที่เพิ่งอัปโหลด -->
                <div v-if="fileNames.length > 0" class="mb-3">
                  <p class="text-sm font-medium text-gray-700 mb-2">
                    ไฟล์ที่เลือกไว้ ({{ fileNames.length }} ไฟล์):
                  </p>
                  <ul class="space-y-2">
                    <li 
                      v-for="(fileName, index) in fileNames" 
                      :key="index"
                      class="flex items-center justify-between p-2 bg-blue-50 rounded-md text-sm border border-blue-200"
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
                        title="ลบไฟล์ที่เลือก"
                      >
                        <XCircleIcon class="h-4 w-4" />
                      </Button>
                    </li>
                  </ul>
                </div>

                <!-- ปุ่มและคำแนะนำการอัปโหลดไฟล์ -->
                <div class="flex flex-col">
                  <div class="flex flex-wrap items-center gap-2">
                    <label for="file-upload" class="flex items-center gap-2 cursor-pointer px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
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
                      รองรับไฟล์ประเภท PDF, Word, Excel, รูปภาพ (ขนาดไม่เกิน 10MB ต่อไฟล์)
                    </p>
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
          <!-- ปุ่มซ้าย -->
          <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <!-- ปุ่มกลับ -->
            <Button
              type="button"
              variant="outline"
              @click="goBack"
              class="w-full sm:w-auto flex items-center gap-2"
            >
              <ArrowLeftIcon class="h-4 w-4" />
              <span>{{ isCreateMode ? 'กลับไปรายการ' : 'กลับไปรายละเอียด' }}</span>
            </Button>
          </div>
          
          <!-- ปุ่มบันทึก -->
          <Button
            type="button"
            @click="handleSubmit"
            :disabled="isSubmitting"
            :class="[
              'w-full sm:w-auto flex items-center gap-2 transition-all',
              isCreateMode 
                ? 'bg-green-600 hover:bg-green-700 text-white' 
                : 'bg-blue-600 hover:bg-blue-700 text-white',
              isSubmitting ? 'opacity-75 cursor-not-allowed' : ''
            ]"
          >
            <!-- แสดง Loading spinner เมื่อกำลังประมวลผล -->
            <Loader2Icon v-if="isSubmitting" class="h-4 w-4 animate-spin" />
            <PlusIcon v-else-if="isCreateMode" class="h-4 w-4" />
            <SaveIcon v-else class="h-4 w-4" />
            
            <!-- เปลี่ยนข้อความเมื่อกำลังประมวลผล -->
            <span>
              {{ 
                isSubmitting 
                  ? (isCreateMode ? 'กำลังสร้าง...' : 'กำลังบันทึก...') 
                  : (isCreateMode ? 'สร้างความเสี่ยง' : 'บันทึกการแก้ไข') 
              }}
            </span>
          </Button>
        </CardFooter>
      </Card>
      
      <!-- Loading overlay สำหรับแสดงระหว่างกำลังบันทึกข้อมูล -->
      <div 
        v-if="isSubmitting" 
        class="fixed inset-0 bg-black/20 flex items-center justify-center z-50"
        role="dialog" 
        aria-modal="true"
        aria-labelledby="loading-title"
      >
        <div class="flex flex-col items-center gap-3 bg-white p-6 rounded-lg shadow-xl border max-w-sm mx-4">
          <Loader2Icon class="h-10 w-10 animate-spin text-primary" />
          <div class="text-center">
            <p id="loading-title" class="text-lg font-medium">
              {{ isCreateMode ? 'กำลังสร้างข้อมูล' : 'กำลังบันทึกข้อมูล' }}
            </p>
            <p class="text-sm text-gray-500 mt-1">
              กรุณารอสักครู่...
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
