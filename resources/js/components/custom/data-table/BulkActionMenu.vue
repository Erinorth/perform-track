<!-- 
  ไฟล์: resources/js/features/organizational_risk/BulkActionMenu.vue
  คอมโพเนนต์สำหรับแสดงเมนูตัวเลือกการจัดการข้อมูลที่เลือกหลายรายการพร้อมกัน
  สามารถใช้เพื่อลบหลายรายการ, ส่งออกข้อมูล หรือยกเลิกการเลือกทั้งหมด
-->

<script setup lang="ts">
// นำเข้าคอมโพเนนต์ UI พื้นฐาน
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'

// นำเข้าไอคอนที่ใช้ในคอมโพเนนต์
import { 
  ChevronDownIcon, // ไอคอนลูกศรลง สำหรับปุ่มดรอปดาวน์
  Trash2Icon,      // ไอคอนถังขยะ สำหรับการลบ
  FileTextIcon,    // ไอคอนไฟล์ สำหรับการส่งออก
  ArchiveIcon      // ไอคอนจัดเก็บ สำหรับการยกเลิกการเลือก
} from 'lucide-vue-next'

// นำเข้าฟังก์ชัน toast สำหรับแสดงข้อความแจ้งเตือน
import { toast } from 'vue-sonner'

/**
 * กำหนด Props ที่คอมโพเนนต์รับเข้ามา
 * @param count   - จำนวนรายการที่ถูกเลือก ใช้สำหรับระบบตรรกะภายใน
 * @param loading - สถานะกำลังประมวลผล ใช้ควบคุมการปิดปุ่มเมื่อกำลังทำงาน
 */
defineProps<{
  count: number
  loading?: boolean
}>()

/**
 * กำหนด Events ที่คอมโพเนนต์จะส่งออกไปยังคอมโพเนนต์แม่
 * - delete: เมื่อผู้ใช้ต้องการลบรายการที่เลือกทั้งหมด
 * - clear: เมื่อผู้ใช้ต้องการยกเลิกการเลือกทั้งหมด
 * - export: เมื่อผู้ใช้ต้องการส่งออกข้อมูลที่เลือก
 */
const emit = defineEmits<{
  (e: 'delete'): void
  (e: 'clear'): void
  (e: 'export'): void
}>()

// ฟังก์ชันสำหรับจัดการการลบรายการที่เลือกทั้งหมด
const handleDelete = () => {
  // ส่ง event 'delete' ไปยังคอมโพเนนต์แม่
  emit('delete')
}

// ฟังก์ชันสำหรับจัดการการยกเลิกการเลือกทั้งหมด
const handleClear = () => {
  // ส่ง event 'clear' ไปยังคอมโพเนนต์แม่
  emit('clear')
}

// ฟังก์ชันสำหรับจัดการการส่งออกข้อมูลที่เลือก
const handleExport = () => {
  // ส่ง event 'export' ไปยังคอมโพเนนต์แม่
  emit('export')
  // แสดงข้อความแจ้งเตือนว่าฟีเจอร์นี้ยังอยู่ระหว่างการพัฒนา
  toast.info('ฟีเจอร์การส่งออกข้อมูลกำลังอยู่ระหว่างการพัฒนา')
}
</script>

<template>
  <!-- คอนเทนเนอร์หลักของคอมโพเนนต์ -->
  <div class="flex items-center space-x-2">
    <!-- เมนูแบบดรอปดาวน์สำหรับการกระทำกับรายการที่เลือก -->
    <DropdownMenu>
      <!-- ปุ่มสำหรับเปิดเมนูดรอปดาวน์ ปิดการทำงานเมื่อ loading เป็น true -->
      <DropdownMenuTrigger asChild>
        <Button variant="outline" size="sm" class="h-8" :disabled="loading">
          ตัวเลือก <ChevronDownIcon class="ml-2 h-4 w-4" />
        </Button>
      </DropdownMenuTrigger>
      
      <!-- เนื้อหาของเมนูดรอปดาวน์ จัดตำแหน่งชิดขวา -->
      <DropdownMenuContent align="end">
        <!-- ตัวเลือกสำหรับลบรายการที่เลือก ใช้สีแดง (destructive) -->
        <DropdownMenuItem class="text-destructive cursor-pointer" @click="handleDelete">
          <Trash2Icon class="mr-2 h-4 w-4" />
          <span>ลบรายการที่เลือก</span>
        </DropdownMenuItem>
        
        <!-- ตัวเลือกสำหรับส่งออกข้อมูลรายการที่เลือก -->
        <DropdownMenuItem @click="handleExport">
          <FileTextIcon class="mr-2 h-4 w-4" />
          <span>ส่งออกข้อมูล</span>
        </DropdownMenuItem>
        
        <!-- ตัวเลือกสำหรับยกเลิกการเลือกทั้งหมด -->
        <DropdownMenuItem @click="handleClear">
          <ArchiveIcon class="mr-2 h-4 w-4" />
          <span>ยกเลิกการเลือก</span>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </div>
</template>
