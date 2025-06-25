<!-- ไฟล์: resources/js/pages/settings/permission/data-table/DataTable.vue -->
<script setup lang="ts" generic="TData, TValue">
// เพิ่ม import จาก Vue ที่ขาดหายไป
import { ref, watch } from 'vue'

// เปลี่ยนจาก @/components/ui/data-table เป็น @/components/data-table
import DataTableSearch from '@/components/custom/data-table/DataTableSearch.vue'
import DataTablePagination from '@/components/custom/data-table/DataTablePagination.vue'

// นำเข้าไอคอนที่จำเป็น (ไม่ได้ใช้ในโค้ดปัจจุบัน สามารถลบได้)
// import { ChevronDown } from 'lucide-vue-next'

// นำเข้า type สำหรับ table
import type {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
  ExpandedState,
} from '@tanstack/vue-table'

// นำเข้า Table component หลัก
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

// นำเข้า utility และ composable ของ vue-table
import {
  FlexRender,
  getCoreRowModel,
  getPaginationRowModel,
  getFilteredRowModel,
  getSortedRowModel,
  getExpandedRowModel,
  useVueTable,
} from '@tanstack/vue-table'

// นำเข้า UI components
import { Button } from '@/components/ui/button'
import { valueUpdater } from '@/lib/utils'
import { Input } from '@/components/ui/input'

// รับ props: columns, data, meta
const props = defineProps<{
  columns: ColumnDef<TData, TValue>[]
  data: TData[]
  meta?: any
}>()

// state สำหรับการจัดการตาราง
const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const expanded = ref<ExpandedState>({})
const searchQuery = ref('')

// columns ที่ต้องการค้นหา (สามารถปรับได้ที่นี่)
const searchColumns = ['egat_id', 'name']

// ฟังก์ชันค้นหาหลายคอลัมน์ (generic)
function globalFilterFn(row: any, _columnId: string, filterValue: string) {
  const searchValue = String(filterValue).toLowerCase()
  return searchColumns.some(col =>
    String(row.getValue(col) || '').toLowerCase().includes(searchValue)
  )
}

// สร้าง table instance ด้วย useVueTable
const table = useVueTable({
  get data() { return props.data },
  get columns() { return props.columns },
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getExpandedRowModel: getExpandedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
  onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
  onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
  onExpandedChange: updaterOrValue => valueUpdater(updaterOrValue, expanded),
  state: {
    get sorting() { return sorting.value },
    get columnFilters() { return columnFilters.value },
    get columnVisibility() { return columnVisibility.value },
    get rowSelection() { return rowSelection.value },
    get expanded() { return expanded.value },
    get globalFilter() { return searchQuery.value }
  },
  globalFilterFn,
  meta: props.meta,
})

// อัปเดต global filter เมื่อ searchQuery เปลี่ยน
// ย้ายมาหลัง table definition เพื่อหลีกเลี่ยงปัญหา hoisting
watch(searchQuery, (value) => {
  table.setGlobalFilter(value)
})
</script>

<template>
  <!-- ส่วนค้นหาและ view options -->
  <div class="flex items-center py-4">
    <!-- ใช้ DataTableSearch component -->
    <DataTableSearch
      v-model="searchQuery"
      :columns="searchColumns"
      placeholder="Search ID or Name..."
    />
  </div>

  <!-- ตารางข้อมูลหลัก -->
  <div class="border rounded-md">
    <Table>
      <TableHeader>
        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
          <TableHead v-for="header in headerGroup.headers" :key="header.id">
            <FlexRender
              v-if="!header.isPlaceholder"
              :render="header.column.columnDef.header"
              :props="header.getContext()"
            />
          </TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <!-- แสดงข้อมูลแต่ละแถว -->
        <template v-if="table.getRowModel().rows?.length">
          <template v-for="row in table.getRowModel().rows" :key="row.id">
            <TableRow :data-state="row.getIsSelected() ? 'selected' : undefined">
              <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
              </TableCell>
            </TableRow>
            <!-- แสดงข้อมูลขยาย (expanded) -->
            <TableRow v-if="row.getIsExpanded()">
              <TableCell :colspan="row.getAllCells().length">
                {{ JSON.stringify(row.original) }}
              </TableCell>
            </TableRow>
          </template>
        </template>
        <!-- กรณีไม่มีข้อมูล -->
        <template v-else>
          <TableRow>
            <TableCell :colspan="props.columns.length" class="h-24 text-center">
              No results.
            </TableCell>
          </TableRow>
        </template>
      </TableBody>
    </Table>
  </div>

  <!-- ส่วน pagination -->
  <div class="flex items-center justify-end py-4 space-x-2">
    <DataTablePagination :table="table" />
  </div>
</template>
