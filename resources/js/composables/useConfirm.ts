// ไฟล์: resources\js\composables\useConfirm.ts
// Composable สำหรับจัดการ confirm dialog ที่ใช้ยืนยันการทำงานสำคัญ เช่น การลบข้อมูล
import { ref, readonly } from 'vue';

// กำหนด interface สำหรับรับค่าตัวเลือกของ confirm dialog
type ConfirmOptions = {
  title: string;                   // หัวข้อของกล่องยืนยัน
  message: string;                 // ข้อความรายละเอียด
  confirmText?: string;            // ข้อความปุ่มยืนยัน (optional)
  cancelText?: string;             // ข้อความปุ่มยกเลิก (optional)
  onConfirm: () => void;           // ฟังก์ชันที่จะทำงานเมื่อกดยืนยัน
  onCancel?: () => void;           // ฟังก์ชันที่จะทำงานเมื่อกดยกเลิก (optional)
};

// สร้าง global state เพื่อให้สามารถใช้งานได้ทั้งแอปพลิเคชัน
const isOpen = ref(false);
const options = ref<ConfirmOptions | null>(null);

export function useConfirm() {
  // ฟังก์ชันสำหรับเรียกใช้ confirm dialog
  const confirm = (opts: ConfirmOptions) => {
    // ตรวจสอบความปลอดภัยเพิ่มเติมก่อนแสดง dialog
    if (!opts.title || !opts.message) {
      console.error('ConfirmDialog ต้องมี title และ message');
      return;
    }
    
    // รวมตัวเลือกที่ส่งมากับค่าเริ่มต้น
    options.value = {
      confirmText: 'ยืนยัน',         // ค่าเริ่มต้นของปุ่มยืนยัน
      cancelText: 'ยกเลิก',          // ค่าเริ่มต้นของปุ่มยกเลิก
      ...opts                         // นำค่าที่ส่งมาทับค่าเริ่มต้น
    };
    
    // เปิด dialog
    isOpen.value = true;
    
    // บันทึก log เพื่อการตรวจสอบ
    console.log('เปิด confirm dialog:', {
      title: opts.title,
      message: opts.message,
      timestamp: new Date().toLocaleString('th-TH')
    });
  };
  
  // ฟังก์ชันสำหรับจัดการเมื่อกดปุ่มยืนยัน
  const handleConfirm = () => {
    // เรียกใช้ callback onConfirm ถ้ามีการกำหนด
    if (options.value?.onConfirm) {
      options.value.onConfirm();
    }
    // ปิด dialog
    isOpen.value = false;
  };
  
  // ฟังก์ชันสำหรับจัดการเมื่อกดปุ่มยกเลิก
  const handleCancel = () => {
    // เรียกใช้ callback onCancel ถ้ามีการกำหนด
    if (options.value?.onCancel) {
      options.value.onCancel();
    }
    // ปิด dialog
    isOpen.value = false;
  };

  const isProcessing = ref(false);
  
  // ส่งคืนค่าและฟังก์ชันที่จำเป็นสำหรับใช้งาน
  return {
    isOpen: readonly(isOpen),       // สถานะการแสดง dialog (readonly เพื่อป้องกันการแก้ไขโดยตรง)
    options: readonly(options),     // ข้อมูลตัวเลือกของ dialog (readonly เพื่อป้องกันการแก้ไขโดยตรง)
    isProcessing: readonly(isProcessing),
    confirm,                        // ฟังก์ชันเปิด dialog
    handleConfirm,                  // ฟังก์ชันเมื่อยืนยัน
    handleCancel                    // ฟังก์ชันเมื่อยกเลิก
  };
}
