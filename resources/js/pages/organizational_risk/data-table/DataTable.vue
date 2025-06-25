<!--
  ไฟล์: resources/js/features/organizational_risk/DataTable.vue
  
  คำอธิบาย: Component หลักสำหรับแสดงตารางข้อมูลความเสี่ยงระดับองค์กร
  ฟีเจอร์หลัก:
  - การค้นหาข้อมูลจาก risk_name และ description
  - การเรียงลำดับข้อมูลในแต่ละคอลัมน์
  - การแบ่งหน้าเพื่อแสดงผล
  - การขยายแถวเพื่อดูรายละเอียดเพิ่มเติม
  - รองรับการแสดงผลแบบ Responsive บนทุกขนาดหน้าจอ
-->

<script setup lang="ts" generic="TData extends OrganizationalRisk, TValue">
// ==================== นำเข้า Types และ Interfaces ====================
// นำเข้า types สำหรับโมเดลข้อมูลความเสี่ยง
import type { OrganizationalRisk, DivisionRisk } from '@/types';

// นำเข้า Vue Composition API
import { ref, watch, computed } from 'vue'

// นำเข้า types จาก @tanstack/vue-table สำหรับการจัดการตาราง
import type {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
  ExpandedState,
} from '@tanstack/vue-table'

// ==================== นำเข้า UI Components ====================
// นำเข้า component ตารางพื้นฐานจาก shadcn-vue
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

// นำเข้า UI components สำหรับการใช้งานร่วมกับตาราง
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { 
  DataTablePagination, 
  DataTableViewOptions
} from '@/components/custom/data-table'

// ==================== นำเข้า Utilities และ Composables ====================
// นำเข้าฟังก์ชันสำหรับจัดการตารางจาก @tanstack/vue-table
import {
  FlexRender,
  getCoreRowModel,
  getPaginationRowModel,
  getFilteredRowModel,
  getSortedRowModel,
  getExpandedRowModel,
  useVueTable,
} from '@tanstack/vue-table'

// นำเข้า helper utilities
import { valueUpdater } from '@/lib/utils'

// นำเข้า composable สำหรับตรวจสอบขนาดหน้าจอ (Responsive Design)
import { useMediaQuery } from '@/composables/useMediaQuery'

// นำเข้า component แสดงรายละเอียดเมื่อขยายแถว
import ExpandedRow from './ExpandedRow.vue'

// นำเข้า toast notifications
import { toast } from 'vue-sonner'

// ==================== กำหนด Props ====================
// กำหนด props ที่ต้องการรับจาก parent component
const props = defineProps<{
  columns: ColumnDef<TData, TValue>[]  // โครงสร้างคอลัมน์
  data: TData[]                        // ข้อมูลที่จะแสดงในตาราง
  meta?: any                           // ข้อมูลเพิ่มเติม เช่น callback functions สำหรับการ CRUD
}>()

// ==================== กำหนด Reactive States ====================
// สถานะการเรียงลำดับข้อมูลในตาราง
const sorting = ref<SortingState>([])
// สถานะการกรองข้อมูลในแต่ละคอลัมน์
const columnFilters = ref<ColumnFiltersState>([])
// ตรวจสอบว่าเป็นหน้าจอมือถือหรือไม่ (Responsive)
const isMobile = useMediaQuery('(max-width: 768px)')
// กำหนดการแสดง/ซ่อนคอลัมน์ตามขนาดหน้าจอ
const columnVisibility = ref<VisibilityState>({
  id: false,
  created_at: false,
  updated_at: false,
})
// สถานะการขยายแถวเพื่อดูรายละเอียดเพิ่มเติม
const expanded = ref<ExpandedState>({})
// คำค้นหาสำหรับค้นหาทั้งตาราง
const searchQuery = ref('')

// ==================== Watch Effects ====================
// เมื่อ searchQuery เปลี่ยน ให้อัปเดตการกรองข้อมูลทันที
watch(searchQuery, (value) => {
  table.setGlobalFilter(value)
})

// ติดตามการเปลี่ยนแปลงของขนาดหน้าจอเพื่อปรับการแสดงผล (Responsive Design)
watch(isMobile, (mobile) => {
  if (mobile) {
    // ถ้าเป็นมือถือ ให้ซ่อนคอลัมน์ description 
    columnVisibility.value = {
      ...columnVisibility.value,
      description: true
    }
  } else {
    // ถ้าไม่ใช่มือถือ ให้แสดงคอลัมน์ description
    columnVisibility.value = {
      ...columnVisibility.value,
      description: false
    }
  }
})

// ==================== สร้างและกำหนดค่าตาราง ====================
const table = useVueTable({
  // ดึงข้อมูลและโครงสร้างคอลัมน์จาก props
  get data() { return props.data },
  get columns() { return props.columns },
  
  // กำหนด models สำหรับจัดการตาราง
  getCoreRowModel: getCoreRowModel(),             // โมเดลพื้นฐานจำเป็นต้องมี
  getPaginationRowModel: getPaginationRowModel(), // โมเดลสำหรับแบ่งหน้า
  getSortedRowModel: getSortedRowModel(),         // โมเดลสำหรับเรียงลำดับ
  getExpandedRowModel: getExpandedRowModel(),     // โมเดลสำหรับขยายแถว
  getFilteredRowModel: getFilteredRowModel(),     // โมเดลสำหรับกรองข้อมูล
  
  // กำหนด callbacks เมื่อมีการเปลี่ยนแปลงสถานะต่างๆ
  onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
  onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
  onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
  onExpandedChange: updaterOrValue => valueUpdater(updaterOrValue, expanded),
  
  // กำหนดสถานะปัจจุบันของตาราง
  state: {
    get sorting() { return sorting.value },
    get columnFilters() { return columnFilters.value },
    get columnVisibility() { return columnVisibility.value },
    get expanded() { return expanded.value },
    get globalFilter() { return searchQuery.value }
  },
  
  // กำหนดฟังก์ชันสำหรับกรองข้อมูลจากการค้นหา (ค้นหาใน risk_name และ description)
  globalFilterFn: (row, columnId, filterValue) => {
    const searchValue = String(filterValue).toLowerCase();
    const riskName = row.original.risk_name.toLowerCase();
    const description = row.original.description?.toLowerCase() || '';
    
    // คืนค่า true ถ้าพบคำค้นหาใน risk_name หรือ description
    return riskName.includes(searchValue) || description.includes(searchValue);
  },
  
  // ส่ง meta ไปใช้ใน table เพื่อเรียกใช้ callback functions
  meta: props.meta,
})

// ==================== Methods ====================
// ฟังก์ชันล้างตัวกรองทั้งหมด
const clearAllFilters = () => {
  // ล้าง search query
  searchQuery.value = ''
}
</script>

<template>
  <!-- ส่วนหัวตารางสำหรับการค้นหาและกรอง -->
  <div class="flex flex-wrap items-center gap-2 py-4">
    <!-- ช่องค้นหา -->
    <Input
      class="max-w-xs sm:max-w-sm"
      placeholder="ค้นหาชื่อความเสี่ยงหรือรายละเอียด..."
      v-model="searchQuery"
    />

    <!-- ปุ่มล้างตัวกรอง -->
    <Button
      v-if="searchQuery"
      variant="ghost"
      class="ml-2"
      @click="clearAllFilters"
      size="sm"
    >
      ล้างการค้นหา
    </Button>

    <!-- กลุ่มด้านขวา: ปุ่ม View Options -->
    <div class="flex items-center gap-2 ml-auto">
      <!-- ปุ่ม View Options -->
      <DataTableViewOptions :table="table" />
    </div>
  </div>
  
  <!-- ตารางข้อมูล -->
  <div class="border rounded-md">
    <Table>
      <!-- ส่วนหัวคอลัมน์ -->
      <TableHeader>
        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
          <TableHead v-for="header in headerGroup.headers" :key="header.id">
            <FlexRender
              v-if="!header.isPlaceholder" :render="header.column.columnDef.header"
              :props="header.getContext()"
            />
          </TableHead>
        </TableRow>
      </TableHeader>
      
      <!-- ส่วนเนื้อหาตาราง -->
      <TableBody>
        <!-- แสดงข้อมูลเมื่อมีข้อมูล -->
        <template v-if="table.getRowModel().rows?.length">
          <template v-for="row in table.getRowModel().rows" :key="row.id">
            <!-- แถวข้อมูลปกติ -->
            <TableRow>
              <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
              </TableCell>
            </TableRow>
            
            <!-- แถวขยายสำหรับแสดงรายละเอียดเพิ่มเติม (แสดงเมื่อคลิก expand) -->
            <TableRow v-if="row.getIsExpanded()">
              <TableCell :colspan="row.getAllCells().length">
                <ExpandedRow :rowData="row.original" />
              </TableCell>
            </TableRow>
          </template>
        </template>
        
        <!-- แสดงข้อความเมื่อไม่มีข้อมูล -->
        <template v-else>
          <TableRow>
            <TableCell :colspan="columns.length" class="h-24 text-center">
              ไม่พบข้อมูล
            </TableCell>
          </TableRow>
        </template>
      </TableBody>
    </Table>
  </div>
  
  <!-- ส่วนการแบ่งหน้า (pagination) -->
  <div class="flex items-center justify-end py-4 space-x-2">
    <DataTablePagination :table="table" />
  </div>
</template>
