import { ref } from 'vue';
import { toast } from 'vue-sonner';

type ConfirmOptions = {
  title: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  onConfirm: () => void | Promise<void>;
  onCancel?: () => void;
};

const isOpen = ref(false);
const options = ref<ConfirmOptions | null>(null);
const isProcessing = ref(false);

export function useConfirm() {
  // ฟังก์ชันสำหรับเรียกใช้ confirm dialog
  const confirm = (opts: ConfirmOptions) => {
    if (!opts.title || !opts.message) {
      console.error('AlertConfirmDialog ต้องมี title และ message');
      return;
    }
    
    options.value = {
      confirmText: 'ยืนยัน',
      cancelText: 'ยกเลิก',
      ...opts
    };
    
    isOpen.value = true;
    
    console.log('เปิด alert confirm dialog:', {
      title: opts.title,
      message: opts.message,
      timestamp: new Date().toLocaleString('th-TH')
    });
  };
  
  // ฟังก์ชันสำหรับเปิด/ปิด dialog โดยตรง
  const setIsOpen = (value: boolean) => {
    isOpen.value = value;
  };
  
  const handleConfirm = async () => {
    try {
      isProcessing.value = true;
      if (options.value?.onConfirm) {
        await options.value.onConfirm();
      }
    } catch (error) {
      console.error('เกิดข้อผิดพลาดขณะดำเนินการ:', error);
      toast.error('เกิดข้อผิดพลาดขณะดำเนินการ');
    } finally {
      isProcessing.value = false;
      isOpen.value = false;
    }
  };
  
  const handleCancel = () => {
    if (options.value?.onCancel) {
      options.value.onCancel();
    }
    isOpen.value = false;
  };

  const openConfirm = (opts: ConfirmOptions) => {
    options.value = opts;
    isOpen.value = true;
  };
  
  return {
    isOpen,             // ไม่ใช้ readonly
    options,            // ไม่ใช้ readonly
    isProcessing,       // ไม่ใช้ readonly
    setIsOpen,          // เพิ่มฟังก์ชันควบคุมสถานะ
    confirm,
    handleConfirm,
    handleCancel,
    openConfirm         // ฟังก์ชันสำหรับเปิด confirm dialog
  };
}
