<script setup lang="ts">
// นำเข้า dependencies ที่จำเป็น
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { 
  Download, 
  ChevronLeft, 
  ZoomIn, 
  ZoomOut, 
  RotateCw
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { toast } from 'vue-sonner';

// กำหนด Props สำหรับรับข้อมูลไฟล์แนบ
const props = defineProps<{
  attachment: {
    id: number;
    file_name: string;
    file_path: string;
    file_type: string;
    file_size: number;
    url: string;
    organizational_risk_id: number; // เพิ่ม property นี้
  }
}>();

// ตัวแปรสำหรับการแสดงผล
const fileUrl = ref<string>(props.attachment.url);
const loading = ref<boolean>(true);
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

// ดาวน์โหลดไฟล์ - แก้ไขให้ใช้ globalThis แทน
const downloadFile = () => {
  try {
    const downloadUrl = `/organizational-risks/${props.attachment.organizational_risk_id}/attachments/${props.attachment.id}/download`;
    globalThis.window.location.href = downloadUrl;
    toast.success('กำลังดาวน์โหลดไฟล์...');
  } catch (error) {
    toast.error('เกิดข้อผิดพลาดในการดาวน์โหลดไฟล์');
    console.error('ข้อผิดพลาดการดาวน์โหลด:', error);
  }
};

// กลับหน้าก่อนหน้า - แก้ไขให้ใช้ globalThis แทน
const goBack = () => {
  globalThis.window.history.back();
};

// สร้างตัวแปรสำหรับใช้ในการสร้าง URL เต็มรูปแบบ
const originUrl = globalThis.window.location.origin;

// คำนวณชนิดของไฟล์เพื่อใช้แสดงผล
const documentType = computed(() => {
  const type = props.attachment.file_type.toLowerCase();
  if (type.includes('pdf')) return 'pdf';
  if (type.includes('image')) return 'image';
  if (type.includes('word') || type.includes('doc')) return 'word';
  if (type.includes('excel') || type.includes('sheet') || type.includes('csv')) return 'excel';
  return 'other';
});

// จัดการเมื่อโหลดไฟล์เสร็จ
const handleLoaded = () => {
  loading.value = false;
};

// แสดงข้อความขนาดไฟล์ในรูปแบบที่อ่านง่าย
const formatFileSize = (bytes: number): string => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
};
</script>

<template>
  <Head :title="attachment.file_name" />
  
  <div class="h-screen flex flex-col bg-gray-100">
    <!-- ส่วนหัวของโปรแกรมดูไฟล์ -->
    <div class="bg-white border-b py-2 px-4 flex items-center justify-between">
      <div class="flex items-center">
        <Button variant="ghost" size="sm" @click="goBack" class="mr-2">
          <ChevronLeft class="w-5 h-5" />
        </Button>
        <div class="truncate">
          <h1 class="text-lg font-medium truncate max-w-md">{{ attachment.file_name }}</h1>
          <p class="text-xs text-gray-500">{{ formatFileSize(attachment.file_size) }}</p>
        </div>
      </div>
      
      <div class="flex items-center space-x-2">
        <Button variant="outline" size="sm" @click="zoomOut">
          <ZoomOut class="w-4 h-4" />
        </Button>
        <span class="text-sm">{{ Math.round(zoom * 100) }}%</span>
        <Button variant="outline" size="sm" @click="zoomIn">
          <ZoomIn class="w-4 h-4" />
        </Button>
        <Button variant="outline" size="sm" @click="rotateFile">
          <RotateCw class="w-4 h-4" />
        </Button>
        <Button variant="outline" size="sm" @click="downloadFile">
          <Download class="w-4 h-4 mr-1" />
          <span class="hidden sm:inline">ดาวน์โหลด</span>
        </Button>
      </div>
    </div>
    
    <!-- ส่วนแสดงเนื้อหาไฟล์ -->
    <div class="flex-1 overflow-auto p-4 flex items-start justify-center">
      <div v-if="loading" class="flex items-center justify-center h-full w-full">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
      
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
          :alt="attachment.file_name"
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
          <Button @click="downloadFile">
            <Download class="w-4 h-4 mr-2" />
            ดาวน์โหลดไฟล์
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>