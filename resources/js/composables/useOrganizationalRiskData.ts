/* 
  ไฟล์: resources/js/composables/useOrganizationalRiskData.ts
  คำอธิบาย: Composable function สำหรับจัดการข้อมูลความเสี่ยงระดับองค์กร
  ทำหน้าที่: แยกโลจิกการจัดการข้อมูลออกจาก component เพื่อเพิ่มความเป็นระเบียบ
  ใช้ร่วมกับ: OrganizationalRiskController.php ในฝั่ง Backend
*/

import { ref, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { CheckCircle2Icon, FileIcon, FileTextIcon, ImageIcon, FileSpreadsheetIcon } from 'lucide-vue-next';
import type { OrganizationalRisk, Attachment } from '@/types/types';

// ฟังก์ชัน composable สำหรับจัดการข้อมูลความเสี่ยงระดับองค์กร
export function useOrganizationalRiskData(initialRisks: OrganizationalRisk[], triggerProp?: any) {
    // สร้าง reactive references สำหรับเก็บข้อมูล
    const data = ref<OrganizationalRisk[]>([]);
    const existingAttachments = ref<Attachment[]>([]);
    const attachmentsToDelete = ref<number[]>([]);
    const selectedFiles = ref<File[]>([]);
    const fileNames = ref<string[]>([]);
    
    // ใช้ usePage hook ของ Inertia เพื่อเข้าถึงข้อมูลจาก props
    const page = usePage();

    // ทำงานเมื่อ component ถูก mount
    onMounted(() => {
        loadData();
    });
    
    // ถ้ามี triggerProp ส่งมา ให้ watch การเปลี่ยนแปลงและอัปเดตข้อมูล
    if (triggerProp !== undefined) {
        watch(() => triggerProp, () => {
            loadData();
        }, { deep: true });
    }
    
    // ฟังก์ชันสำหรับโหลดข้อมูลเริ่มต้น
    const loadData = () => {
        if (initialRisks && initialRisks.length > 0) {
            data.value = [...initialRisks];
        } else if (page.props.risks) {
            data.value = [...page.props.risks as OrganizationalRisk[]];
        } else {
            console.warn('ไม่พบข้อมูลความเสี่ยงองค์กรจากทั้ง props และ Inertia page');
            data.value = [];
        }
        
        console.log('📊 โหลดข้อมูลความเสี่ยงองค์กรสำเร็จ', {
            count: data.value.length,
            source: initialRisks && initialRisks.length > 0 ? 'initialRisks' : 'page.props',
            timestamp: new Date().toLocaleString('th-TH')
        });
    };
    
    // ฟังก์ชันสำหรับโหลดข้อมูลเอกสารแนบของความเสี่ยง
    const loadAttachments = (risk?: OrganizationalRisk) => {
        // รีเซ็ตรายการเอกสารแนบ
        attachmentsToDelete.value = [];
        selectedFiles.value = [];
        fileNames.value = [];
        
        // ถ้ามีข้อมูลความเสี่ยงและมีเอกสารแนบ
        if (risk?.attachments) {
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

    // สร้าง FormData จากข้อมูลที่ต้องการบันทึก
    const createFormData = (
        formData: { risk_name: string; description: string; },
        files?: File[],
        attachmentsToDelete?: number[]
    ) => {
        const form = new FormData();

        // เพิ่มข้อมูลพื้นฐาน
        if (formData.risk_name) form.append('risk_name', formData.risk_name);
        if (formData.description) form.append('description', formData.description);
        
        // เพิ่มไฟล์แนบ (ถ้ามี)
        if (files && files.length > 0) {
            files.forEach((file, index) => {
                if (file instanceof File) {
                    form.append(`attachments[${index}]`, file);
                    console.log(`เพิ่มไฟล์ ${file.name} ขนาด ${formatFileSize(file.size)}`);
                }
            });
        }
        
        // เพิ่มรายการไฟล์ที่ต้องการลบ
        if (attachmentsToDelete && attachmentsToDelete.length > 0) {
            attachmentsToDelete.forEach(id => {
                form.append('attachments_to_delete[]', id.toString());
            });
        }
        
        return form;
    };

    // ฟังก์ชันเพื่อส่งข้อมูลแบบฟอร์มไปยัง backend
    const submitForm = async (
        formData: { risk_name: string; description: string; },
        riskId?: number,
        onSuccess?: () => void
    ) => {
        const form = createFormData(
            formData,
            selectedFiles.value,
            attachmentsToDelete.value
        );

        console.log('📝 ข้อมูลที่กำลังส่ง:', {
            mode: riskId ? 'แก้ไข' : 'เพิ่มใหม่',
            riskId,
            hasAttachments: selectedFiles.value.length > 0,
            attachmentsToDelete: attachmentsToDelete.value.length
        });

        return new Promise((resolve, reject) => {
            if (riskId) {
                // กรณีแก้ไข
                router.put(route('organizational-risks.update', riskId), form, {
                    forceFormData: true,
                    preserveState: false,
                    onSuccess: (page) => {
                        // อัปเดตข้อมูลใน data array
                        const index = data.value.findIndex(item => item.id === riskId);
                        if (index !== -1) {
                            data.value[index] = { 
                                ...data.value[index], 
                                risk_name: formData.risk_name,
                                description: formData.description,
                            };
                            data.value = [...data.value];
                        }
                        
                        toast.success('บันทึกข้อมูลความเสี่ยงสำเร็จ', {
                            icon: CheckCircle2Icon,
                            description: `ความเสี่ยง "${formData.risk_name}" ได้รับการแก้ไขเรียบร้อยแล้ว`
                        });
                        
                        resetAttachmentState();
                        if (onSuccess) onSuccess();
                        resolve(page);
                    },
                    onError: (errors) => {
                        console.error('❌ ไม่สามารถอัปเดตความเสี่ยงองค์กรได้:', errors);
                        reject(errors);
                    }
                });
            } else {
                // กรณีเพิ่มใหม่
                router.post('/organizational-risks', form, {
                    forceFormData: true,
                    onSuccess: (page) => {
                        if (page.props.risk && typeof page.props.risk === 'object' && 'id' in page.props.risk) {
                            const newRisk = page.props.risk as OrganizationalRisk;
                            data.value.push(newRisk);
                            data.value = [...data.value];
                        }
                        
                        toast.success('เพิ่มความเสี่ยงสำเร็จ', {
                            icon: CheckCircle2Icon,
                            description: `ความเสี่ยง "${formData.risk_name}" ถูกเพิ่มเรียบร้อยแล้ว`
                        });
                        
                        resetAttachmentState();
                        if (onSuccess) onSuccess();
                        resolve(page);
                    },
                    onError: (errors) => {
                        console.error('❌ ไม่สามารถเพิ่มความเสี่ยงองค์กรได้:', errors);
                        reject(errors);
                    }
                });
            }
        });
    };

    // ฟังก์ชันลบข้อมูลความเสี่ยง
    const deleteRisk = async (risk: OrganizationalRisk): Promise<void> => {
        if (!risk || !risk.id) {
            throw new Error('ไม่พบข้อมูลที่ต้องการลบ');
        }
        
        return new Promise((resolve, reject) => {
            router.delete(route('organizational-risks.destroy', risk.id), {
                preserveScroll: true,
                onSuccess: () => {
                    data.value = data.value.filter(item => item.id !== risk.id);
                    toast.success('ลบความเสี่ยงระดับองค์กรเรียบร้อยแล้ว');
                    
                    console.log('✅ ลบความเสี่ยงองค์กรสำเร็จ', {
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
            router.delete('/organizational-risks/bulk-destroy', {
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

    // ========== ฟังก์ชันจัดการเอกสารแนบ ==========
    
    // รีเซ็ตสถานะของเอกสารแนบ
    const resetAttachmentState = () => {
        selectedFiles.value = [];
        fileNames.value = [];
        attachmentsToDelete.value = [];
        existingAttachments.value = [];
    };
    
    // เพิ่มไฟล์ที่เลือกเข้าในรายการ
    const addSelectedFiles = (files: FileList | null) => {
        if (!files || files.length === 0) return;
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            selectedFiles.value.push(file);
            fileNames.value.push(file.name);
        }
        
        console.log('📄 เพิ่มไฟล์เข้ารายการอัปโหลด:', {
            count: files.length,
            total: selectedFiles.value.length
        });
    };
    
    // ลบไฟล์ที่เลือกออกจากรายการ
    const removeSelectedFile = (index: number) => {
        if (index < 0 || index >= selectedFiles.value.length) {
            console.warn('ไม่พบไฟล์ที่ต้องการลบในรายการ');
            return;
        }
        
        const removedFile = selectedFiles.value[index];
        
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
    
    // ส่งคืนข้อมูลและฟังก์ชันที่ต้องการให้ component อื่นใช้งาน
    return {
        // ข้อมูลความเสี่ยงและเอกสารแนบ
        data,
        existingAttachments,
        selectedFiles,
        fileNames,
        attachmentsToDelete,
        
        // ฟังก์ชันจัดการข้อมูลหลัก
        loadData,
        loadAttachments,
        submitForm,
        deleteRisk,
        bulkDeleteRisks,
        
        // ฟังก์ชันจัดการเอกสารแนบ
        resetAttachmentState,
        addSelectedFiles,
        removeSelectedFile,
        markAttachmentForDeletion,
        openAttachment,
        validateFiles,
        
        // ฟังก์ชันช่วยเหลือ UI
        getFileIcon,
        formatFileSize
    };
}
