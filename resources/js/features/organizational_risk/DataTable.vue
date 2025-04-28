<script setup lang="ts" generic="TData, TValue">
import { ref, watch, computed } from 'vue'
import type {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
  ExpandedState,
} from '@tanstack/vue-table'

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

import {
  FlexRender,
  getCoreRowModel,
  getPaginationRowModel,
  getFilteredRowModel,
  getSortedRowModel,
  getExpandedRowModel,
  useVueTable,
} from '@tanstack/vue-table'

import { Button } from '@/components/ui/button'

import { valueUpdater } from '@/lib/utils'

import { Input } from '@/components/ui/input'

import DataTablePagination from './DataTablePagination.vue'

import DataTableViewOptions from './DataTableViewOptions.vue'

// เพิ่ม import นี้
import TagFilter from '@/components/ui/tag-filter/TagFilter.vue'

const props = defineProps<{
  columns: ColumnDef<TData, TValue>[]
  data: TData[]
  meta?: any
}>()

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({
  id: false,
  created_at: false,
  updated_at: false
})
const rowSelection = ref({})
const expanded = ref<ExpandedState>({})

const searchQuery = ref('')

watch(searchQuery, (value) => {
  table.setGlobalFilter(value)
})

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
  onRowSelectionChange: updaterOrValue => valueUpdater(updaterOrValue, rowSelection),
  onExpandedChange: updaterOrValue => valueUpdater(updaterOrValue, expanded),
  state: {
    get sorting() { return sorting.value },
    get columnFilters() { return columnFilters.value },
    get columnVisibility() { return columnVisibility.value },
    get rowSelection() { return rowSelection.value },
    get expanded() { return expanded.value },
    get globalFilter() { return searchQuery.value }
  },
  globalFilterFn: (row, columnId, filterValue) => {
    const searchValue = String(filterValue).toLowerCase();
    const riskNameMatch = String(row.getValue('risk_name')).toLowerCase().includes(searchValue);
    const descriptionMatch = String(row.getValue('description') || '').toLowerCase().includes(searchValue);
    return riskNameMatch || descriptionMatch;
  },
  meta: props.meta,
})

// เพิ่ม function นี้ใน script setup
const getUniqueYears = computed(() => {
  const years = new Set<string>();
  props.data.forEach((item: any) => {
    if (item.year) {
      years.add(item.year.toString());
    }
  });
  
  const yearOptions = Array.from(years).map(year => ({
    value: year,
    label: year,
    count: props.data.filter((item: any) => item.year?.toString() === year).length
  }));
  
  return yearOptions.sort((a, b) => b.value.localeCompare(a.value)); // เรียงจากใหม่ไปเก่า
});

const clearAllFilters = () => {
  // ล้าง search query
  searchQuery.value = ''
  
  // ล้าง column filter สำหรับ year
  table.getColumn('year')?.setFilterValue(null)
}
</script>

<template>
  <div class="flex items-center py-4">
    <Input
      class="max-w-sm"
      placeholder="Search Risk Name or Description..."
      v-model="searchQuery"
    />
    
    <div class="ml-2" size="sm">
      <!-- เพิ่ม TagFilter สำหรับปีที่นี่ -->
      <TagFilter 
        title="Year" 
        column="year" 
        :options="getUniqueYears" 
        :table="table" 
      />
    </div>
    <Button
      v-if="searchQuery || table.getColumn('year')?.getFilterValue()"
      variant="ghost"
      class="ml-2"
      @click="clearAllFilters"
      size="sm"
    >
      Clear
    </Button>

    
            
      <DataTableViewOptions :table="table" />
        </div>
  <div class="border rounded-md">
    <Table>
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
      <TableBody>
        <template v-if="table.getRowModel().rows?.length">
                      <template v-for="row in table.getRowModel().rows" :key="row.id">
                        <TableRow :data-state="row.getIsSelected() ? 'selected' : undefined">
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                            </TableCell>
                        </TableRow>
                        <!-- แก้ไขในส่วนขยายแถวของ DataTable.vue -->
<TableRow v-if="row.getIsExpanded()">
  <TableCell :colspan="row.getAllCells().length">
    <div class="p-4 bg-muted/30 rounded-md">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <h3 class="text-sm font-medium">รายละเอียดความเสี่ยง</h3>
          <p class="mt-1 whitespace-pre-wrap">{{ row.original.description }}</p>
        </div>
        <div v-if="row.original.department_risks && row.original.department_risks.length > 0">
          <h3 class="text-sm font-medium">ความเสี่ยงระดับสายงานที่เกี่ยวข้อง</h3>
          <ul class="mt-1 space-y-1">
            <li v-for="dept in row.original.department_risks" :key="dept.id" class="text-sm">
              {{ dept.risk_name }}
            </li>
          </ul>
        </div>
        <div v-else>
          <h3 class="text-sm font-medium">ความเสี่ยงระดับสายงานที่เกี่ยวข้อง</h3>
          <p class="text-sm text-muted-foreground mt-1">ไม่มีความเสี่ยงระดับสายงานที่เกี่ยวข้อง</p>
        </div>
      </div>
    </div>
  </TableCell>
</TableRow>
                      </template>
                    </template>
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
  <div class="flex items-center justify-end py-4 space-x-2">
      <DataTablePagination :table="table" />
    </div>
</template>