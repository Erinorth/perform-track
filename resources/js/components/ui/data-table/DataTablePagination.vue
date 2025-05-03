<!-- 
  ไฟล์: resources\js\components\ui\data-table\DataTablePagination.vue
  Component สำหรับแสดงและควบคุมการเปลี่ยนหน้า (pagination) ของ DataTable
  รองรับการเลือกจำนวนแถวต่อหน้า, แสดงแถวที่เลือก, ปุ่มเปลี่ยนหน้าต่างๆ
  ใช้ร่วมกับ @tanstack/vue-table, lucide-vue-next, และ shadcn-vue
-->

<script setup lang="ts">
// นำเข้า type Table จาก @tanstack/vue-table สำหรับจัดการข้อมูลตาราง
import { type Table } from '@tanstack/vue-table'
// นำเข้า icon ที่ใช้สำหรับปุ่มนำทางเปลี่ยนหน้า
import { 
  ChevronLeft as ChevronLeftIcon,          // ไอคอนไปหน้าก่อนหน้า
  ChevronRight as ChevronRightIcon,        // ไอคอนไปหน้าถัดไป
  ChevronsLeft as DoubleArrowLeftIcon,     // ไอคอนไปหน้าแรก
  ChevronsRight as DoubleArrowRightIcon    // ไอคอนไปหน้าสุดท้าย
} from 'lucide-vue-next'

// นำเข้า UI components จาก shadcn-vue
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

// กำหนด props ที่ต้องการรับ: table (ข้อมูลตารางทั้งหมด)
interface DataTablePaginationProps {
  table: Table<any>
}
defineProps<DataTablePaginationProps>()
</script>

<template>
  <!-- ส่วนแสดงจำนวนแถวที่เลือก เทียบกับจำนวนแถวทั้งหมดที่กรองแล้ว -->
  <div class="flex-1 text-sm text-muted-foreground">
      {{ table.getFilteredSelectedRowModel().rows.length }} of
      {{ table.getFilteredRowModel().rows.length }} row(s) selected.
    </div>
  <div class="flex items-center justify-between px-2">
    <div class="flex items-center space-x-6 lg:space-x-8">
      <!-- ส่วนเลือกจำนวนแถวต่อหน้า -->
      <div class="flex items-center space-x-2">
        <p class="text-sm font-medium">
          Rows per page
        </p>
        <!-- Dropdown เลือกจำนวนแถวต่อหน้า (10, 20, 30, 40, 50) -->
        <Select
          :model-value="`${table.getState().pagination.pageSize}`"
          @update:model-value="(value) => value !== null && table.setPageSize(Number(value))"
        >
          <SelectTrigger class="h-8 w-[70px]">
            <SelectValue :placeholder="`${table.getState().pagination.pageSize}`" />
          </SelectTrigger>
          <SelectContent side="top">
            <SelectItem v-for="pageSize in [10, 20, 30, 40, 50]" :key="pageSize" :value="`${pageSize}`">
              {{ pageSize }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>
      
      <!-- ส่วนแสดงหน้าปัจจุบัน / จำนวนหน้าทั้งหมด -->
      <div class="flex w-[100px] items-center justify-center text-sm font-medium">
        Page {{ table.getState().pagination.pageIndex + 1 }} of
        {{ table.getPageCount() }}
      </div>
      
      <!-- ส่วนปุ่มนำทางเปลี่ยนหน้า -->
      <div class="flex items-center space-x-2">
        <!-- ปุ่มไปหน้าแรก (จะซ่อนบนหน้าจอขนาดเล็ก) -->
        <Button
          variant="outline"
          class="hidden w-8 h-8 p-0 lg:flex"
          :disabled="!table.getCanPreviousPage()"
          @click="table.setPageIndex(0)"
        >
          <span class="sr-only">Go to first page</span>
          <DoubleArrowLeftIcon class="w-4 h-4" />
        </Button>
        
        <!-- ปุ่มไปหน้าก่อนหน้า -->
        <Button
          variant="outline"
          class="w-8 h-8 p-0"
          :disabled="!table.getCanPreviousPage()"
          @click="table.previousPage()"
        >
          <span class="sr-only">Go to previous page</span>
          <ChevronLeftIcon class="w-4 h-4" />
        </Button>
        
        <!-- ปุ่มไปหน้าถัดไป -->
        <Button
          variant="outline"
          class="w-8 h-8 p-0"
          :disabled="!table.getCanNextPage()"
          @click="table.nextPage()"
        >
          <span class="sr-only">Go to next page</span>
          <ChevronRightIcon class="w-4 h-4" />
        </Button>
        
        <!-- ปุ่มไปหน้าสุดท้าย (จะซ่อนบนหน้าจอขนาดเล็ก) -->
        <Button
          variant="outline"
          class="hidden w-8 h-8 p-0 lg:flex"
          :disabled="!table.getCanNextPage()"
          @click="table.setPageIndex(table.getPageCount() - 1)"
        >
          <span class="sr-only">Go to last page</span>
          <DoubleArrowRightIcon class="w-4 h-4" />
        </Button>
      </div>
    </div>
  </div>
</template>
