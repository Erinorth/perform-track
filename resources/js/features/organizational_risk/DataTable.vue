<!--
  ไฟล์: resources/js/features/organizational_risk/DataTable.vue
  
  คำอธิบาย: Component หลักสำหรับแสดงตารางข้อมูลความเสี่ยงระดับองค์กร
  ฟีเจอร์หลัก:
  - การค้นหาข้อมูลจาก risk_name และ description
  - การกรองข้อมูลตามปี (year)
  - การเรียงลำดับข้อมูลในแต่ละคอลัมน์
  - การแบ่งหน้าเพื่อแสดงผล
  - การขยายแถวเพื่อดูรายละเอียดเพิ่มเติม
  - การเลือกแถวหลายรายการเพื่อลบพร้อมกัน
  - รองรับการแสดงผลแบบ Responsive บนทุกขนาดหน้าจอ
-->

<script setup lang="ts" generic="TData extends OrganizationalRisk, TValue">
// ==================== นำเข้า Types และ Interfaces ====================
// นำเข้า types สำหรับโมเดลข้อมูลความเสี่ยง
import type { OrganizationalRisk, DepartmentRisk } from '@/types/types';

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
  DataTableViewOptions, 
  TagFilter, 
  BulkActionMenu 
} from '@/components/ui/data-table'

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
  // ซ่อนคอลัมน์ description บนมือถือ
  description: isMobile.value
})
// สถานะการเลือกแถวสำหรับทำการลบหลายรายการ
const rowSelection = ref({})
// สถานะการขยายแถวเพื่อดูรายละเอียดเพิ่มเติม
const expanded = ref<ExpandedState>({})
// คำค้นหาสำหรับค้นหาทั้งตาราง
const searchQuery = ref('')
// สถานะกำลังลบข้อมูล
const isDeleting = ref(false)

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
  onRowSelectionChange: updaterOrValue => valueUpdater(updaterOrValue, rowSelection),
  onExpandedChange: updaterOrValue => valueUpdater(updaterOrValue, expanded),
  
  // กำหนดสถานะปัจจุบันของตาราง
  state: {
    get sorting() { return sorting.value },
    get columnFilters() { return columnFilters.value },
    get columnVisibility() { return columnVisibility.value },
    get rowSelection() { return rowSelection.value },
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

// ==================== Computed Properties ====================
// สร้าง computed property เพื่อดึงปีที่ไม่ซ้ำกันสำหรับใช้ในตัวกรอง
const getUniqueYears = computed(() => {
  // สร้าง Set เพื่อเก็บปีที่ไม่ซ้ำกัน
  const years = new Set<string>();
  
  // วนลูปเก็บปีที่ไม่ซ้ำกันจากข้อมูลทั้งหมด
  props.data.forEach((item: any) => {
    if (item.year) {
      years.add(item.year.toString());
    }
  });
  
  // แปลงเป็น array ของ option และเพิ่มจำนวนรายการในแต่ละปี
  const yearOptions = Array.from(years).map(year => ({
    value: year,
    label: year,
    count: props.data.filter((item: any) => item.year?.toString() === year).length
  }));
  
  // เรียงลำดับจากปีล่าสุดไปยังปีเก่าสุด
  return yearOptions.sort((a, b) => b.value.localeCompare(a.value));
});

// คำนวณจำนวนแถวที่ถูกเลือกสำหรับ bulk actions
const selectedRowsCount = computed(() => {
  return Object.keys(rowSelection.value).length
})

// คำนวณรายการ ID ที่ถูกเลือกทั้งหมดสำหรับการลบหลายรายการ
const selectedRowIds = computed(() => {
  return Object.keys(rowSelection.value).map(rowIndex => {
    const row = table.getRowModel().rows[parseInt(rowIndex)]
    return row?.original?.id
  }).filter(Boolean) // กรอง null/undefined ออก
})

// ==================== Methods ====================
// ฟังก์ชันล้างตัวกรองทั้งหมด
const clearAllFilters = () => {
  // ล้าง search query
  searchQuery.value = ''
  
  // ล้าง column filter สำหรับ year
  table.getColumn('year')?.setFilterValue(null)
}

// ฟังก์ชันสำหรับลบข้อมูลที่เลือกทั้งหมด
const handleBulkDelete = async () => {
  // ตรวจสอบว่ามีรายการที่เลือกหรือไม่
  if (!selectedRowIds.value.length) {
    toast.error('ไม่มีรายการที่เลือก')
    return
  }
  
  // ตรวจสอบว่ามีเมธอด onBulkDelete ที่ส่งมาจาก parent component หรือไม่
  if (!props.meta?.onBulkDelete) {
    console.error('ไม่พบเมธอด onBulkDelete ใน meta')
    toast.error('เกิดข้อผิดพลาด: ไม่สามารถดำเนินการลบข้อมูลพร้อมกันได้')
    return
  }
  
  try {
    // กำหนดสถานะกำลังลบข้อมูล
    isDeleting.value = true
    
    // เรียกใช้ onBulkDelete จาก meta
    await props.meta.onBulkDelete(selectedRowIds.value)
    
    // รีเซ็ตการเลือกหลังจากลบสำเร็จ
    rowSelection.value = {}
  } catch (error) {
    // บันทึก log และแสดง toast error เมื่อเกิดข้อผิดพลาด
    console.error('เกิดข้อผิดพลาดในการลบข้อมูล:', error)
    toast.error('เกิดข้อผิดพลาดในการลบข้อมูล โปรดลองอีกครั้ง')
  } finally {
    // รีเซ็ตสถานะการลบข้อมูล
    isDeleting.value = false
  }
}

// ฟังก์ชันสำหรับยกเลิกการเลือกทั้งหมด
const clearRowSelection = () => {
  rowSelection.value = {}
}
</script>

<template>
  <!-- ส่วนหัวตารางสำหรับการค้นหาและกรอง -->
  <div class="flex items-center py-4">
    <!-- ช่องค้นหา -->
    <Input
      class="max-w-sm"
      placeholder="Search Risk Name or Description..."
      v-model="searchQuery"
    />
    
    <!-- TagFilter สำหรับกรองตามปี -->
    <div class="ml-2" size="sm">
      <TagFilter 
        title="Year" 
        column="year" 
        :options="getUniqueYears" 
        :table="table" 
      />
    </div>
    
    <!-- ปุ่มล้างตัวกรอง (แสดงเฉพาะเมื่อมีการกรอง) -->
    <Button
      v-if="searchQuery || table.getColumn('year')?.getFilterValue()"
      variant="ghost"
      class="ml-2"
      @click="clearAllFilters"
      size="sm"
    >
      Clear
    </Button>

    <!-- แสดงเมนูสำหรับ Bulk Actions เมื่อมีการเลือกแถว -->
    <div v-if="selectedRowsCount > 0" class="ml-auto">
      <BulkActionMenu 
        :count="selectedRowsCount"
        :loading="isDeleting"
        @delete="handleBulkDelete"
        @clear="clearRowSelection"
        @export="() => {}"
      />
    </div>
  </div>
  
  <!-- ตัวเลือกการแสดงคอลัมน์ (จัดให้อยู่ด้านขวาเสมอ) -->
  <div :class="{ 'ml-auto': selectedRowsCount === 0 }">
    <DataTableViewOptions :table="table" />
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
            <TableRow :data-state="row.getIsSelected() ? 'selected' : undefined">
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
              No results.
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