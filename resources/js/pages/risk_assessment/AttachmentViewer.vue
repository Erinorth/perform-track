<!-- ไฟล์: resources/js/Pages/AttachmentViewer.vue -->
<script setup lang="ts">
// นำเข้า dependencies ที่จำเป็น
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

// นำเข้า components ที่สร้างไว้
import FileViewerHeader from '@/components/FileViewer/FileViewerHeader.vue';
import FileViewerControls from '@/components/FileViewer/FileViewerControls.vue';
import FileViewerContent from '@/components/FileViewer/FileViewerContent.vue';

// กำหนด Props สำหรับรับข้อมูลไฟล์แนบ
const props = defineProps<{
  attachment: {
    id: number;
    file_name: string;
    file_path: string;
    file_type: string;
    file_size: number;
    url: string;
    risk_assessment_id: number;
  }
}>();

// ตัวแปรสำหรับการแสดงผล
const fileUrl = ref<string>(props.attachment.url);
const zoom = ref<number>(1);
const rotation = ref<number>(0);

// ฟังก์ชันย่อขยาย
const zoomIn = () => {
  zoom.value = Math.min(zoom.value + 0.25, 3);
};

const zoomOut = () => {
  zoom.value = Math.max(zoom.value - 0.25, 0.5);
};

// ฟังก์ชันหมุนไฟล์
const rotateFile = () => {
  rotation.value = (rotation.value + 90) % 360;
};

// ดาวน์โหลดไฟล์
const downloadFile = () => {
  try {
    const downloadUrl = `/risk-assessments/${props.attachment.risk_assessment_id}/attachments/${props.attachment.id}/download`;
    globalThis.window.location.href = downloadUrl;
    toast.success('กำลังดาวน์โหลดไฟล์...');
  } catch (error) {
    toast.error('เกิดข้อผิดพลาดในการดาวน์โหลดไฟล์');
    console.error('ข้อผิดพลาดการดาวน์โหลด:', error);
  }
};

// กลับหน้าก่อนหน้า
const goBack = () => {
  globalThis.window.history.back();
};

// จัดการเมื่อโหลดไฟล์เสร็จ
const handleFileLoaded = () => {
  console.log('ไฟล์โหลดเสร็จเรียบร้อย');
};
</script>

<template>
  <Head :title="attachment.file_name" />
  
  <div class="h-screen flex flex-col bg-gray-100">
    <!-- ส่วนหัวของโปรแกรมดูไฟล์ -->
    <FileViewerHeader 
      :fileName="attachment.file_name"
      :fileSize="attachment.file_size"
      @go-back="goBack"
    >
      <!-- ใช้ slot เพื่อแทรกส่วนควบคุมไฟล์ -->
      <FileViewerControls 
        :zoom="zoom"
        @zoom-in="zoomIn"
        @zoom-out="zoomOut"
        @rotate="rotateFile"
        @download="downloadFile"
      />
    </FileViewerHeader>
    
    <!-- ส่วนแสดงเนื้อหาไฟล์ -->
    <FileViewerContent 
      :fileUrl="fileUrl"
      :fileName="attachment.file_name"
      :fileType="attachment.file_type"
      :zoom="zoom"
      :rotation="rotation"
      @download="downloadFile"
      @loaded="handleFileLoaded"
    />
  </div>
</template>
