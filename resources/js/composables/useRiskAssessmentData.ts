/* 
  ไฟล์: resources/js/composables/useRiskAssessmentData.ts
  คำอธิบาย: Composable function สำหรับจัดการข้อมูลการประเมินความเสี่ยง
  ทำหน้าที่: แยกโลจิกการจัดการข้อมูลออกจาก component เพื่อเพิ่มความเป็นระเบียบ
  หลักการ: แบ่งโลจิกเป็นหมวดหมู่ - การโหลดข้อมูล, การจัดการฟอร์ม, การจัดการไฟล์แนบ
  ใช้ร่วมกับ: RiskAssessmentController.php ในฝั่ง Backend
*/

import { ref, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { CheckCircle2Icon, FileIcon, FileTextIcon, ImageIcon, FileSpreadsheetIcon, AlertTriangleIcon } from 'lucide-vue-next';
import type { RiskAssessment, RiskAssessmentAttachment } from '@/types/types';

// นิยาม interface สำหรับข้อมูลฟอร์ม
interface AssessmentFormData {
  assessment_date: string;
  likelihood_level: number;
  impact_level: number;
  risk_score?: number;
  division_risk_id?: number | null;
  notes?: string;
  attachments?: File[] | null;
}

// ฟังก์ชัน composable สำหรับจัดการข้อมูลการประเมินความเสี่ยง
export function useRiskAssessmentData(initialAssessments: RiskAssessment[] = [], triggerProp?: any) {
  // ============ STATE MANAGEMENT ============
  const data = ref<RiskAssessment[]>([]);
  const existingAttachments = ref<RiskAssessmentAttachment[]>([]);
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
      if (initialAssessments && initialAssessments.length > 0) {
        data.value = [...initialAssessments];
      } else if (page.props.assessments) {
        data.value = [...page.props.assessments as RiskAssessment[]];
      } else {
        console.warn('ไม่พบข้อมูลการประเมินความเสี่ยงจากทั้ง props และ Inertia page');
        data.value = [];
      }
      
      console.log('📊 โหลดข้อมูลการประเมินความเสี่ยงสำเร็จ', {
        count: data.value.length,
        source: initialAssessments && initialAssessments.length > 0 ? 'initialAssessments' : 'page.props',
        timestamp: new Date().toLocaleString('th-TH')
      });
    } finally {
      isLoading.value = false;
    }
  };
  
  // โหลดข้อมูลเอกสารแนบของการประเมินความเสี่ยง
  const loadAttachments = (assessment?: RiskAssessment) => {
    resetAttachmentState();
    
    if (assessment?.attachments && assessment.attachments.length > 0) {
      existingAttachments.value = [...assessment.attachments];
      console.log('📄 โหลดข้อมูลเอกสารแนบสำเร็จ', {
        count: existingAttachments.value.length,
        assessment_id: assessment.id,
        assessment_date: assessment.assessment_date
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
    formData: AssessmentFormData, 
    assessmentId?: number, 
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
      if (assessmentId) {
        form.append('_method', 'put');
      }
      
      // เพิ่มข้อมูลหลัก
      form.append('assessment_date', formData.assessment_date);
      form.append('likelihood_level', formData.likelihood_level.toString());
      form.append('impact_level', formData.impact_level.toString());
      
      // เพิ่มบันทึกเพิ่มเติม (ถ้ามี)
      if (formData.notes) {
        form.append('notes', formData.notes.trim());
      }
      
      // เพิ่มความเสี่ยงระดับฝ่ายที่เกี่ยวข้อง (ถ้ามี)
      if (formData.division_risk_id) {
        form.append('division_risk_id', formData.division_risk_id.toString());
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
        assessmentId 
          ? route('risk-assessments.update', assessmentId) 
          : route('risk-assessments.store'),
        form,
        {
          forceFormData: true,
          preserveState: false,
          onSuccess: (page) => {
            // แสดงข้อความแจ้งเตือนสำเร็จ
            const formattedDate = new Date(formData.assessment_date).toLocaleDateString('th-TH');
            toast.success(assessmentId ? 'แก้ไขข้อมูลสำเร็จ' : 'เพิ่มข้อมูลสำเร็จ', {
              icon: CheckCircle2Icon,
              description: `การประเมินความเสี่ยงวันที่ ${formattedDate} ${assessmentId ? 'ได้รับการแก้ไข' : 'ได้รับการเพิ่ม'}เรียบร้อยแล้ว`
            });
            
            resetAttachmentState();
            if (onSuccess) onSuccess();
            resolve(page);
            isSubmitting.value = false;
          },
          onError: (errors) => {
            console.error('❌ ไม่สามารถบันทึกข้อมูลการประเมินความเสี่ยงได้:', errors);
            reject(errors);
            isSubmitting.value = false;
          }
        }
      );
    });
  };

  // ฟังก์ชันลบข้อมูลการประเมินความเสี่ยง
  const deleteAssessment = async (assessment: RiskAssessment): Promise<void> => {
    if (!assessment || !assessment.id) {
        throw new Error('ไม่พบข้อมูลที่ต้องการลบ');
    }
    
    return new Promise((resolve, reject) => {
        router.delete(route('risk-assessments.destroy', assessment.id), {
            preserveScroll: true,
            onSuccess: () => {
                data.value = data.value.filter(item => item.id !== assessment.id);
                toast.success('ลบข้อมูลการประเมินความเสี่ยงเรียบร้อยแล้ว');
                
                console.log('✅ ลบข้อมูลการประเมินความเสี่ยงสำเร็จ', {
                    assessment_date: new Date(assessment.assessment_date).toLocaleDateString('th-TH'),
                    id: assessment.id
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
  const bulkDeleteAssessments = async (assessmentIds: number[]): Promise<void> => {
      if (!assessmentIds || assessmentIds.length === 0) {
          throw new Error('ไม่มีรายการที่ต้องการลบ');
      }
      
      return new Promise((resolve, reject) => {
          router.delete('/risk-assessments/bulk-destroy', {
              data: { ids: assessmentIds },
              preserveScroll: true,
              onSuccess: () => {
                  data.value = data.value.filter(item => !assessmentIds.includes(item.id));
                  toast.success(`ลบ ${assessmentIds.length} รายการเรียบร้อยแล้ว`);
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
    deleteAssessment,
    bulkDeleteAssessments,
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
