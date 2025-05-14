<!-- 
  ไฟล์: resources\js\components\AlertConfirmDialog.vue
  คอมโพเนนต์แสดงกล่องยืนยันการทำงานสำคัญ เช่น การลบข้อมูล
  ใช้ร่วมกับ composable useConfirm เพื่อแสดงข้อความยืนยัน
  รองรับการแสดงผลแบบ Responsive
-->
<script setup lang="ts">
// นำเข้า components จาก shadcn-vue สำหรับสร้าง alert dialog
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle
} from '@/components/ui/alert-dialog';

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
  <!-- AlertDialog component จาก shadcn-vue -->
  <AlertDialog 
    :open="show" 
    @update:open="(val) => $emit('update:show', val)"
  >
    <AlertDialogContent class="sm:max-w-[425px]">
      <!-- ส่วนหัวของ dialog -->
      <AlertDialogHeader>
        <AlertDialogTitle>
          <div class="flex items-center gap-2">
            <!-- ไอคอนเตือน -->
            <AlertTriangleIcon class="h-5 w-5 text-warning" />
            <span>{{ title }}</span>
          </div>
        </AlertDialogTitle>
        <!-- ข้อความรายละเอียด -->
        <AlertDialogDescription>
          <span v-html="message"></span>
        </AlertDialogDescription>
      </AlertDialogHeader>
      
      <!-- ส่วนท้ายของ dialog แสดงปุ่มดำเนินการ -->
      <AlertDialogFooter class="flex flex-col sm:flex-row gap-2">
        <!-- ปุ่มยกเลิก -->
        <AlertDialogCancel 
          :disabled="processing"
          @click="$emit('cancel')"
          class="w-full sm:w-auto"
        >
          {{ cancelText || 'ยกเลิก' }}
        </AlertDialogCancel>
        <!-- ปุ่มยืนยัน สีแดง -->
        <AlertDialogAction
          class="bg-destructive text-destructive-foreground hover:bg-destructive/90 w-full sm:w-auto"
          :disabled="processing"
          @click="$emit('confirm')"
        >
          <span v-if="processing">กำลังดำเนินการ...</span>
          <span v-else>{{ confirmText || 'ยืนยัน' }}</span>
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>