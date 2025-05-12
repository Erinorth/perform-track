/* 
  ไฟล์: resources/js/composables/useDivisionRiskData.ts
  คำอธิบาย: Composable function สำหรับจัดการข้อมูลความเสี่ยงระดับฝ่าย
  ทำหน้าที่: แยกโลจิกการจัดการข้อมูลออกจาก component เพื่อเพิ่มความเป็นระเบียบ
  หลักการ: แบ่งโลจิกเป็นหมวดหมู่ - การโหลดข้อมูล, การจัดการฟอร์ม, การจัดการไฟล์แนบ
  ใช้ร่วมกับ: DivisionRiskController.php ในฝั่ง Backend
*/

import { ref, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { CheckCircle2Icon, FileIcon, FileTextIcon, ImageIcon, FileSpreadsheetIcon, AlertTriangleIcon } from 'lucide-vue-next';
import type { DivisionRisk, DivisionRiskAttachment } from '@/types/types';

// นิยาม interface สำหรับข้อมูลฟอร์ม
interface RiskFormData {
  risk_name: string;
  description: string;
  organizational_risk_id?: number | null;
  attachments?: File[] | null;
}

// ฟังก์ชัน composable สำหรับจัดการข้อมูลความเสี่ยงระดับฝ่าย
export function useDivisionRiskData(initialRisks: DivisionRisk[] = [], triggerProp?: any) {
  // ============ STATE MANAGEMENT ============
  const data = ref<DivisionRisk[]>([]);
  const existingAttachments = ref<DivisionRiskAttachment[]>([]);
  const attachmentsToDelete = ref<number[]>([]);
  const selectedFiles = ref<File[]>([]);
  const fileNames = ref<string[]>([]);
  const isLoading = ref<boolean>(false);
  const isSubmitting = ref<boolean>(false);
  
  // ใช้ usePage hook ของ Inertia เพื่อเข้าถึงข้อมูลจาก props
  const page = usePage();

  // ทำงานเมื่อ component ถูก mount
  onMounted(() => {
    loadData();
  });
  
  // ถ้ามี triggerProp ส่งมา ให้ watch การเปลี่ยนแปลง
  if (triggerProp !== undefined) {
    watch(() => triggerProp, (newVal, oldVal) => {
      if (newVal !== oldVal) {
        loadData();
      }
    }, { deep: true });
  }
  
  // ============ DATA LOADING FUNCTIONS ============
  const loadData = () => {
    isLoading.value = true;
    
    try {
      if (initialRisks && initialRisks.length > 0) {
        data.value = [...initialRisks];
      } else if (page.props.risks) {
        data.value = [...page.props.risks as DivisionRisk[]];
      } else {
        console.warn('ไม่พบข้อมูลความเสี่ยงฝ่ายจากทั้ง props และ Inertia page');
        data.value = [];
      }
      
      console.log('📊 โหลดข้อมูลความเสี่ยงฝ่ายสำเร็จ', {
        count: data.value.length,
        source: initialRisks && initialRisks.length > 0 ? 'initialRisks' : 'page.props',
        timestamp: new Date().toLocaleString('th-TH')
      });
    } finally {
      isLoading.value = false;
    }
  };
  
  // โหลดข้อมูลเอกสารแนบของความเสี่ยง
  const loadAttachments = (risk?: DivisionRisk) => {
    resetAttachmentState();
    
    if (risk?.attachments && risk.attachments.length > 0) {
      existingAttachments.value = [...risk.attachments];
      console.log('📄 โหลดข้อมูลเอกสารแนบสำเร็จ', {
        count: existingAttachments.value.length,
        risk_id: risk.id,
        risk_name: risk.risk_name
      });
    } else {
      existingAttachments.value = [];
    }
  };
  
  // ============ FORM SUBMISSION FUNCTIONS ============
  /**
   * ฟังก์ชันเพื่อส่งข้อมูลแบบฟอร์มไปยัง backend
   */
  const submitForm = (
    formData: RiskFormData, 
    riskId?: number, 
    onSuccess?: () => void
  ): Promise<any> => {
    return new Promise((resolve, reject) => {
      // ป้องกันการส่งซ้ำ
      if (isSubmitting.value) {
        reject(new Error('กำลังดำเนินการส่งข้อมูล โปรดรอสักครู่'));
        return;
      }
      
      isSubmitting.value = true;
      
      // สร้าง FormData object
      const form = new FormData();
      
      // เพิ่ม Method Spoofing สำหรับ PUT request กรณีแก้ไขข้อมูล
      if (riskId) {
        form.append('_method', 'put');
      }
      
      // เพิ่มข้อมูลหลัก
      form.append('risk_name', formData.risk_name.trim());
      form.append('description', formData.description.trim());
      
      // เพิ่มความเสี่ยงระดับองค์กรที่เกี่ยวข้อง (ถ้ามี)
      if (formData.organizational_risk_id) {
        form.append('organizational_risk_id', formData.organizational_risk_id.toString());
      }
      
      // เพิ่มไฟล์แนบและรายการไฟล์ที่ต้องการลบ
      if (selectedFiles.value.length > 0) {
        selectedFiles.value.forEach((file, index) => {
          form.append(`attachments[${index}]`, file);
        });
      }
      
      if (attachmentsToDelete.value.length > 0) {
        attachmentsToDelete.value.forEach(id => {
          form.append('attachments_to_delete[]', id.toString());
        });
      }
      
      // ส่งข้อมูลไปยัง backend ด้วย Inertia
      router.post(
        riskId 
          ? route('division-risks.update', riskId) 
          : route('division-risks.store'),
        form,
        {
          forceFormData: true,
          preserveState: false,
          onSuccess: (page) => {
            // แสดงข้อความแจ้งเตือนสำเร็จ
            toast.success(riskId ? 'แก้ไขข้อมูลสำเร็จ' : 'เพิ่มข้อมูลสำเร็จ', {
              icon: CheckCircle2Icon,
              description: `ความเสี่ยง "${formData.risk_name}" ${riskId ? 'ได้รับการแก้ไข' : 'ได้รับการเพิ่ม'}เรียบร้อยแล้ว`
            });
            
            resetAttachmentState();
            if (onSuccess) onSuccess();
            resolve(page);
            isSubmitting.value = false;
          },
          onError: (errors) => {
            console.error('❌ ไม่สามารถบันทึกข้อมูลความเสี่ยงฝ่ายได้:', errors);
            reject(errors);
            isSubmitting.value = false;
          }
        }
      );
    });
  };

  // ฟังก์ชันลบข้อมูลความเสี่ยง
  const deleteRisk = async (risk: DivisionRisk): Promise<void> => {
    if (!risk || !risk.id) {
        throw new Error('ไม่พบข้อมูลที่ต้องการลบ');
    }
    
    return new Promise((resolve, reject) => {
        router.delete(route('division-risks.destroy', risk.id), {
            preserveScroll: true,
            onSuccess: () => {
                data.value = data.value.filter(item => item.id !== risk.id);
                toast.success('ลบความเสี่ยงระดับฝ่ายเรียบร้อยแล้ว');
                
                console.log('✅ ลบความเสี่ยงฝ่ายสำเร็จ', {
                    risk: risk.risk_name,
                    id: risk.id
                });
                
                resolve();
            },
            onError: (errors) => {
                if (errors.error) toast.error(errors.error);
                else toast.error('เกิดข้อผิดพลาดในการลบข้อมูล');
                
                reject(errors);
            }
        });
    });
  };

  // ฟังก์ชันสำหรับลบข้อมูลหลายรายการพร้อมกัน
  const bulkDeleteRisks = async (riskIds: number[]): Promise<void> => {
      if (!riskIds || riskIds.length === 0) {
          throw new Error('ไม่มีรายการที่ต้องการลบ');
      }
      
      return new Promise((resolve, reject) => {
          router.delete('/division-risks/bulk-destroy', {
              data: { ids: riskIds },
              preserveScroll: true,
              onSuccess: () => {
                  data.value = data.value.filter(item => !riskIds.includes(item.id));
                  toast.success(`ลบ ${riskIds.length} รายการเรียบร้อยแล้ว`);
                  resolve();
              },
              onError: (errors) => {
                  if (errors.error) toast.error(errors.error);
                  else toast.error('เกิดข้อผิดพลาดในการลบข้อมูล');
                  reject(errors);
              }
          });
      });
  };

  // ============ ATTACHMENT MANAGEMENT FUNCTIONS ============
  // รีเซ็ตสถานะของเอกสารแนบทั้งหมด
  const resetAttachmentState = () => {
    selectedFiles.value = [];
    fileNames.value = [];
    attachmentsToDelete.value = [];
    existingAttachments.value = [];
  };
  
  // เพิ่มไฟล์ที่เลือกเข้าในรายการ
  const addSelectedFiles = (files: FileList | null) => {
    if (!files || files.length === 0) return;
    
    // ตรวจสอบความถูกต้องของไฟล์ก่อนเพิ่ม
    const filesToAdd: File[] = [];
    const fileNamesToAdd: string[] = [];
    const validationResult = validateFiles(Array.from(files));
    
    if (!validationResult.valid) {
      // แสดงข้อความแจ้งเตือนข้อผิดพลาด
      toast.error('ไฟล์บางรายการไม่ถูกต้อง', {
        icon: AlertTriangleIcon,
        description: validationResult.errors.join(', ')
      });
      
      // เพิ่มเฉพาะไฟล์ที่ถูกต้อง
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase();
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedFileTypes = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.jpg', '.jpeg', '.png'];
        
        if (allowedFileTypes.includes(fileExtension) && file.size <= maxSize) {
          filesToAdd.push(file);
          fileNamesToAdd.push(file.name);
        }
      }
    } else {
      for (let i = 0; i < files.length; i++) {
        filesToAdd.push(files[i]);
        fileNamesToAdd.push(files[i].name);
      }
    }
    
    // เพิ่มไฟล์ที่ถูกต้องทั้งหมดเข้าในรายการ
    if (filesToAdd.length > 0) {
      selectedFiles.value = [...selectedFiles.value, ...filesToAdd];
      fileNames.value = [...fileNames.value, ...fileNamesToAdd];
    }
  };

  // ลบไฟล์ที่เลือกออกจากรายการ
  const removeSelectedFile = (index: number) => {
    if (index < 0 || index >= selectedFiles.value.length) {
        console.warn('ไม่พบไฟล์ที่ต้องการลบในรายการ');
        return;
    }
    
    const newSelectedFiles: File[] = [];
    const newFileNames: string[] = [];
    
    for (let i = 0; i < selectedFiles.value.length; i++) {
        if (i !== index) {
            newSelectedFiles.push(selectedFiles.value[i]);
            newFileNames.push(fileNames.value[i]);
        }
    }
    
    selectedFiles.value = newSelectedFiles;
    fileNames.value = newFileNames;
  };

  // มาร์คเอกสารแนบที่มีอยู่แล้วเพื่อลบ
  const markAttachmentForDeletion = (attachmentId: number) => {
    if (!attachmentId) return;
    
    attachmentsToDelete.value.push(attachmentId);
    
    existingAttachments.value = existingAttachments.value.filter(
        attachment => !attachmentsToDelete.value.includes(attachment.id)
    );
    
    toast.success('เอกสารถูกมาร์คให้ลบเมื่อบันทึกข้อมูล');
  };

  // เปิดไฟล์เอกสารแนบในแท็บใหม่
  const openAttachment = (url: string) => {
    if (!url) return;
    window.open(url, '_blank');
  };

  // ตรวจสอบความถูกต้องของไฟล์
  const validateFiles = (files: File[]): { valid: boolean; errors: string[] } => {
    const errors: string[] = [];
    const allowedFileTypes = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.jpg', '.jpeg', '.png'];
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    for (const file of files) {
        const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase();
        
        if (!allowedFileTypes.includes(fileExtension)) {
            errors.push(`ไฟล์ "${file.name}" มีนามสกุลไม่ถูกต้อง (รองรับ pdf, word, excel, รูปภาพ)`);
            continue;
        }
        
        if (file.size > maxSize) {
            errors.push(`ไฟล์ "${file.name}" มีขนาดใหญ่เกินไป (ไม่เกิน 10MB)`);
        }
    }
    
    return {
        valid: errors.length === 0,
        errors
    };
  };
  
  // ดึงไอคอนตามประเภทไฟล์
  const getFileIcon = (fileName: string) => {
    if (!fileName) return FileIcon;
    
    const extension = fileName.split('.').pop()?.toLowerCase();
    
    switch (extension) {
        case 'pdf':
            return FileTextIcon;
        case 'doc':
        case 'docx':
            return FileTextIcon;
        case 'xls':
        case 'xlsx':
            return FileSpreadsheetIcon;
        case 'jpg':
        case 'jpeg':
        case 'png':
            return ImageIcon;
        default:
            return FileIcon;
    }
  };

  // จัดรูปแบบขนาดไฟล์ให้อ่านง่าย
  const formatFileSize = (bytes: number): string => {
    if (!bytes || bytes === 0) return '0 B';
    
    if (bytes < 1024) {
        return bytes + ' B';
    } else if (bytes < 1024 * 1024) {
        return (bytes / 1024).toFixed(1) + ' KB';
    } else {
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
  };
  
  return {
    // ข้อมูลและสถานะ
    data,
    existingAttachments,
    selectedFiles,
    fileNames,
    attachmentsToDelete,
    isLoading,
    isSubmitting,
    
    // ฟังก์ชันต่างๆ
    loadData,
    loadAttachments,
    submitForm,
    deleteRisk,
    bulkDeleteRisks,
    resetAttachmentState,
    addSelectedFiles,
    removeSelectedFile,
    markAttachmentForDeletion,
    openAttachment,
    validateFiles,
    getFileIcon,
    formatFileSize
  };
}
