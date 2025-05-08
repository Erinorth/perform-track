<!-- ไฟล์: components/FileViewer/FileViewerControls.vue -->
<script setup lang="ts">
// นำเข้า dependencies ที่จำเป็น
import { ZoomIn, ZoomOut, RotateCw, Download } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

// กำหนด Props
const props = defineProps<{
  zoom: number; 
}>();

// กำหนด Events
const emit = defineEmits<{
  (e: 'zoom-in'): void;
  (e: 'zoom-out'): void;
  (e: 'rotate'): void;
  (e: 'download'): void;
}>();
</script>

<template>
  <div class="flex items-center space-x-2">
    <Button variant="outline" size="sm" @click="emit('zoom-out')">
      <ZoomOut class="w-4 h-4" />
    </Button>
    <span class="text-sm">{{ Math.round(zoom * 100) }}%</span>
    <Button variant="outline" size="sm" @click="emit('zoom-in')">
      <ZoomIn class="w-4 h-4" />
    </Button>
    <Button variant="outline" size="sm" @click="emit('rotate')">
      <RotateCw class="w-4 h-4" />
    </Button>
    <Button variant="outline" size="sm" @click="emit('download')">
      <Download class="w-4 h-4 mr-1" />
      <span class="hidden sm:inline">ดาวน์โหลด</span>
    </Button>
  </div>
</template>
