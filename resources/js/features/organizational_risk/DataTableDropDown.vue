<!-- 
  ไฟล์: resources\js\features\organizational_risk\DataTableDropDown.vue
  Component สำหรับแสดงเมนู dropdown action (Edit, Delete, Expand) ในแต่ละแถวของ DataTable
  ใช้ร่วมกับ lucide-vue-next สำหรับ icon, shadcn-vue สำหรับ UI, และรองรับการส่ง event ไปยัง parent
-->

<script setup lang="ts">
// นำเข้า Button และ DropdownMenu component จาก shadcn-vue UI library
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
// นำเข้า icon MoreHorizontal จาก lucide-vue-next สำหรับแสดงไอคอนเมนู
import { MoreHorizontal } from 'lucide-vue-next'

// รับ props organization_risk (ต้องมี id) จาก parent component
defineProps<{
  organization_risk: {
    id: number
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
        <span class="sr-only">Open menu</span> <!-- สำหรับ screen reader -->
        <MoreHorizontal class="w-4 h-4" />
      </Button>
    </DropdownMenuTrigger>
    <!-- เนื้อหาเมนู dropdown -->
    <DropdownMenuContent min-w-[120px] align="end">
      <DropdownMenuLabel>Actions</DropdownMenuLabel> <!-- หัวข้อเมนู -->
      <!-- เมนูแก้ไข: เมื่อคลิกจะ emit event 'edit' พร้อม id -->
      <DropdownMenuItem @click="$emit('edit', organization_risk.id)">
        Edit
      </DropdownMenuItem>
      <!-- เมนูลบ: เมื่อคลิกจะ emit event 'delete' พร้อม id -->
      <DropdownMenuItem @click="$emit('delete', organization_risk.id)">
        Delete
      </DropdownMenuItem>
      <DropdownMenuSeparator />
      <!-- เมนูขยาย: เมื่อคลิกจะ emit event 'expand' -->
      <DropdownMenuItem @click="$emit('expand')">View Organizational Risk</DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>