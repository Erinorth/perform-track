<!-- 
  ไฟล์: resources\js\components\ui\data-table\DataTableDropDown.vue
  Generic Component สำหรับแสดงเมนู dropdown action (Edit, Delete, Expand) ในแต่ละแถวของ DataTable
  สามารถใช้ได้กับข้อมูลหลายประเภทโดยใช้ TypeScript Generics
  ใช้ร่วมกับ lucide-vue-next สำหรับ icon, shadcn-vue สำหรับ UI, และรองรับการส่ง event ไปยัง parent
-->

<script setup lang="ts" generic="T extends { id: number; risk_name: string }">
// นำเข้า Button และ DropdownMenu component จาก shadcn-vue UI library
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
// นำเข้า icon ต่างๆ จาก lucide-vue-next
import { MoreHorizontal, EditIcon, TrashIcon, EyeIcon } from 'lucide-vue-next'
// นำเข้าฟังก์ชั่นสำหรับ logging
import { logAction } from '@/lib/logger'

// รับ props data แบบ generic ที่ต้องมี id และ risk_name เป็นอย่างน้อย
const props = defineProps<{
  data: T,
  // กำหนดชื่อที่จะแสดงในเมนู - เพื่อให้มีความยืดหยุ่นในการนำไปใช้
  menuLabel?: string
}>()

// กำหนด emits สำหรับส่ง event ไปยัง parent เมื่อผู้ใช้เลือก action ต่าง ๆ
const emit = defineEmits<{
  (e: 'expand'): void            // ขยายแถวเพื่อดูรายละเอียด
  (e: 'edit', id: number): void  // แก้ไขข้อมูล โดยส่ง id ไปด้วย
  (e: 'delete', id: number): void // ลบข้อมูล โดยส่ง id ไปด้วย
}>()

// ฟังก์ชันสำหรับการจัดการ event
const handleEdit = () => {
  logAction(`Editing item with ID: ${props.data.id}`)
  emit('edit', props.data.id)
}

const handleDelete = () => {
  logAction(`Deleting item with ID: ${props.data.id}`)
  emit('delete', props.data.id)
}

const handleExpand = () => {
  logAction(`Expanding item with ID: ${props.data.id}`)
  emit('expand')
}
</script>

<template>
  <!-- DropdownMenu สำหรับแสดง action ต่าง ๆ -->
  <DropdownMenu>
    <!-- ปุ่ม trigger เมนู ใช้ icon MoreHorizontal -->
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" class="w-8 h-8 p-0">
        <span class="sr-only">เปิดเมนู</span> <!-- สำหรับ screen reader -->
        <MoreHorizontal class="w-4 h-4" />
      </Button>
    </DropdownMenuTrigger>
    
    <!-- เนื้อหาของเมนู dropdown -->
    <DropdownMenuContent align="end" class="min-w-[120px]">
      <DropdownMenuLabel>{{ menuLabel || 'ตัวเลือก' }}</DropdownMenuLabel>
      
      <!-- เมนูแก้ไข -->
      <DropdownMenuItem @click="handleEdit" class="cursor-pointer">
        <EditIcon class="w-4 h-4 mr-2" />
        <span>แก้ไข</span>
      </DropdownMenuItem>
      
      <!-- เมนูลบ -->
      <DropdownMenuItem @click="handleDelete" class="cursor-pointer text-destructive focus:text-destructive">
        <TrashIcon class="w-4 h-4 mr-2" />
        <span>ลบข้อมูล</span>
      </DropdownMenuItem>
      
      <DropdownMenuSeparator />
      
      <!-- เมนูขยาย -->
      <DropdownMenuItem @click="handleExpand" class="cursor-pointer">
        <EyeIcon class="w-4 h-4 mr-2" />
        <span>ดูรายละเอียด</span>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
