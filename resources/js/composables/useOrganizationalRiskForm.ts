// ไฟล์: resources/js/composables/useOrganizationalRiskForm.ts
// คำอธิบาย: Composable สำหรับจัดการ logic ของฟอร์มความเสี่ยงองค์กร
// ทำหน้าที่: แยก business logic ออกจาก UI component
// หลักการ: รองรับทั้ง create และ edit mode

import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { FileTextIcon, FileImageIcon, FileSpreadsheetIcon, FileIcon } from 'lucide-vue-next'
import type { OrganizationalRisk, OrganizationalRiskAttachment } from '@/types/types'

type FormMode = 'create' | 'edit'

interface FileValidationResult {
  valid: boolean;
  errors: string[];
}

interface FormData {
  risk_name: string;
  description: string;
}

export function useOrganizationalRiskForm(mode: FormMode) {
  // ---------------------------------------------------
  // Reactive State
  // ---------------------------------------------------
  const existingAttachments = ref<OrganizationalRiskAttachment[]>([])
  const selectedFiles = ref<File[]>([])
  const attachmentsToDelete = ref<number[]>([])

  // ---------------------------------------------------
  // Computed Properties
  // ---------------------------------------------------
  const fileNames = computed(() => selectedFiles.value.map(file => file.name))

  // ---------------------------------------------------
  // Methods สำหรับจัดการเอกสารแนบ
  // ---------------------------------------------------
  
  /**
   * โหลดรายการเอกสารแนบที่มีอยู่ (สำหรับ edit mode)
   * @param risk - ข้อมูลความเสี่ยง
   */
  const loadAttachments = (risk: OrganizationalRisk) => {
    if (mode === 'edit' && risk.attachments) {
      existingAttachments.value = [...risk.attachments]
      console.log('โหลดเอกสารแนบ:', existingAttachments.value.length, 'ไฟล์')
    }
  }

  /**
   * เพิ่มไฟล์ที่เลือกใหม่
   * @param files - รายการไฟล์ที่เลือก
   */
  const addSelectedFiles = (files: FileList | null) => {
    if (!files) return

    const validFiles: File[] = []
    const errors: string[] = []

    Array.from(files).forEach(file => {
      // ตรวจสอบขนาดไฟล์ (ไม่เกิน 10MB)
      if (file.size > 10 * 1024 * 1024) {
        errors.push(`ไฟล์ ${file.name} มีขนาดใหญ่เกินไป (เกิน 10MB)`)
        return
      }

      // ตรวจสอบประเภทไฟล์
      const allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/jpg',
        'image/png'
      ]

      if (!allowedTypes.includes(file.type)) {
        errors.push(`ไฟล์ ${file.name} ไม่ใช่ประเภทที่รองรับ`)
        return
      }

      validFiles.push(file)
    })

    if (errors.length > 0) {
      toast.warning('ไฟล์บางไฟล์ไม่สามารถเพิ่มได้', {
        description: errors.join(', ')
      })
    }

    if (validFiles.length > 0) {
      selectedFiles.value.push(...validFiles)
      console.log('เพิ่มไฟล์ใหม่:', validFiles.length, 'ไฟล์')
    }
  }

  /**
   * ลบไฟล์ที่เลือกไว้
   * @param index - ดัชนีของไฟล์ที่ต้องการลบ
   */
  const removeSelectedFile = (index: number) => {
    if (index >= 0 && index < selectedFiles.value.length) {
      const removedFile = selectedFiles.value.splice(index, 1)[0]
      console.log('ลบไฟล์:', removedFile.name)
    }
  }

  /**
   * ทำเครื่องหมายเอกสารแนบเพื่อลบ (สำหรับ edit mode)
   * @param attachmentId - ID ของเอกสารแนบ
   */
  const markAttachmentForDeletion = (attachmentId: number) => {
    if (!attachmentsToDelete.value.includes(attachmentId)) {
      attachmentsToDelete.value.push(attachmentId)
      
      // ลบออกจากรายการแสดงผล
      existingAttachments.value = existingAttachments.value.filter(
        attachment => attachment.id !== attachmentId
      )
      
      console.log('ทำเครื่องหมายลบเอกสาร ID:', attachmentId)
      toast.info('เอกสารจะถูกลบเมื่อบันทึกข้อมูล')
    }
  }

  /**
   * เปิดดูเอกสารแนบ
   * @param url - URL ของเอกสาร
   */
  const openAttachment = (url: string) => {
    window.open(url, '_blank')
    console.log('เปิดดูเอกสาร:', url)
  }

  /**
   * ตรวจสอบความถูกต้องของไฟล์
   * @param files - รายการไฟล์ที่ต้องการตรวจสอบ
   * @returns ผลการตรวจสอบ
   */
  const validateFiles = (files: File[]): FileValidationResult => {
    const errors: string[] = []
    let valid = true

    files.forEach(file => {
      // ตรวจสอบขนาดไฟล์
      if (file.size > 10 * 1024 * 1024) {
        errors.push(`ไฟล์ ${file.name} มีขนาดใหญ่เกินไป`)
        valid = false
      }
    })

    return { valid, errors }
  }

  /**
   * ได้ไอคอนที่เหมาะสมตามประเภทไฟล์
   * @param fileName - ชื่อไฟล์
   * @returns คอมโพเนนต์ไอคอน
   */
  const getFileIcon = (fileName: string) => {
    const extension = fileName.split('.').pop()?.toLowerCase()
    
    switch (extension) {
      case 'pdf':
        return FileTextIcon
      case 'jpg':
      case 'jpeg':
      case 'png':
        return FileImageIcon
      case 'xls':
      case 'xlsx':
        return FileSpreadsheetIcon
      case 'doc':
      case 'docx':
        return FileTextIcon
      default:
        return FileIcon
    }
  }

  /**
   * จัดรูปแบบขนาดไฟล์
   * @param bytes - ขนาดไฟล์ในหน่วยไบต์
   * @returns ขนาดไฟล์ในรูปแบบที่อ่านง่าย
   */
  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes'
    
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
  }

  /**
   * ส่งข้อมูลฟอร์มไปยัง backend
   * @param data - ข้อมูลฟอร์ม
   * @param riskId - ID ของความเสี่ยง (สำหรับ edit mode)
   * @param onSuccess - callback เมื่อบันทึกสำเร็จ
   */
    const submitForm = async (
    data: FormData,
    riskId?: number,
    onSuccess?: () => void
    ) => {
    try {
        // เตรียมข้อมูลสำหรับส่ง
        const formData = new FormData()
        formData.append('risk_name', data.risk_name)
        formData.append('description', data.description)

        // เพิ่มไฟล์แนบใหม่
        selectedFiles.value.forEach((file, index) => {
        formData.append(`attachments[${index}]`, file)
        })

        // เพิ่มรายการเอกสารที่ต้องการลบ (สำหรับ edit mode)
        if (mode === 'edit' && attachmentsToDelete.value.length > 0) {
        attachmentsToDelete.value.forEach((id, index) => {
            formData.append(`attachments_to_delete[${index}]`, id.toString())
        })
        }

        // กำหนด URL และ method ตาม mode
        let url: string

        if (mode === 'create') {
        url = route('organizational-risks.store')
        } else {
        url = route('organizational-risks.update', riskId!)
        formData.append('_method', 'PUT')
        }

        console.log(`ส่งข้อมูล ${mode} ไปยัง:`, url)

        // ส่งข้อมูลผ่าน Inertia
        router.post(url, formData, {
        forceFormData: true,
        onSuccess: () => {
            console.log(`${mode} สำเร็จ`)
            onSuccess?.()
        },
        onError: (errors) => {
            console.error(`เกิดข้อผิดพลาดใน ${mode}:`, errors)
            throw new Error(`ไม่สามารถ${mode === 'create' ? 'สร้าง' : 'บันทึก'}ข้อมูลได้`)
        }
        })

    } catch (error) {
        console.error(`เกิดข้อผิดพลาดใน submitForm (${mode}):`, error)
        throw error
    }
    }

  // ---------------------------------------------------
  // Return composable interface
  // ---------------------------------------------------
  return {
    // State
    existingAttachments,
    selectedFiles,
    fileNames,
    attachmentsToDelete,

    // Methods
    loadAttachments,
    addSelectedFiles,
    removeSelectedFile,
    markAttachmentForDeletion,
    openAttachment,
    validateFiles,
    getFileIcon,
    formatFileSize,
    submitForm,
  }
}
