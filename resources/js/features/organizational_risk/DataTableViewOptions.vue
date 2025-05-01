<!-- 
  ไฟล์: resources\js\features\organizational_risk\DataTableViewOptions.vue
  Component สำหรับจัดการการแสดงผลคอลัมน์ของตาราง
  รองรับการเปิด/ปิดการแสดงคอลัมน์ต่าง ๆ ตามความต้องการของผู้ใช้
  ใช้ร่วมกับ @tanstack/vue-table และ shadcn-vue
-->

<script setup lang="ts">
// นำเข้า type Table จาก @tanstack/vue-table สำหรับจัดการข้อมูลตาราง
import type { Table } from '@tanstack/vue-table'
// นำเข้า computed จาก Vue เพื่อสร้าง computed property
import { computed } from 'vue'
// นำเข้า icon สำหรับปุ่มแสดงตัวเลือกการแสดงผล
import { SlidersHorizontal as MixerHorizontalIcon } from 'lucide-vue-next'

// นำเข้า UI components จาก shadcn-vue
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

// กำหนด props ที่ต้องการรับ: table (ข้อมูลตารางทั้งหมด)
interface DataTableViewOptionsProps {
  table: Table<any>
}
const props = defineProps<DataTableViewOptionsProps>()

// สร้าง computed property เพื่อกรองเฉพาะคอลัมน์ที่สามารถซ่อนได้
// (มี accessorFn และ getCanHide() เป็น true)
const columns = computed(() => props.table.getAllColumns()
  .filter(
    column =>
      typeof column.accessorFn !== 'undefined' && column.getCanHide(),
  ))
</script>

<template>
  <!-- Dropdown Menu สำหรับการเลือกคอลัมน์ที่จะแสดง -->
  <DropdownMenu>
    <!-- ปุ่มเปิดเมนูตัวเลือก จะแสดงเฉพาะบนหน้าจอขนาด lg ขึ้นไป -->
    <DropdownMenuTrigger as-child>
      <Button
        variant="outline"
        size="sm"
        class="hidden h-8 ml-auto lg:flex"
      >
        <MixerHorizontalIcon class="w-4 h-4 mr-2" />
        View
      </Button>
    </DropdownMenuTrigger>
    <!-- เนื้อหาเมนู dropdown -->
    <DropdownMenuContent align="end" class="w-[150px]">
      <DropdownMenuLabel>Toggle columns</DropdownMenuLabel>
      <DropdownMenuSeparator />

      <!-- รายการคอลัมน์ต่าง ๆ ที่สามารถเปิด/ปิดการแสดงผลได้ -->
      <!-- ค่าการแสดงผลปัจจุบันของคอลัมน์ -->
      <!-- สลับการแสดงผลของคอลัมน์ -->
      <DropdownMenuCheckboxItem
        v-for="column in columns"
        :key="column.id"
        class="capitalize"
        :modelValue="column.getIsVisible()"
        @update:modelValue="(value) => column.toggleVisibility(!!value)" 
      >
        {{ column.id }} <!-- ชื่อคอลัมน์ -->
      </DropdownMenuCheckboxItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
