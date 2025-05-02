<!-- 
  ไฟล์: resources\js\features\department_risk\DataTableDropDown.vue
  Component สำหรับแสดงเมนู dropdown action (Edit, Delete, Expand) ในแต่ละแถวของ DataTable
  ใช้ร่วมกับ lucide-vue-next สำหรับ icon, shadcn-vue สำหรับ UI, และรองรับการส่ง event ไปยัง parent
-->

<script setup lang="ts">
// นำเข้า Button และ DropdownMenu component จาก shadcn-vue UI library
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
// นำเข้า icon MoreHorizontal จาก lucide-vue-next สำหรับแสดงไอคอนเมนู
import { MoreHorizontal, EditIcon, TrashIcon, EyeIcon } from 'lucide-vue-next'

// รับ props department_risk จาก parent component
defineProps<{
  department_risk: {
    id: number,
    risk_name: string,  // เพิ่มเพื่อแสดงชื่อความเสี่ยงในข้อความยืนยันการลบ
    organizational_risk?: {
      id: number,
      risk_name: string
    }
  }
}>()

// กำหนด emits สำหรับส่ง event ไปยัง parent เมื่อผู้ใช้เลือก action ต่าง ๆ
defineEmits<{
  (e: 'expand'): void            // ขยายแถวเพื่อดูรายละเอียด
  (e: 'edit', id: number): void  // แก้ไขข้อมูล โดยส่ง id ไปด้วย
  (e: 'delete', id: number): void // ลบข้อมูล โดยส่ง id ไปด้วย
}>()
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
    
    <!-- แก้ไขโดยย้าย attribute min-w-[120px] ไปเป็น class แทน -->
    <DropdownMenuContent align="end" class="min-w-[120px]">
      <DropdownMenuLabel>ตัวเลือก</DropdownMenuLabel>
      
      <!-- เมนูแก้ไข -->
      <DropdownMenuItem @click="$emit('edit', department_risk.id)" class="cursor-pointer">
        <EditIcon class="w-4 h-4 mr-2" />
        <span>แก้ไข</span>
      </DropdownMenuItem>
      
      <!-- เมนูลบ -->
      <DropdownMenuItem @click="$emit('delete', department_risk.id)" class="cursor-pointer text-destructive focus:text-destructive">
        <TrashIcon class="w-4 h-4 mr-2" />
        <span>ลบข้อมูล</span>
      </DropdownMenuItem>
      
      <DropdownMenuSeparator />
      
      <!-- เมนูขยาย -->
      <DropdownMenuItem @click="$emit('expand')" class="cursor-pointer">
        <EyeIcon class="w-4 h-4 mr-2" />
        <span>ดูรายละเอียด</span>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
