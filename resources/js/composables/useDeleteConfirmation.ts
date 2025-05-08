import { useConfirm } from '@/composables/useConfirm';
import { toast } from 'vue-sonner';

/**
 * Composable สำหรับจัดการ confirmation dialog ในการลบข้อมูล
 * สามารถใช้ได้กับข้อมูลทุกประเภทในระบบ
 */
export function useDeleteConfirmation() {
  /**
   * แสดง dialog ยืนยันการลบข้อมูลเดี่ยว
   */
  const confirmDelete = async <T extends { id: number }>(
    item: T, 
    itemName: string, 
    deleteFunction: (item: T) => Promise<void>
  ) => {
    useConfirm().confirm({
      title: 'ยืนยันการลบ',
      message: `คุณต้องการลบ "${itemName}" ใช่หรือไม่?`,
      confirmText: 'ลบข้อมูล',
      cancelText: 'ยกเลิก',
      onConfirm: async () => {
        try {
          // บันทึก log สำหรับการตรวจสอบ
          console.log(`ยืนยันการลบข้อมูล: ${itemName} (ID: ${item.id})`);
          
          await deleteFunction(item);
          toast.success('ลบข้อมูลเรียบร้อยแล้ว');
        } catch (error: any) {
          console.error('เกิดข้อผิดพลาดในการลบข้อมูล:', error);
          toast.error(`เกิดข้อผิดพลาดในการลบข้อมูล: ${error.message}`);
        }
      },
      onCancel: () => {
        // บันทึก log สำหรับการตรวจสอบ
        console.log(`ยกเลิกการลบข้อมูล: ${itemName}`);
      }
    });
  };

  /**
   * แสดง dialog ยืนยันการลบข้อมูลหลายรายการ
   */
  const confirmBulkDelete = async (
    selectedIds: number[],
    itemTypeName: string,
    bulkDeleteFunction: (ids: number[]) => Promise<void>
  ) => {
    useConfirm().confirm({
      title: 'ยืนยันการลบข้อมูล',
      message: `คุณต้องการลบ${itemTypeName}ที่เลือกทั้ง ${selectedIds.length} รายการใช่หรือไม่?
                หมายเหตุ: การลบข้อมูลจะไม่สามารถเรียกคืนได้`,
      confirmText: 'ลบข้อมูล',
      cancelText: 'ยกเลิก',
      onConfirm: async () => {
        try {
          // บันทึก log สำหรับการตรวจสอบ
          console.log(`ยืนยันการลบข้อมูล ${selectedIds.length} รายการ:`, selectedIds);
          
          await bulkDeleteFunction(selectedIds);
          toast.success(`ลบข้อมูล ${selectedIds.length} รายการเรียบร้อยแล้ว`);
        } catch (error: any) {
          console.error('เกิดข้อผิดพลาดในการลบข้อมูล:', error);
          toast.error(`เกิดข้อผิดพลาดในการลบข้อมูล: ${error.message}`);
        }
      }
    });
  };

  return {
    confirmDelete,
    confirmBulkDelete
  };
}
