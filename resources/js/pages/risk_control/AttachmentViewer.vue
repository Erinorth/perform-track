<!-- ไฟล์: resources/js/pages/risk_control/AttachmentViewer.vue -->
<!-- 
  คำอธิบาย: หน้าสำหรับแสดงและจัดการไฟล์แนบของการควบคุมความเสี่ยง
  ฟีเจอร์หลัก:
  - แสดงไฟล์แนบในรูปแบบต่างๆ (PDF, รูปภาพ, เอกสาร)
  - ควบคุมการย่อขยาย (Zoom In/Out)
  - หมุนไฟล์ (Rotate)
  - ดาวน์โหลดไฟล์
  - รองรับการแสดงผลแบบ Responsive
  - รองรับไฟล์ประเภท PDF, Images, Documents
-->

<script setup lang="ts">
// ==================== นำเข้า Dependencies ที่จำเป็น ====================
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

// ==================== นำเข้า Types ====================
import type { RiskControlAttachment } from '@/types/risk-control';

// ==================== นำเข้า Components ที่สร้างไว้ ====================
import FileViewerHeader from '@/components/FileViewer/FileViewerHeader.vue';
import FileViewerControls from '@/components/FileViewer/FileViewerControls.vue';
import FileViewerContent from '@/components/FileViewer/FileViewerContent.vue';

// ==================== กำหนด Props สำหรับรับข้อมูลไฟล์แนบ ====================
const props = defineProps<{
  attachment: {
    id: number;
    filename: string;
    filepath: string;
    filetype?: string;
    filesize?: number;
    url: string;
    risk_control_id: number;
    created_at: string;
    updated_at: string;
    // ข้อมูลเพิ่มเติมของ Risk Control
    risk_control?: {
      id: number;
      control_name: string;
      control_type?: string;
      status: string;
    };
  }
}>();

// ==================== ตัวแปรสำหรับการแสดงผลและควบคุม ====================
const fileUrl = ref<string>(props.attachment.url);
const zoom = ref<number>(1);
const rotation = ref<number>(0);
const isLoading = ref<boolean>(true);
const hasError = ref<boolean>(false);
const errorMessage = ref<string>('');

// ==================== Computed Properties ====================
// กำหนดขนาดไฟล์ในรูปแบบที่อ่านง่าย
const formattedFileSize = computed(() => {
  const size = props.attachment.filesize || 0;
  if (size === 0) return 'ไม่ระบุขนาด';
  
  if (size < 1024) {
    return `${size} B`;
  } else if (size < 1024 * 1024) {
    return `${(size / 1024).toFixed(1)} KB`;
  } else {
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
  }
});

// ตรวจสอบประเภทไฟล์
const fileExtension = computed(() => {
  const filename = props.attachment.filename || '';
  return filename.split('.').pop()?.toLowerCase() || '';
});

// กำหนดไอคอนตามประเภทไฟล์
const fileIcon = computed(() => {
  const ext = fileExtension.value;
  
  if (['pdf'].includes(ext)) return 'file-text';
  if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'image';
  if (['doc', 'docx'].includes(ext)) return 'file-text';
  if (['xls', 'xlsx'].includes(ext)) return 'file-spreadsheet';
  
  return 'file';
});

// ตรวจสอบว่าไฟล์สามารถแสดงผลได้หรือไม่
const isViewableFile = computed(() => {
  const ext = fileExtension.value;
  return ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
});

// ==================== ฟังก์ชันควบคุมการแสดงผล ====================
/**
 * ฟังก์ชันย่อขยายเข้า (Zoom In)
 * เพิ่มค่า zoom สูงสุดไม่เกิน 3 เท่า
 */
const zoomIn = () => {
  const newZoom = Math.min(zoom.value + 0.25, 3);
  zoom.value = newZoom;
  
  console.log('🔍 ย่อขยายเข้า:', {
    old_zoom: zoom.value - 0.25,
    new_zoom: newZoom,
    filename: props.attachment.filename,
    timestamp: new Date().toISOString()
  });
  
  toast.success(`ขยายเป็น ${Math.round(newZoom * 100)}%`);
};

/**
 * ฟังก์ชันย่อขยายออก (Zoom Out)  
 * ลดค่า zoom ต่ำสุดไม่ต่ำกว่า 0.5 เท่า
 */
const zoomOut = () => {
  const newZoom = Math.max(zoom.value - 0.25, 0.5);
  zoom.value = newZoom;
  
  console.log('🔍 ย่อขยายออก:', {
    old_zoom: zoom.value + 0.25,
    new_zoom: newZoom,
    filename: props.attachment.filename,
    timestamp: new Date().toISOString()
  });
  
  toast.success(`ขยายเป็น ${Math.round(newZoom * 100)}%`);
};

/**
 * ฟังก์ชันรีเซ็ตการย่อขยาย
 * กลับไปที่ขนาดปกติ (100%)
 */
const resetZoom = () => {
  zoom.value = 1;
  
  console.log('🔍 รีเซ็ตการย่อขยาย:', {
    filename: props.attachment.filename,
    timestamp: new Date().toISOString()
  });
  
  toast.success('รีเซ็ตการย่อขยายเป็น 100%');
};

/**
 * ฟังก์ชันหมุนไฟล์
 * หมุนไฟล์ 90 องศาในแต่ละครั้ง
 */
const rotateFile = () => {
  const newRotation = (rotation.value + 90) % 360;
  rotation.value = newRotation;
  
  console.log('🔄 หมุนไฟล์:', {
    old_rotation: rotation.value - 90,
    new_rotation: newRotation,
    filename: props.attachment.filename,
    timestamp: new Date().toISOString()
  });
  
  toast.success(`หมุนไฟล์ ${newRotation} องศา`);
};

/**
 * ฟังก์ชันรีเซ็ตการหมุน
 * กลับไปที่ 0 องศา
 */
const resetRotation = () => {
  rotation.value = 0;
  
  console.log('🔄 รีเซ็ตการหมุน:', {
    filename: props.attachment.filename,
    timestamp: new Date().toISOString()
  });
  
  toast.success('รีเซ็ตการหมุนไฟล์');
};

// ==================== ฟังก์ชันจัดการไฟล์ ====================
/**
 * ฟังก์ชันดาวน์โหลดไฟล์
 * สร้าง URL สำหรับดาวน์โหลดและเปิดในหน้าต่างใหม่
 */
const downloadFile = () => {
  try {
    // สร้าง URL สำหรับดาวน์โหลดไฟล์แนบของการควบคุมความเสี่ยง
    const downloadUrl = `/risk-controls/${props.attachment.risk_control_id}/attachments/${props.attachment.id}/download`;
    
    // เปิด URL ในหน้าต่างใหม่เพื่อดาวน์โหลด
    globalThis.window.open(downloadUrl, '_blank');
    
    // แสดงข้อความแจ้งเตือนสำเร็จ
    toast.success('กำลังดาวน์โหลดไฟล์...', {
      description: `ไฟล์: ${props.attachment.filename} (${formattedFileSize.value})`
    });
    
    // บันทึก log การดาวน์โหลด
    console.log('📥 ดาวน์โหลดไฟล์:', {
      attachment_id: props.attachment.id,
      filename: props.attachment.filename,
      filesize: props.attachment.filesize,
      risk_control_id: props.attachment.risk_control_id,
      control_name: props.attachment.risk_control?.control_name,
      download_url: downloadUrl,
      timestamp: new Date().toISOString()
    });
    
  } catch (error) {
    // จัดการข้อผิดพลาด
    console.error('❌ เกิดข้อผิดพลาดในการดาวน์โหลดไฟล์:', error);
    
    toast.error('เกิดข้อผิดพลาดในการดาวน์โหลดไฟล์', {
      description: 'กรุณาลองใหม่อีกครั้งหรือติดต่อผู้ดูแลระบบ'
    });
  }
};

/**
 * ฟังก์ชันคัดลอก URL ไฟล์
 * คัดลอก URL ของไฟล์ไปยัง clipboard
 */
const copyFileUrl = async () => {
  try {
    await navigator.clipboard.writeText(props.attachment.url);
    
    toast.success('คัดลอก URL ไฟล์เรียบร้อยแล้ว');
    
    console.log('📋 คัดลอก URL ไฟล์:', {
      attachment_id: props.attachment.id,
      filename: props.attachment.filename,
      url: props.attachment.url,
      timestamp: new Date().toISOString()
    });
    
  } catch (error) {
    console.error('❌ เกิดข้อผิดพลาดในการคัดลอก URL:', error);
    toast.error('เกิดข้อผิดพลาดในการคัดลอก URL');
  }
};

// ==================== ฟังก์ชันนำทาง ====================
/**
 * ฟังก์ชันกลับหน้าก่อนหน้า
 * ใช้ history.back() หรือนำทางไปยังหน้ารายการการควบคุมความเสี่ยง
 */
const goBack = () => {
  try {
    // ลองใช้ history.back() ก่อน
    if (globalThis.window.history.length > 1) {
      globalThis.window.history.back();
    } else {
      // ถ้าไม่มี history ให้นำทางไปยังหน้ารายการการควบคุมความเสี่ยง
      globalThis.window.location.href = '/risk-controls';
    }
    
    console.log('🔙 กลับหน้าก่อนหน้า:', {
      attachment_id: props.attachment.id,
      filename: props.attachment.filename,
      risk_control_id: props.attachment.risk_control_id,
      timestamp: new Date().toISOString()
    });
    
  } catch (error) {
    console.error('❌ เกิดข้อผิดพลาดในการกลับหน้าก่อนหน้า:', error);
    
    // fallback: นำทางไปยังหน้ารายการการควบคุมความเสี่ยง
    globalThis.window.location.href = '/risk-controls';
  }
};

/**
 * ฟังก์ชันไปยังหน้ารายละเอียดการควบคุมความเสี่ยง
 */
const goToRiskControl = () => {
  const url = `/risk-controls/${props.attachment.risk_control_id}`;
  globalThis.window.location.href = url;
  
  console.log('📄 ไปยังหน้ารายละเอียดการควบคุมความเสี่ยง:', {
    risk_control_id: props.attachment.risk_control_id,
    control_name: props.attachment.risk_control?.control_name,
    url: url,
    timestamp: new Date().toISOString()
  });
};

// ==================== Event Handlers ====================
/**
 * จัดการเมื่อโหลดไฟล์เสร็จ
 */
const handleFileLoaded = () => {
  isLoading.value = false;
  hasError.value = false;
  errorMessage.value = '';
  
  console.log('✅ ไฟล์โหลดเสร็จเรียบร้อย:', {
    attachment_id: props.attachment.id,
    filename: props.attachment.filename,
    filetype: props.attachment.filetype,
    timestamp: new Date().toISOString()
  });
  
  toast.success('โหลดไฟล์เรียบร้อยแล้ว');
};

/**
 * จัดการเมื่อเกิดข้อผิดพลาดในการโหลดไฟล์
 */
const handleFileError = (error: Error | string) => {
  isLoading.value = false;
  hasError.value = true;
  errorMessage.value = typeof error === 'string' ? error : error.message;
  
  console.error('❌ เกิดข้อผิดพลาดในการโหลดไฟล์:', {
    attachment_id: props.attachment.id,
    filename: props.attachment.filename,
    error: errorMessage.value,
    timestamp: new Date().toISOString()
  });
  
  toast.error('เกิดข้อผิดพลาดในการโหลดไฟล์', {
    description: errorMessage.value
  });
};

// ==================== Lifecycle Hooks ====================
onMounted(() => {
  // บันทึก log เมื่อเปิดหน้าดูไฟล์
  console.log('📂 เปิดหน้าดูไฟล์แนบการควบคุมความเสี่ยง:', {
    attachment_id: props.attachment.id,
    filename: props.attachment.filename,
    filetype: props.attachment.filetype,
    filesize: props.attachment.filesize,
    risk_control_id: props.attachment.risk_control_id,
    control_name: props.attachment.risk_control?.control_name,
    control_type: props.attachment.risk_control?.control_type,
    is_viewable: isViewableFile.value,
    timestamp: new Date().toISOString()
  });
  
  // ตรวจสอบว่าไฟล์สามารถแสดงผลได้หรือไม่
  if (!isViewableFile.value) {
    toast.info('ไฟล์นี้ไม่สามารถแสดงตัวอย่างได้ กรุณาดาวน์โหลดเพื่อดูเนื้อหา', {
      duration: 5000
    });
  }
});
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head :title="`ไฟล์แนบ: ${attachment.filename}`" />
  
  <!-- พื้นที่หลักของหน้าแสดงไฟล์ -->
  <div class="h-screen flex flex-col bg-gray-100">
    <!-- ส่วนหัวของโปรแกรมดูไฟล์ -->
    <FileViewerHeader 
      :fileName="attachment.filename"
      :fileSize="attachment.filesize || 0"
      :fileType="attachment.filetype"
      :controlInfo="{
        name: attachment.risk_control?.control_name || 'ไม่ระบุ',
        type: attachment.risk_control?.control_type,
        status: attachment.risk_control?.status
      }"
      @go-back="goBack"
      @go-to-control="goToRiskControl"
    >
      <!-- ใช้ slot เพื่อแทรกส่วนควบคุมไฟล์ -->
      <FileViewerControls 
        :zoom="zoom"
        :rotation="rotation"
        :canZoom="isViewableFile"
        :canRotate="isViewableFile"
        :isLoading="isLoading"
        @zoom-in="zoomIn"
        @zoom-out="zoomOut"
        @reset-zoom="resetZoom"
        @rotate="rotateFile"
        @reset-rotation="resetRotation"
        @download="downloadFile"
        @copy-url="copyFileUrl"
      />
    </FileViewerHeader>
    
    <!-- ส่วนแสดงเนื้อหาไฟล์ -->
    <FileViewerContent 
      :fileUrl="fileUrl"
      :fileName="attachment.filename"
      :fileType="attachment.filetype || fileExtension"
      :fileIcon="fileIcon"
      :zoom="zoom"
      :rotation="rotation"
      :isLoading="isLoading"
      :hasError="hasError"
      :errorMessage="errorMessage"
      :isViewable="isViewableFile"
      :formattedSize="formattedFileSize"
      @download="downloadFile"
      @loaded="handleFileLoaded"
      @error="handleFileError"
    />
  </div>
</template>

<style scoped>
/* สไตล์เพิ่มเติมสำหรับหน้าดูไฟล์ (ถ้าจำเป็น) */
.file-viewer-container {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

/* Animation สำหรับการโหลด */
.loading-animation {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .h-screen {
    height: 100vh;
    height: 100dvh; /* ใช้ dynamic viewport height สำหรับ mobile */
  }
}
</style>
