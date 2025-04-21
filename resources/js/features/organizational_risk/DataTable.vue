<script setup lang="ts" generic="TData, TValue">
import { ref, watch } from 'vue'
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

import DataTablePagination from '@/components/ui/data-table/DataTablePagination.vue'

import DataTableViewOptions from '@/components/ui/data-table/DataTableViewOptions.vue'

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
</script>

<template>
  <div class="flex items-center py-4">
      <Input
      class="max-w-sm"
      placeholder="Search Risk Name or Description..."
      v-model="searchQuery"
    />
    <Button
      v-if="searchQuery"
      variant="ghost"
      class="ml-2"
      @click="searchQuery = ''"
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
                        <TableRow v-if="row.getIsExpanded()">
                          <TableCell :colspan="row.getAllCells().length">
                            {{ JSON.stringify(row.original) }}
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