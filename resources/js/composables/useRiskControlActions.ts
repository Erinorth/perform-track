/**
 * ไฟล์: resources\js\composables\useRiskControlActions.ts
 * คำอธิบาย: Composable สำหรับจัดการ action ต่างๆ ของการควบคุมความเสี่ยง
 * ทำหน้าที่: รวมฟังก์ชันการเพิ่ม แก้ไข ลบ และจัดการสถานะของการควบคุมความเสี่ยง
 * ใช้งาน: ใน Vue components ที่เกี่ยวข้องกับการจัดการการควบคุมความเสี่ยง
 */

// นำเข้าตัวแปรและฟังก์ชันที่จำเป็น
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { RiskControl } from '@/types/risk-control';
import { useRiskControlData } from '@/composables/useRiskControlData';
import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation';

/**
 * Composable สำหรับจัดการ action ต่างๆ ของการควบคุมความเสี่ยง
 * รวมฟังก์ชันการเพิ่ม แก้ไข ลบ และเปลี่ยนสถานะข้อมูล
 * 
 * @param initialControls รายการการควบคุมความเสี่ยงเริ่มต้น
 * @returns ออบเจ็กต์ที่ประกอบด้วยข้อมูลและฟังก์ชันต่างๆ
 */
export function useRiskControlActions(initialControls: RiskControl[]) {
  // ใช้ composable สำหรับจัดการข้อมูลการควบคุมความเสี่ยง
  const { 
    data, 
    deleteControl, 
    bulkDeleteControls, 
    toggleControlStatus,
    refreshData 
  } = useRiskControlData(initialControls);

  // ตัวแปรสำหรับจัดการสถานะ Modal และข้อมูลที่กำลังแก้ไข
  const showModal = ref(false);
  const showEditModal = ref(false);
  const currentControl = ref<RiskControl | undefined>(undefined);
  
  // ตัวแปรสำหรับจัดการการเลือกรายการหลายรายการ
  const selectedItems = ref<number[]>([]);
  const isProcessing = ref(false);

  /**
   * ฟังก์ชันเปิด Modal สำหรับเพิ่มการควบคุมความเสี่ยงใหม่
   * รีเซ็ตข้อมูลการแก้ไขและเปิด Modal เพิ่มข้อมูล
   */
  const openCreateModal = () => {
    try {
      // รีเซ็ตข้อมูลเก่า
      currentControl.value = undefined;
      showModal.value = true;
      showEditModal.value = false;
      
      // บันทึก log เพื่อการตรวจสอบ
      console.log('เปิด Modal เพื่อเพิ่มการควบคุมความเสี่ยงใหม่', {
        timestamp: new Date().toISOString(),
        action: 'open_create_modal'
      });
      
      // แสดงข้อความแจ้งเตือน
      toast.info('กำลังเปิดฟอร์มเพิ่มการควบคุมความเสี่ยงใหม่');
      
    } catch (error) {
      console.error('เกิดข้อผิดพลาดในการเปิด Modal เพิ่มข้อมูล:', error);
      toast.error('เกิดข้อผิดพลาดในการเปิดฟอร์ม');
    }
  };

  /**
   * ฟังก์ชันเปิด Modal สำหรับแก้ไขการควบคุมความเสี่ยง
   * ตั้งค่าข้อมูลที่ต้องการแก้ไขและเปิด Modal
   * 
   * @param control ข้อมูลการควบคุมความเสี่ยงที่ต้องการแก้ไข
   */
  const openEditModal = (control: RiskControl) => {
    try {
      // ตรวจสอบความถูกต้องของข้อมูลที่ส่งเข้ามา
      if (!control || !control.id) {
        throw new Error('ข้อมูลการควบคุมความเสี่ยงไม่ถูกต้อง');
      }

      // ตั้งค่าข้อมูลสำหรับการแก้ไข
      currentControl.value = { ...control }; // สร้าง copy เพื่อป้องกันการแก้ไขข้อมูลต้นฉบับ
      showEditModal.value = true;
      showModal.value = false;
      
      // บันทึก log เพื่อการตรวจสอบ
      console.log('เปิด Modal เพื่อแก้ไขการควบคุมความเสี่ยง', {
        control_id: control.id,
        control_name: control.control_name,
        control_type: control.control_type,
        status: control.status,
        timestamp: new Date().toISOString(),
        action: 'open_edit_modal'
      });
      
      // แสดงข้อความแจ้งเตือน
      toast.info(`กำลังเปิดฟอร์มแก้ไข: ${control.control_name}`);
      
    } catch (error) {
      console.error('เกิดข้อผิดพลาดในการเปิด Modal แก้ไข:', error);
      toast.error('เกิดข้อผิดพลาดในการเปิดฟอร์มแก้ไข');
    }
  };

  /**
   * ฟังก์ชันปิด Modal ทั้งหมด
   * รีเซ็ตสถานะต่างๆ กลับเป็นค่าเริ่มต้น
   */
  const closeModal = () => {
    showModal.value = false;
    showEditModal.value = false;
    currentControl.value = undefined;
    
    // บันทึก log
    console.log('ปิด Modal การควบคุมความเสี่ยง', {
      timestamp: new Date().toISOString(),
      action: 'close_modal'
    });
  };

  /**
   * ฟังก์ชันจัดการเมื่อบันทึกข้อมูลสำเร็จ
   * รีเฟรชข้อมูลและปิด Modal
   */
  const handleSaved = async () => {
    try {
      // ปิด Modal ทั้งหมด
      closeModal();
      
      // รีเฟรชข้อมูลหน้าปัจจุบัน
      await router.visit(route('risk-controls.index'), {
        preserveScroll: true,
        only: ['controls', 'statistics'],
        onSuccess: () => {
          // รีเฟรชข้อมูลใน composable
          refreshData();
          
          // แสดงข้อความสำเร็จ
          toast.success('บันทึกข้อมูลการควบคุมความเสี่ยงเรียบร้อยแล้ว');
          
          // บันทึก log
          console.log('บันทึกข้อมูลการควบคุมความเสี่ยงสำเร็จ', {
            timestamp: new Date().toISOString(),
            action: 'save_success'
          });
        },
        onError: (errors) => {
          console.error('เกิดข้อผิดพลาดในการรีเฟรชข้อมูล:', errors);
          toast.error('เกิดข้อผิดพลาดในการรีเฟรชข้อมูล');
        }
      });
      
    } catch (error) {
      console.error('เกิดข้อผิดพลาดหลังจากบันทึกข้อมูล:', error);
      toast.error('เกิดข้อผิดพลาดหลังจากบันทึกข้อมูล');
    }
  };

  // ใช้ composable สำหรับการยืนยันการลบ
  const { confirmDelete, confirmBulkDelete } = useDeleteConfirmation();

  /**
   * ฟังก์ชันสำหรับลบการควบคุมความเสี่ยงรายการเดียว
   * แสดง dialog ยืนยันก่อนลบข้อมูล
   * 
   * @param control ข้อมูลการควบคุมความเสี่ยงที่ต้องการลบ
   */
  const handleDelete = async (control: RiskControl) => {
    try {
      // ตรวจสอบความถูกต้องของข้อมูล
      if (!control || !control.id) {
        throw new Error('ข้อมูลการควบคุมความเสี่ยงไม่ถูกต้อง');
      }

      // สร้างป้ายกำกับสำหรับการแสดงผล
      const label = `${control.control_name}`;
      const controlType = control.control_type ? ` (${getControlTypeLabel(control.control_type)})` : '';
      const fullLabel = `การควบคุมความเสี่ยง "${label}"${controlType}`;
      
      // เรียกใช้ฟังก์ชันยืนยันการลบ
      await confirmDelete(
        control, 
        fullLabel, 
        async (controlToDelete: RiskControl) => {
          isProcessing.value = true;
          
          try {
            await deleteControl(controlToDelete);
            
            // บันทึก log สำเร็จ
            console.log('ลบการควบคุมความเสี่ยงสำเร็จ', {
              control_id: controlToDelete.id,
              control_name: controlToDelete.control_name,
              timestamp: new Date().toISOString(),
              action: 'delete_success'
            });
            
            toast.success('ลบการควบคุมความเสี่ยงเรียบร้อยแล้ว');
            
          } catch (deleteError) {
            console.error('เกิดข้อผิดพลาดในการลบ:', deleteError);
            toast.error('เกิดข้อผิดพลาดในการลบข้อมูล');
          } finally {
            isProcessing.value = false;
          }
        }
      );
      
    } catch (error) {
      console.error('เกิดข้อผิดพลาดในกระบวนการลบ:', error);
      toast.error('เกิดข้อผิดพลาดในกระบวนการลบ');
    }
  };

  /**
   * ฟังก์ชันสำหรับลบการควบคุมความเสี่ยงหลายรายการพร้อมกัน
   * ตรวจสอบรายการที่เลือกและแสดง dialog ยืนยัน
   * 
   * @param selectedIds รายการ ID ของการควบคุมความเสี่ยงที่ต้องการลบ
   */
  const handleBulkDelete = async (selectedIds: number[]) => {
    try {
      // ตรวจสอบว่ามีรายการที่เลือกหรือไม่
      if (!selectedIds || selectedIds.length === 0) {
        toast.warning('กรุณาเลือกรายการที่ต้องการลบ');
        return;
      }

      // เรียกใช้ฟังก์ชันยืนยันการลบหลายรายการ
      await confirmBulkDelete(
        selectedIds, 
        'การควบคุมความเสี่ยง', 
        async (idsToDelete: number[]) => {
          isProcessing.value = true;
          
          try {
            await bulkDeleteControls(idsToDelete);
            
            // รีเซ็ตรายการที่เลือก
            selectedItems.value = [];
            
            // บันทึก log สำเร็จ
            console.log('ลบการควบคุมความเสี่ยงหลายรายการสำเร็จ', {
              deleted_count: idsToDelete.length,
              deleted_ids: idsToDelete,
              timestamp: new Date().toISOString(),
              action: 'bulk_delete_success'
            });
            
            toast.success(`ลบการควบคุมความเสี่ยง ${idsToDelete.length} รายการเรียบร้อยแล้ว`);
            
          } catch (deleteError) {
            console.error('เกิดข้อผิดพลาดในการลบหลายรายการ:', deleteError);
            toast.error('เกิดข้อผิดพลาดในการลบข้อมูล');
          } finally {
            isProcessing.value = false;
          }
        }
      );
      
    } catch (error) {
      console.error('เกิดข้อผิดพลาดในกระบวนการลบหลายรายการ:', error);
      toast.error('เกิดข้อผิดพลาดในกระบวนการลบ');
    }
  };

  /**
   * ฟังก์ชันสำหรับเปลี่ยนสถานะการควบคุมความเสี่ยง
   * สลับระหว่างสถานะ active และ inactive
   * 
   * @param control ข้อมูลการควบคุมความเสี่ยงที่ต้องการเปลี่ยนสถานะ
   */
  const handleToggleStatus = async (control: RiskControl) => {
    try {
      // ตรวจสอบความถูกต้องของข้อมูล
      if (!control || !control.id) {
        throw new Error('ข้อมูลการควบคุมความเสี่ยงไม่ถูกต้อง');
      }

      const newStatus = control.status === 'active' ? 'inactive' : 'active';
      const statusText = newStatus === 'active' ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
      
      // แสดง dialog ยืนยัน
      if (confirm(`คุณต้องการ${statusText}การควบคุมความเสี่ยง "${control.control_name}" หรือไม่?`)) {
        isProcessing.value = true;
        
        try {
          await toggleControlStatus(control);
          
          // บันทึก log สำเร็จ
          console.log('เปลี่ยนสถานะการควบคุมความเสี่ยงสำเร็จ', {
            control_id: control.id,
            control_name: control.control_name,
            old_status: control.status,
            new_status: newStatus,
            timestamp: new Date().toISOString(),
            action: 'toggle_status_success'
          });
          
          toast.success(`${statusText}การควบคุมความเสี่ยงเรียบร้อยแล้ว`);
          
        } catch (toggleError) {
          console.error('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ:', toggleError);
          toast.error('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ');
        } finally {
          isProcessing.value = false;
        }
      }
      
    } catch (error) {
      console.error('เกิดข้อผิดพลาดในกระบวนการเปลี่ยนสถานะ:', error);
      toast.error('เกิดข้อผิดพลาดในกระบวนการเปลี่ยนสถานะ');
    }
  };

  /**
   * ฟังก์ชันช่วยสำหรับแปลงประเภทการควบคุมเป็นป้ายกำกับ
   * 
   * @param type ประเภทการควบคุม
   * @returns ป้ายกำกับเป็นภาษาไทย
   */
  const getControlTypeLabel = (type?: string): string => {
    const labels: Record<string, string> = {
      'preventive': 'การป้องกัน',
      'detective': 'การตรวจจับ',
      'corrective': 'การแก้ไข',
      'compensating': 'การชดเชย'
    };
    return labels[type || ''] || 'ไม่ระบุ';
  };

  /**
   * ฟังก์ชันจัดการการเลือกรายการ
   * 
   * @param ids รายการ ID ที่เลือก
   */
  const handleSelectionChange = (ids: number[]) => {
    selectedItems.value = ids;
    
    // บันทึก log สำหรับการติดตาม
    console.log('เปลี่ยนการเลือกรายการ', {
      selected_count: ids.length,
      selected_ids: ids,
      timestamp: new Date().toISOString(),
      action: 'selection_change'
    });
  };

  // ส่งกลับออบเจ็กต์ที่ประกอบด้วยข้อมูลและฟังก์ชันทั้งหมด
  return {
    // ข้อมูลหลัก
    data,
    
    // สถานะ Modal
    showModal,
    showEditModal,
    currentControl,
    
    // สถานะการประมวลผล
    isProcessing,
    selectedItems,
    
    // ฟังก์ชันจัดการ Modal
    openCreateModal,
    openEditModal,
    closeModal,
    handleSaved,
    
    // ฟังก์ชันจัดการข้อมูล
    handleDelete,
    handleBulkDelete,
    handleToggleStatus,
    
    // ฟังก์ชันเสริม
    handleSelectionChange,
    getControlTypeLabel,
    refreshData
  };
}
