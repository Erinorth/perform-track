<!-- 
  ไฟล์: resources/js/features/organizational_risk/BulkActionMenu.vue
  คอมโพเนนต์สำหรับแสดงตัวเลือกการทำงานกับข้อมูลที่เลือกหลายรายการ
-->

<script setup lang="ts">
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'
import { ChevronDownIcon, Trash2Icon, FileTextIcon, ArchiveIcon } from 'lucide-vue-next'
import { toast } from 'vue-sonner'

// กำหนด Props ที่รับเข้ามา
defineProps<{
  count: number // จำนวนรายการที่เลือก
  loading?: boolean // สถานะกำลังโหลด
}>()

// กำหนด Events ที่จะส่งออกไป
const emit = defineEmits<{
  (e: 'delete'): void
  (e: 'clear'): void
  (e: 'export'): void
}>()

// ฟังก์ชันสำหรับการทำงานต่างๆ
const handleDelete = () => emit('delete')
const handleClear = () => emit('clear')
const handleExport = () => {
  emit('export')
  toast.info('ฟีเจอร์การส่งออกข้อมูลกำลังอยู่ระหว่างการพัฒนา')
}
</script>

<template>
  <div class="flex items-center space-x-2">
    <!-- แสดงจำนวนรายการที่เลือก -->
    <span class="text-sm font-medium">เลือก {{ count }} รายการ</span>
    
    <!-- เมนูแบบ dropdown สำหรับการทำงานกับข้อมูลที่เลือก -->
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" size="sm" class="h-8" :disabled="loading">
          ตัวเลือก <ChevronDownIcon class="ml-2 h-4 w-4" />
        </Button>
      </DropdownMenuTrigger>
      
      <DropdownMenuContent align="end">
        <DropdownMenuItem class="text-destructive cursor-pointer" @click="handleDelete">
          <Trash2Icon class="mr-2 h-4 w-4" />
          <span>ลบรายการที่เลือก</span>
        </DropdownMenuItem>
        
        <DropdownMenuItem @click="handleExport">
          <FileTextIcon class="mr-2 h-4 w-4" />
          <span>ส่งออกข้อมูล</span>
        </DropdownMenuItem>
        
        <DropdownMenuItem @click="handleClear">
          <ArchiveIcon class="mr-2 h-4 w-4" />
          <span>ยกเลิกการเลือก</span>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </div>
</template>
