<script setup lang="ts">
// นำเข้า Components และ Icons ที่จำเป็น
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-vue-next';

// กำหนด props สำหรับรับข้อมูลเข้ามาแสดง
defineProps<{
  title: string;
  description?: string;
}>();

// กำหนด emit events สำหรับส่งเหตุการณ์กลับไปยัง parent
const emit = defineEmits<{
  (e: 'create'): void
}>();

// ฟังก์ชันจัดการการเพิ่มข้อมูลใหม่
const handleCreate = () => {
  emit('create');
};
</script>

<template>
  <!-- ส่วนหัวที่แสดงชื่อหน้าและปุ่มเพิ่มข้อมูล -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
    <!-- ส่วนหัวข้อหน้า -->
    <div>
      <h1 class="text-2xl font-bold">{{ title }}</h1>
      <p v-if="description" class="text-muted-foreground">{{ description }}</p>
    </div>
    
    <!-- ปุ่มเพิ่มข้อมูล (Responsive) -->
    <Button @click="handleCreate" class="flex items-center gap-2 w-full sm:w-auto">
      <PlusIcon class="h-4 w-4" />
      <span>เพิ่มความเสี่ยงฝ่าย</span>
    </Button>
  </div>
</template>
