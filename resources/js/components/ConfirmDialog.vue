<!-- 
  ไฟล์: resources\js\components\ConfirmDialog.vue
  Component แสดงกล่องยืนยันการทำงานสำคัญ เช่น การลบข้อมูล
  ใช้ร่วมกับ composable useConfirm เพื่อแสดงข้อความยืนยัน
  รองรับการแสดงผลแบบ Responsive
-->
<script setup lang="ts">
// นำเข้า components จาก shadcn-vue สำหรับสร้าง dialog
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
// นำเข้าไอคอนจาก lucide-vue-next
import { AlertTriangleIcon } from 'lucide-vue-next';

// กำหนด props ที่รับจาก parent component
defineProps<{
  show: boolean;                  // สถานะการแสดง/ซ่อน dialog
  title: string;                  // หัวข้อ dialog
  message: string;                // ข้อความรายละเอียด
  confirmText?: string;           // ข้อความปุ่มยืนยัน (optional)
  cancelText?: string;            // ข้อความปุ่มยกเลิก (optional)
  processing?: boolean;           // สถานะกำลังประมวลผล (optional)
}>();

// กำหนด events ที่จะส่งไปยัง parent component
defineEmits<{
  (e: 'update:show', value: boolean): void;  // สำหรับการใช้งานกับ v-model
  (e: 'confirm'): void;                      // เมื่อกดปุ่มยืนยัน
  (e: 'cancel'): void;                       // เมื่อกดปุ่มยกเลิก
}>();
</script>

<template>
  <!-- Dialog component จาก shadcn-vue -->
  <Dialog :open="show" @update:open="$emit('update:show', $event)">
    <DialogContent class="sm:max-w-[425px]">
      <!-- ส่วนหัวของ dialog -->
      <DialogHeader>
        <DialogTitle>
          <div class="flex items-center gap-2">
            <!-- ไอคอนเตือน -->
            <AlertTriangleIcon class="h-5 w-5 text-warning" />
            <span>{{ title }}</span>
          </div>
        </DialogTitle>
        <!-- ข้อความรายละเอียด -->
        <DialogDescription>
          {{ message }}
        </DialogDescription>
      </DialogHeader>
      
      <!-- ส่วนท้ายของ dialog แสดงปุ่มดำเนินการ -->
      <DialogFooter class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
        <!-- ปุ่มยกเลิก แบบ responsive -->
        <Button 
          variant="outline" 
          @click="$emit('cancel')"
          class="w-full sm:w-auto"
          :disabled="processing"
        >
          {{ cancelText || 'ยกเลิก' }}
        </Button>
        <!-- ปุ่มยืนยัน แบบ responsive และสีแดง (destructive) -->
        <Button 
          variant="destructive" 
          @click="$emit('confirm')"
          class="w-full sm:w-auto"
          :disabled="processing"
        >
          <span v-if="processing">กำลังดำเนินการ...</span>
          <span v-else>{{ confirmText || 'ยืนยัน' }}</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
