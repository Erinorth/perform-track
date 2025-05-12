<!-- 
  ไฟล์: resources\js\features\division_risk\DataTable.vue
  Component หลักสำหรับแสดงตารางข้อมูลความเสี่ยงระดับฝ่าย
  รองรับการค้นหา, กรอง, เรียงลำดับ, แบ่งหน้า, และการขยายแถวเพื่อดูรายละเอียดเพิ่มเติม
  ใช้ @tanstack/vue-table เป็นหลักในการจัดการข้อมูลตาราง
  รองรับการแสดงผลแบบ Responsive สำหรับทุกขนาดหน้าจอ
-->

<script setup lang="ts" generic="TData extends DivisionRisk, TValue">
// นำเข้า types สำหรับโมเดลข้อมูล
import type { DivisionRisk, OrganizationalRisk } from '@/types/types';

// นำเข้า Vue Composition API
import { ref, watch, computed } from 'vue'

// นำเข้า types จาก @tanstack/vue-table
import type {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
  ExpandedState,
} from '@tanstack/vue-table'

// นำเข้า component ตารางพื้นฐานจาก shadcn-vue
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

// นำเข้าฟังก์ชันจัดการตารางจาก @tanstack/vue-table
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
import DataTablePagination from '@/components/ui/data-table/DataTablePagination.vue'
import DataTableViewOptions from '@/components/ui/data-table/DataTableViewOptions.vue'
import TagFilter from '@/components/ui/data-table/tag-filter/TagFilter.vue'

// นำเข้า composable สำหรับตรวจสอบขนาดหน้าจอ
import { useMediaQuery } from '@/composables/useMediaQuery'

import ExpandedRow from './ExpandedRow.vue'

// กำหนด props ที่ต้องการรับ: columns (โครงสร้างคอลัมน์), data (ข้อมูล) และ meta (ข้อมูลเพิ่มเติม)
const props = defineProps<{
  columns: ColumnDef<TData, TValue>[]  // โครงสร้างคอลัมน์
  data: TData[]                        // ข้อมูลที่จะแสดงในตาราง
  meta?: any                           // ข้อมูลเพิ่มเติม เช่น callback functions
}>()

// กำหนด reactive state ต่างๆ สำหรับตาราง
const sorting = ref<SortingState>([])                  // สถานะการเรียงลำดับ
const columnFilters = ref<ColumnFiltersState>([])      // สถานะการกรองคอลัมน์
const isMobile = useMediaQuery('(max-width: 768px)')   // ตรวจสอบว่าเป็นหน้าจอมือถือหรือไม่
const columnVisibility = ref<VisibilityState>({        // สถานะการแสดง/ซ่อนคอลัมน์
  id: false,
  created_at: false,
  updated_at: false,
  description: isMobile.value
})
const rowSelection = ref({})                           // สถานะการเลือกแถว
const expanded = ref<ExpandedState>({})                // สถานะการขยายแถว
const searchQuery = ref('')                            // คำค้นหา

// เมื่อ searchQuery เปลี่ยน ให้อัปเดตการกรองข้อมูล
watch(searchQuery, (value) => {
  table.setGlobalFilter(value)
})

// สร้างและกำหนดค่าตาราง
const table = useVueTable({
  // ดึงข้อมูลและโครงสร้างคอลัมน์จาก props
  get data() { return props.data },
  get columns() { return props.columns },
  
  // กำหนด models สำหรับจัดการตาราง
  getCoreRowModel: getCoreRowModel(),             // โมเดลพื้นฐาน
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
  
  // กำหนดฟังก์ชันสำหรับกรองข้อมูลจากการค้นหา (ค้นหาใน risk_name, description และชื่อความเสี่ยงองค์กรที่เกี่ยวข้อง)
  globalFilterFn: (row, columnId, filterValue) => {
    const searchValue = String(filterValue).toLowerCase();
    const riskName = row.original.risk_name.toLowerCase();
    const description = row.original.description?.toLowerCase() || '';
    
    // เพิ่มการค้นหาในชื่อความเสี่ยงองค์กรที่เกี่ยวข้อง (ถ้ามี)
    const orgRiskName = row.original.organizational_risk?.risk_name?.toLowerCase() || '';
    
    return riskName.includes(searchValue) || 
           description.includes(searchValue) || 
           orgRiskName.includes(searchValue);
  },
  
  // ส่ง meta ไปยัง table
  meta: props.meta,
})

// สร้าง computed property เพื่อดึงปีที่ไม่ซ้ำกันสำหรับใช้ในตัวกรอง
const getUniqueYears = computed(() => {
  // สร้าง Set เพื่อเก็บปีที่ไม่ซ้ำกัน
  const years = new Set<string>();
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
  return yearOptions.sort((a, b) => b.value.localeCompare(a.value)); // เรียงจากใหม่ไปเก่า
});

// สร้าง computed property เพื่อดึงความเสี่ยงองค์กรที่ไม่ซ้ำกันสำหรับใช้ในตัวกรอง
const getUniqueOrgRisks = computed(() => {
  // สร้าง Map เพื่อเก็บความเสี่ยงองค์กรที่ไม่ซ้ำกัน (key = id, value = risk_name)
  const orgRisks = new Map<string, string>();
  
  props.data.forEach((item: any) => {
    if (item.organizational_risk?.id && item.organizational_risk?.risk_name) {
      orgRisks.set(
        item.organizational_risk.id.toString(), 
        item.organizational_risk.risk_name
      );
    }
  });
  
  // แปลงเป็น array ของ option
  const orgRiskOptions = Array.from(orgRisks).map(([id, name]) => ({
    value: id,
    label: name,
    count: props.data.filter((item: any) => 
      item.organizational_risk?.id?.toString() === id
    ).length
  }));
  
  // เรียงลำดับตามชื่อความเสี่ยงองค์กร
  return orgRiskOptions.sort((a, b) => a.label.localeCompare(b.label));
});

// ฟังก์ชันล้างตัวกรองทั้งหมด
const clearAllFilters = () => {
  // ล้าง search query
  searchQuery.value = ''
  
  // ล้าง column filter สำหรับ year
  table.getColumn('year')?.setFilterValue(null)
  
  // ล้าง column filter สำหรับ organizational_risk.id (ถ้ามี)
  table.getColumn('organizational_risk')?.setFilterValue(null)
}

// ติดตามการเปลี่ยนแปลงของขนาดหน้าจอเพื่อปรับการแสดงผล
watch(isMobile, (mobile) => {
  if (mobile) {
    // ถ้าเป็นมือถือ ให้ซ่อนคอลัมน์ description และปรับการแสดงผลอื่นๆ ตามความเหมาะสม
    columnVisibility.value = {
      ...columnVisibility.value,
      description: true,
      organizational_risk: false // ซ่อนคอลัมน์ความเสี่ยงองค์กรบนมือถือเพื่อประหยัดพื้นที่
    }
  } else {
    // ถ้าไม่ใช่มือถือ ให้แสดงคอลัมน์ description และความเสี่ยงองค์กร
    columnVisibility.value = {
      ...columnVisibility.value,
      description: false,
      organizational_risk: true
    }
  }
})
</script>

<template>
  <!-- ส่วนหัวตารางสำหรับการค้นหาและกรอง -->
  <div class="flex flex-wrap items-center gap-2 py-4">
    <!-- ช่องค้นหา -->
    <Input
      class="max-w-sm"
      placeholder="ค้นหาชื่อความเสี่ยงหรือรายละเอียด..."
      v-model="searchQuery"
    />
    
    <!-- TagFilter สำหรับกรองตามปี -->
    <div size="sm">
      <TagFilter 
        title="ปี" 
        column="year" 
        :options="getUniqueYears" 
        :table="table" 
      />
    </div>
    
    <!-- TagFilter สำหรับกรองตามความเสี่ยงองค์กร (เพิ่มเติม) -->
    <div v-if="getUniqueOrgRisks.length > 0" size="sm">
      <TagFilter 
        title="ความเสี่ยงองค์กร" 
        column="organizational_risk" 
        :options="getUniqueOrgRisks" 
        :table="table" 
      />
    </div>
    
    <!-- ปุ่มล้างตัวกรอง (แสดงเฉพาะเมื่อมีการกรอง) -->
    <Button
      v-if="searchQuery || table.getColumn('year')?.getFilterValue() || table.getColumn('organizational_risk')?.getFilterValue()"
      variant="ghost"
      class="ml-auto"
      @click="clearAllFilters"
      size="sm"
    >
      ล้างตัวกรอง
    </Button>
            
    <!-- ตัวเลือกการแสดงคอลัมน์ -->
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
