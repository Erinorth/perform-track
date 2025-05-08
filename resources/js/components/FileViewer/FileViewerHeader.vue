<!-- ไฟล์: components/FileViewer/FileViewerHeader.vue -->
<script setup lang="ts">
// นำเข้า dependencies ที่จำเป็น
import { ChevronLeft } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

// กำหนด Props สำหรับรับข้อมูล
const props = defineProps<{
  fileName: string;
  fileSize: number;
}>();

// กำหนด Events ที่จะส่งไปยัง parent component
const emit = defineEmits<{
  (e: 'go-back'): void;
}>();

// แสดงข้อความขนาดไฟล์ในรูปแบบที่อ่านง่าย
const formatFileSize = (bytes: number): string => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
};
</script>

<template>
  <div class="bg-white border-b py-2 px-4 flex items-center justify-between">
    <div class="flex items-center">
      <Button variant="ghost" size="sm" @click="emit('go-back')" class="mr-2">
        <ChevronLeft class="w-5 h-5" />
      </Button>
      <div class="truncate">
        <h1 class="text-lg font-medium truncate max-w-md">{{ fileName }}</h1>
        <p class="text-xs text-gray-500">{{ formatFileSize(fileSize) }}</p>
      </div>
    </div>
    
    <!-- ส่วนที่สามารถกำหนดเนื้อหาจากภายนอก -->
    <slot></slot>
  </div>
</template>
