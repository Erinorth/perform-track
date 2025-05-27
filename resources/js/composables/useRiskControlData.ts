/**
 * ไฟล์: resources/js/composables/useRiskControlData.ts (แก้ไข)
 * คำอธิบาย: Composable function สำหรับจัดการข้อมูลการควบคุมความเสี่ยง - แก้ไข TypeScript errors
 */

import { ref, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { 
  CheckCircle2Icon, 
  FileIcon, 
  FileTextIcon, 
  ImageIcon, 
  FileSpreadsheetIcon, 
  AlertTriangleIcon,
  ShieldIcon,
  ShieldCheckIcon
} from 'lucide-vue-next';
import type { 
  RiskControl, 
  RiskControlAttachment, 
  RiskControlFormData,
  ControlType,
  ControlStatus,
  FileValidationResult
} from '@/types/risk-control';

// ฟังก์ชัน composable สำหรับจัดการข้อมูลการควบคุมความเสี่ยง
export function useRiskControlData(initialControls: RiskControl[] = [], triggerProp?: any) {
  // ============ STATE MANAGEMENT ============
  const data = ref<RiskControl[]>([]);
  const existingAttachments = ref<RiskControlAttachment[]>([]);
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
  /**
   * ฟังก์ชันโหลดข้อมูลการควบคุมความเสี่ยง
   * ดึงข้อมูลจาก props หรือ Inertia page props
   */
  const loadData = () => {
    isLoading.value = true;
    
    try {
      if (initialControls && initialControls.length > 0) {
        data.value = [...initialControls];
      } else if (page.props.controls) {
        data.value = [...page.props.controls as RiskControl[]];
      } else {
        console.warn('ไม่พบข้อมูลการควบคุมความเสี่ยงจากทั้ง props และ Inertia page');
        data.value = [];
      }
      
      console.log('🛡️ โหลดข้อมูลการควบคุมความเสี่ยงสำเร็จ', {
        count: data.value.length,
        source: initialControls && initialControls.length > 0 ? 'initialControls' : 'page.props',
        timestamp: new Date().toLocaleString('th-TH'),
        active_controls: data.value.filter(control => control.status === 'active').length,
        inactive_controls: data.value.filter(control => control.status === 'inactive').length
      });
    } catch (error) {
      console.error('❌ เกิดข้อผิดพลาดในการโหลดข้อมูลการควบคุมความเสี่ยง:', error);
      data.value = [];
    } finally {
      isLoading.value = false;
    }
  };
  
  /**
   * โหลดข้อมูลเอกสารแนบของการควบคุมความเสี่ยง
   * @param control ข้อมูลการควบคุมความเสี่ยงที่ต้องการโหลดเอกสารแนบ
   */
  const loadAttachments = (control?: RiskControl) => {
    resetAttachmentState();
    
    if (control) {
      console.log('📎 กำลังโหลดเอกสารแนบของการควบคุมความเสี่ยง:', {
        control_id: control.id,
        control_name: control.control_name,
        control_type: control.control_type
      });
      
      // ตรวจสอบว่ามีข้อมูลเอกสารแนบในรูปแบบใด
      let attachmentData: RiskControlAttachment[] | null = null;
      
      // ตรวจสอบตามลำดับความเป็นไปได้
      if (control.attachments && control.attachments.length > 0) {
        attachmentData = control.attachments;
        console.log('พบข้อมูลเอกสารแนบใน attachments:', attachmentData.length, 'ไฟล์');
      }
      
      // ถ้ามีข้อมูลเอกสารแนบ
      if (attachmentData && attachmentData.length > 0) {
        existingAttachments.value = [...attachmentData];
        
        console.log('📄 โหลดข้อมูลเอกสารแนบสำเร็จ', {
          count: existingAttachments.value.length,
          control_id: control.id,
          control_name: control.control_name,
          control_type: control.control_type
        });
      } else {
        console.log('ไม่พบข้อมูลเอกสารแนบสำหรับการควบคุมนี้', {
          control_id: control.id,
          control_name: control.control_name,
          control_type: control.control_type,
          keys: Object.keys(control),
        });
        existingAttachments.value = [];
      }
    } else {
      console.log('ไม่มีข้อมูล control ที่ส่งมาในฟังก์ชัน loadAttachments');
      existingAttachments.value = [];
    }
  };

  // ============ FORM SUBMISSION FUNCTIONS ============
  /**
   * ฟังก์ชันเพื่อส่งข้อมูลแบบฟอร์มไปยัง backend
   * @param formData ข้อมูลฟอร์มการควบคุมความเสี่ยง
   * @param controlId ID ของการควบคุมความเสี่ยง (สำหรับแก้ไข)
   * @param onSuccess callback function เมื่อบันทึกสำเร็จ
   */
  const submitForm = (
    formData: RiskControlFormData, 
    controlId?: number, 
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
      if (controlId) {
        form.append('_method', 'put');
      }
      
      // เพิ่มข้อมูลหลัก
      form.append('division_risk_id', formData.division_risk_id.toString());
      form.append('control_name', formData.control_name.trim());
      form.append('status', formData.status);
      
      // เพิ่มข้อมูลเสริม (ถ้ามี)
      if (formData.description) {
        form.append('description', formData.description.trim());
      }
      
      if (formData.owner) {
        form.append('owner', formData.owner.trim());
      }
      
      if (formData.control_type) {
        form.append('control_type', formData.control_type);
      }
      
      if (formData.implementation_details) {
        form.append('implementation_details', formData.implementation_details.trim());
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
      
      // บันทึก log ก่อนส่งข้อมูล
      console.log('🚀 กำลังส่งข้อมูลการควบคุมความเสี่ยง', {
        action: controlId ? 'update' : 'create',
        control_id: controlId || 'new',
        control_name: formData.control_name,
        control_type: formData.control_type,
        status: formData.status,
        has_attachments: selectedFiles.value.length > 0,
        attachments_to_delete: attachmentsToDelete.value.length,
        timestamp: new Date().toISOString()
      });
      
      // ส่งข้อมูลไปยัง backend ด้วย Inertia
      router.post(
        controlId 
          ? route('risk-controls.update', controlId) 
          : route('risk-controls.store'),
        form,
        {
          forceFormData: true,
          preserveState: false,
          onSuccess: (page) => {
            // แสดงข้อความแจ้งเตือนสำเร็จ
            const actionText = controlId ? 'แก้ไข' : 'เพิ่ม';
            const statusText = formData.status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน';
            
            toast.success(`${actionText}การควบคุมความเสี่ยงสำเร็จ`, {
              icon: controlId ? CheckCircle2Icon : ShieldCheckIcon,
              description: `การควบคุม "${formData.control_name}" (${statusText}) ได้รับการ${actionText}เรียบร้อยแล้ว`
            });
            
            // บันทึก log สำเร็จ
            console.log('✅ บันทึกข้อมูลการควบคุมความเสี่ยงสำเร็จ', {
              action: controlId ? 'update' : 'create',
              control_id: controlId || 'new',
              control_name: formData.control_name,
              control_type: formData.control_type,
              status: formData.status,
              timestamp: new Date().toISOString()
            });
            
            resetAttachmentState();
            if (onSuccess) onSuccess();
            resolve(page);
            isSubmitting.value = false;
          },
          onError: (errors) => {
            console.error('❌ ไม่สามารถบันทึกข้อมูลการควบคุมความเสี่ยงได้:', errors);
            
            // แสดงข้อความข้อผิดพลาดที่เหมาะสม
            if (errors.control_name) {
              toast.error('ชื่อการควบคุมไม่ถูกต้อง', {
                description: errors.control_name[0] || 'กรุณาตรวจสอบชื่อการควบคุม'
              });
            } else if (errors.division_risk_id) {
              toast.error('ความเสี่ยงระดับฝ่ายไม่ถูกต้อง', {
                description: errors.division_risk_id[0] || 'กรุณาเลือกความเสี่ยงระดับฝ่าย'
              });
            } else {
              toast.error('เกิดข้อผิดพลาดในการบันทึก', {
                description: 'กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง'
              });
            }
            
            reject(errors);
            isSubmitting.value = false;
          }
        }
      );
    });
  };

  /**
   * ฟังก์ชันลบข้อมูลการควบคุมความเสี่ยง
   * @param control ข้อมูลการควบคุมความเสี่ยงที่ต้องการลบ
   */
  const deleteControl = async (control: RiskControl): Promise<void> => {
    if (!control || !control.id) {
        throw new Error('ไม่พบข้อมูลที่ต้องการลบ');
    }
    
    return new Promise((resolve, reject) => {
        router.delete(route('risk-controls.destroy', control.id), {
            preserveScroll: true,
            onSuccess: () => {
                // อัปเดตข้อมูลใน reactive state
                data.value = data.value.filter(item => item.id !== control.id);
                
                toast.success('ลบการควบคุมความเสี่ยงเรียบร้อยแล้ว', {
                  icon: ShieldIcon,
                  description: `ลบ "${control.control_name}" เรียบร้อยแล้ว`
                });
                
                console.log('✅ ลบข้อมูลการควบคุมความเสี่ยงสำเร็จ', {
                    control_name: control.control_name,
                    control_type: control.control_type,
                    control_id: control.id,
                    timestamp: new Date().toISOString()
                });
                
                resolve();
            },
            onError: (errors) => {
                console.error('❌ เกิดข้อผิดพลาดในการลบการควบคุมความเสี่ยง:', errors);
                
                if (errors.error) {
                  toast.error(errors.error as string);
                } else {
                  toast.error('เกิดข้อผิดพลาดในการลบข้อมูล');
                }
                
                reject(errors);
            }
        });
    });
  };

  /**
   * ฟังก์ชันสำหรับลบข้อมูลหลายรายการพร้อมกัน
   * @param controlIds รายการ ID ของการควบคุมความเสี่ยงที่ต้องการลบ
   */
  const bulkDeleteControls = async (controlIds: number[]): Promise<void> => {
      if (!controlIds || controlIds.length === 0) {
          throw new Error('ไม่มีรายการที่ต้องการลบ');
      }
      
      return new Promise((resolve, reject) => {
          router.delete(route('risk-controls.bulk-destroy'), {
              data: { ids: controlIds },
              preserveScroll: true,
              onSuccess: () => {
                  // อัปเดตข้อมูลใน reactive state
                  data.value = data.value.filter(item => !controlIds.includes(item.id));
                  
                  toast.success(`ลบการควบคุมความเสี่ยง ${controlIds.length} รายการเรียบร้อยแล้ว`, {
                    icon: ShieldIcon
                  });
                  
                  console.log('✅ ลบข้อมูลการควบคุมความเสี่ยงหลายรายการสำเร็จ', {
                    deleted_count: controlIds.length,
                    deleted_ids: controlIds,
                    timestamp: new Date().toISOString()
                  });
                  
                  resolve();
              },
              onError: (errors) => {
                  console.error('❌ เกิดข้อผิดพลาดในการลบหลายรายการ:', errors);
                  
                  if (errors.error) {
                    toast.error(errors.error as string);
                  } else {
                    toast.error('เกิดข้อผิดพลาดในการลบข้อมูล');
                  }
                  
                  reject(errors);
              }
          });
      });
  };

  /**
   * ฟังก์ชันเปลี่ยนสถานะการควบคุมความเสี่ยง
   * @param control ข้อมูลการควบคุมความเสี่ยงที่ต้องการเปลี่ยนสถานะ
   */
  const toggleControlStatus = async (control: RiskControl): Promise<void> => {
    if (!control || !control.id) {
        throw new Error('ไม่พบข้อมูลที่ต้องการเปลี่ยนสถานะ');
    }
    
    const newStatus: ControlStatus = control.status === 'active' ? 'inactive' : 'active';
    const statusText = newStatus === 'active' ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
    
    return new Promise((resolve, reject) => {
        router.patch(route('risk-controls.toggle-status', control.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // อัปเดตข้อมูลใน reactive state
                const index = data.value.findIndex(item => item.id === control.id);
                if (index > -1) {
                    data.value[index].status = newStatus;
                }
                
                toast.success(`${statusText}การควบคุมความเสี่ยงเรียบร้อยแล้ว`, {
                  icon: newStatus === 'active' ? ShieldCheckIcon : ShieldIcon,
                  description: `"${control.control_name}" ถูก${statusText}แล้ว`
                });
                
                console.log('✅ เปลี่ยนสถานะการควบคุมความเสี่ยงสำเร็จ', {
                    control_id: control.id,
                    control_name: control.control_name,
                    old_status: control.status,
                    new_status: newStatus,
                    timestamp: new Date().toISOString()
                });
                
                resolve();
            },
            onError: (errors) => {
                console.error('❌ เกิดข้อผิดพลาดในการเปลี่ยนสถานะ:', errors);
                
                if (errors.error) {
                  toast.error(errors.error as string);
                } else {
                  toast.error('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ');
                }
                
                reject(errors);
            }
        });
    });
  };

  /**
   * ฟังก์ชันรีเฟรชข้อมูล
   */
  const refreshData = async (): Promise<void> => {
    return new Promise((resolve, reject) => {
      router.reload({
        only: ['controls', 'statistics'],
        onSuccess: (page) => {
          if (page.props.controls) {
            data.value = page.props.controls as RiskControl[];
          }
          
          console.log('🔄 รีเฟรชข้อมูลการควบคุมความเสี่ยงสำเร็จ', {
            count: data.value.length,
            timestamp: new Date().toISOString()
          });
          
          resolve();
        },
        onError: (errors) => {
          console.error('❌ เกิดข้อผิดพลาดในการรีเฟรชข้อมูล:', errors);
          reject(errors);
        }
      });
    });
  };

  // ============ ATTACHMENT MANAGEMENT FUNCTIONS ============
  /**
   * รีเซ็ตสถานะของเอกสารแนบทั้งหมด
   */
  const resetAttachmentState = () => {
    selectedFiles.value = [];
    fileNames.value = [];
    attachmentsToDelete.value = [];
    existingAttachments.value = [];
    
    console.log('🧹 รีเซ็ตสถานะเอกสารแนบเรียบร้อยแล้ว');
  };
  
  /**
   * เพิ่มไฟล์ที่เลือกเข้าในรายการ
   * @param files รายการไฟล์ที่เลือก
   */
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
      
      console.log('📎 เพิ่มไฟล์แนบสำเร็จ', {
        added_count: filesToAdd.length,
        total_count: selectedFiles.value.length,
        file_names: fileNamesToAdd
      });
    }
  };

  /**
   * ลบไฟล์ที่เลือกออกจากรายการ
   * @param index ตำแหน่งของไฟล์ในรายการ
   */
  const removeSelectedFile = (index: number) => {
    if (index < 0 || index >= selectedFiles.value.length) {
        console.warn('ไม่พบไฟล์ที่ต้องการลบในรายการ');
        return;
    }
    
    const removedFileName = fileNames.value[index];
    
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
    
    console.log('🗑️ ลบไฟล์แนบออกจากรายการ', {
      removed_file: removedFileName,
      remaining_count: selectedFiles.value.length
    });
  };

  /**
   * มาร์คเอกสารแนบที่มีอยู่แล้วเพื่อลบ
   * @param attachmentId ID ของเอกสารแนบ
   */
  const markAttachmentForDeletion = (attachmentId: number) => {
    if (!attachmentId) return;
    
    attachmentsToDelete.value.push(attachmentId);
    
    const removedAttachment = existingAttachments.value.find(att => att.id === attachmentId);
    
    existingAttachments.value = existingAttachments.value.filter(
        attachment => !attachmentsToDelete.value.includes(attachment.id)
    );
    
    toast.success('เอกสารถูกมาร์คให้ลบเมื่อบันทึกข้อมูล');
    
    console.log('🗑️ มาร์คเอกสารแนับเพื่อลบ', {
      attachment_id: attachmentId,
      attachment_name: removedAttachment?.filename || 'unknown',
      total_marked: attachmentsToDelete.value.length
    });
  };

  /**
   * เปิดไฟล์เอกสารแนบในแท็บใหม่
   * @param url URL ของไฟล์
   */
  const openAttachment = (url: string) => {
    if (!url) return;
    
    window.open(url, '_blank');
    
    console.log('🔗 เปิดเอกสารแนบ', {
      url: url,
      timestamp: new Date().toISOString()
    });
  };

  /**
   * ตรวจสอบความถูกต้องของไฟล์
   * @param files รายการไฟล์ที่ต้องการตรวจสอบ
   */
  const validateFiles = (files: File[]): FileValidationResult => {
    const errors: string[] = [];
    const validFiles: File[] = [];
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
            continue;
        }
        
        validFiles.push(file);
    }
    
    return {
        valid: errors.length === 0,
        errors,
        validFiles
    };
  };
  
  /**
   * ดึงไอคอนตามประเภทไฟล์
   * @param fileName ชื่อไฟล์
   */
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

  /**
   * จัดรูปแบบขนาดไฟล์ให้อ่านง่าย
   * @param bytes ขนาดไฟล์ในหน่วย bytes
   */
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

  /**
   * ได้รับป้ายกำกับประเภทการควบคุม
   * @param controlType ประเภทการควบคุม
   */
  const getControlTypeLabel = (controlType?: ControlType): string => {
    const labels: Record<ControlType, string> = {
      'preventive': 'การป้องกัน',
      'detective': 'การตรวจจับ',
      'corrective': 'การแก้ไข',
      'compensating': 'การชดเชย'
    };
    return controlType ? labels[controlType] : 'ไม่ระบุ';
  };

  /**
   * ได้รับป้ายกำกับสถานะ
   * @param status สถานะ
   */
  const getStatusLabel = (status: ControlStatus): string => {
    return status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน';
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
    deleteControl,
    bulkDeleteControls,
    toggleControlStatus,
    refreshData,
    resetAttachmentState,
    addSelectedFiles,
    removeSelectedFile,
    markAttachmentForDeletion,
    openAttachment,
    validateFiles,
    getFileIcon,
    formatFileSize,
    getControlTypeLabel,
    getStatusLabel
  };
}
