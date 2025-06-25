<script setup lang="ts">
import { computed } from 'vue'
import type { Table } from '@tanstack/vue-table'
import { 
  ChevronLeft, 
  ChevronRight, 
  ChevronsLeft, 
  ChevronsRight 
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

// Props definition
interface Props {
  table: Table<any>
}

const props = defineProps<Props>()

// Computed values สำหรับการแสดงผล
const totalItems = computed(() => props.table.getFilteredRowModel().rows.length)
const currentPage = computed(() => props.table.getState().pagination.pageIndex + 1)
const totalPages = computed(() => props.table.getPageCount())
const pageSize = computed(() => props.table.getState().pagination.pageSize)

// คำนวณ range ของรายการที่แสดงในหน้าปัจจุบัน
const currentRange = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value + 1
  const end = Math.min(currentPage.value * pageSize.value, totalItems.value)
  return { start, end }
})

// Page size options
const pageSizeOptions = [10, 20, 30, 40, 50, 100]
</script>

<template>
  <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <!-- แสดงข้อมูลสรุป -->
    <div class="flex flex-col gap-2 text-sm text-muted-foreground lg:flex-row lg:items-center">
      <span>
        แสดง {{ currentRange.start.toLocaleString() }} - {{ currentRange.end.toLocaleString() }} 
        จาก {{ totalItems.toLocaleString() }} รายการ
      </span>
      
      <!-- แสดงจำนวนแถวที่เลือก (หากมี) -->
      <span 
        v-if="table.getFilteredSelectedRowModel().rows.length > 0"
        class="font-medium"
      >
        (เลือกแล้ว {{ table.getFilteredSelectedRowModel().rows.length }} รายการ)
      </span>
    </div>

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6">
      <!-- Page Size Selector -->
      <div class="flex items-center gap-2">
        <span class="text-sm font-medium text-muted-foreground whitespace-nowrap">
          แสดงต่อหน้า:
        </span>
        <Select
          :model-value="pageSize.toString()"
          @update:model-value="(value) => table.setPageSize(Number(value))"
        >
          <SelectTrigger class="w-20 h-8">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem 
              v-for="size in pageSizeOptions" 
              :key="size" 
              :value="size.toString()"
            >
              {{ size }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Pagination Controls -->
      <div class="flex items-center gap-2">
        <!-- หน้าแรก -->
        <Button
          variant="outline"
          size="icon"
          class="h-8 w-8"
          :disabled="!table.getCanPreviousPage()"
          @click="table.setPageIndex(0)"
        >
          <ChevronsLeft class="h-4 w-4" />
        </Button>
        
        <!-- หน้าก่อนหน้า -->
        <Button
          variant="outline"
          size="icon"
          class="h-8 w-8"
          :disabled="!table.getCanPreviousPage()"
          @click="table.previousPage()"
        >
          <ChevronLeft class="h-4 w-4" />
        </Button>

        <!-- แสดงหน้าปัจจุบัน -->
        <div class="flex items-center gap-1 px-2">
          <span class="text-sm font-medium">
            หน้า {{ currentPage }} จาก {{ totalPages }}
          </span>
        </div>

        <!-- หน้าถัดไป -->
        <Button
          variant="outline"
          size="icon"
          class="h-8 w-8"
          :disabled="!table.getCanNextPage()"
          @click="table.nextPage()"
        >
          <ChevronRight class="h-4 w-4" />
        </Button>

        <!-- หน้าสุดท้าย -->
        <Button
          variant="outline"
          size="icon"
          class="h-8 w-8"
          :disabled="!table.getCanNextPage()"
          @click="table.setPageIndex(table.getPageCount() - 1)"
        >
          <ChevronsRight class="h-4 w-4" />
        </Button>
      </div>
    </div>
  </div>
</template>
