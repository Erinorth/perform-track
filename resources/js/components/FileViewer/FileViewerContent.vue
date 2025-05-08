<!-- ไฟล์: components/FileViewer/FileViewerContent.vue -->
<script setup lang="ts">
// นำเข้า dependencies ที่จำเป็น
import { ref, computed } from 'vue';
import { Download } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

// กำหนด Props
const props = defineProps<{
  fileUrl: string;
  fileName: string;
  fileType: string;
  zoom: number;
  rotation: number;
}>();

// กำหนด Events
const emit = defineEmits<{
  (e: 'download'): void;
  (e: 'loaded'): void;
}>();

// ตัวแปรสำหรับการแสดงผล
const loading = ref<boolean>(true);

// สร้างตัวแปรสำหรับใช้ในการสร้าง URL เต็มรูปแบบ
const originUrl = globalThis.window.location.origin;

// คำนวณชนิดของไฟล์เพื่อใช้แสดงผล
const documentType = computed(() => {
  const type = props.fileType.toLowerCase();
  if (type.includes('pdf')) return 'pdf';
  if (type.includes('image')) return 'image';
  if (type.includes('word') || type.includes('doc')) return 'word';
  if (type.includes('excel') || type.includes('sheet') || type.includes('csv')) return 'excel';
  return 'other';
});

// จัดการเมื่อโหลดไฟล์เสร็จ
const handleLoaded = () => {
  loading.value = false;
  emit('loaded');
};
</script>

<template>
  <div class="flex-1 overflow-auto p-4 flex items-start justify-center">
    <!-- แสดงสถานะกำลังโหลด -->
    <div v-if="loading" class="flex items-center justify-center h-full w-full">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
    </div>
      
    <!-- เนื้อหาไฟล์ -->
    <div 
      v-show="!loading" 
      class="max-w-full"
      :style="{
        transform: `scale(${zoom}) rotate(${rotation}deg)`,
        transformOrigin: 'center center',
        transition: 'transform 0.2s'
      }"
    >
      <!-- แสดงรูปภาพ -->
      <img 
        v-if="documentType === 'image'" 
        :src="fileUrl"
        :alt="fileName"
        class="max-w-full shadow-lg"
        @load="handleLoaded"
      />
        
      <!-- แสดง PDF -->
      <iframe
        v-else-if="documentType === 'pdf'"
        :src="fileUrl"
        class="w-full h-[80vh] border-0 shadow-lg bg-white"
        @load="handleLoaded"
      ></iframe>
        
      <!-- แสดง Office Document (Word, Excel) -->
      <iframe
        v-else-if="['word', 'excel'].includes(documentType)"
        :src="`https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(originUrl + fileUrl)}`"
        class="w-full h-[80vh] border-0 shadow-lg bg-white"
        @load="handleLoaded"
      ></iframe>
        
      <!-- สำหรับไฟล์ที่ไม่รองรับการแสดงผล -->
      <div 
        v-else 
        class="p-8 bg-white rounded-lg shadow-lg text-center"
      >
        <div class="text-xl mb-4">ไม่สามารถแสดงตัวอย่างไฟล์นี้ได้</div>
        <p class="text-gray-600 mb-6">ไฟล์นี้ไม่รองรับการแสดงผลในเบราว์เซอร์ กรุณาดาวน์โหลดเพื่อเปิดดู</p>
        <Button @click="emit('download')">
          <Download class="w-4 h-4 mr-2" />
          ดาวน์โหลดไฟล์
        </Button>
      </div>
    </div>
  </div>
</template>
