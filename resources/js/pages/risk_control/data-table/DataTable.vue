<!--
  ไฟล์: resources\js\pages\risk_control\data-table\DataTable.vue
  
  คำอธิบาย: Component หลักสำหรับแสดงตารางข้อมูลการควบคุมความเสี่ยง
  ฟีเจอร์หลัก:
  - การค้นหาข้อมูลจาก control_name, description, owner และ implementation_details
  - การเรียงลำดับข้อมูลในแต่ละคอลัมน์
  - การแบ่งหน้าเพื่อแสดงผล
  - การขยายแถวเพื่อดูรายละเอียดเพิ่มเติม
  - การเลือกแถวหลายรายการเพื่อลบพร้อมกัน
  - การเปลี่ยนสถานะการควบคุมความเสี่ยง (active/inactive)
  - รองรับการแสดงผลแบบ Responsive บนทุกขนาดหน้าจอ
-->

<script setup lang="ts" generic="TData extends RiskControl, TValue">
// ==================== นำเข้า Types และ Interfaces ====================
// นำเข้า types สำหรับโมเดลข้อมูลการควบคุมความเสี่ยง
import type { RiskControl, DivisionRisk } from '@/types/risk-control';

// นำเข้า Vue Composition API
import { ref, watch, computed } from 'vue';

// นำเข้า types จาก @tanstack/vue-table สำหรับการจัดการตาราง
import type {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
  ExpandedState,
} from '@tanstack/vue-table';

// ==================== นำเข้า UI Components ====================
// นำเข้า component ตารางพื้นฐานจาก shadcn-vue
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

// นำเข้า UI components สำหรับการใช้งานร่วมกับตาราง
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { 
  DataTablePagination, 
  DataTableViewOptions, 
  TagFilter, 
  BulkActionMenu 
} from '@/components/custom/data-table';

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
} from '@tanstack/vue-table';

// นำเข้า helper utilities
import { valueUpdater } from '@/lib/utils';

// นำเข้า composable สำหรับตรวจสอบขนาดหน้าจอ (Responsive Design)
import { useMediaQuery } from '@/composables/useMediaQuery';

// นำเข้า component แสดงรายละเอียดเมื่อขยายแถว
import ExpandedRow from './ExpandedRow.vue';

// นำเข้า toast notifications
import { toast } from 'vue-sonner';

// ==================== กำหนด Props ====================
// กำหนด props ที่ต้องการรับจาก parent component
const props = defineProps<{
  columns: ColumnDef<TData, TValue>[]  // โครงสร้างคอลัมน์
  data: TData[]                        // ข้อมูลที่จะแสดงในตาราง
  meta?: any                           // ข้อมูลเพิ่มเติม เช่น callback functions สำหรับการ CRUD
  isLoading?: boolean                  // สถานะการโหลดข้อมูล
  selectedItems?: number[]             // รายการที่เลือกจาก parent
}>();

// ==================== ฟังก์ชันช่วยสำหรับการจัดการประเภทการควบคุม ====================
// ฟังก์ชันสำหรับแปลประเภทการควบคุมเป็นป้ายกำกับภาษาไทย
const getControlTypeLabel = (type: string): string => {
  const typeLabels: Record<string, string> = {
    'preventive': 'การป้องกัน',
    'detective': 'การตรวจจับ',
    'corrective': 'การแก้ไข',
    'compensating': 'การชดเชย'
  };
  return typeLabels[type] || 'ไม่ระบุ';
};

// ฟังก์ชันสำหรับแปลสถานะเป็นป้ายกำกับภาษาไทย
const getStatusLabel = (status: string): string => {
  return status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน';
};

// ฟังก์ชันสำหรับจัดรูปแบบวันที่
const formatDateTime = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// ==================== กำหนด Reactive States ====================
// สถานะการเรียงลำดับข้อมูลในตาราง
const sorting = ref<SortingState>([]);
// สถานะการกรองข้อมูลในแต่ละคอลัมน์
const columnFilters = ref<ColumnFiltersState>([]);
// ตรวจสอบว่าเป็นหน้าจอมือถือหรือไม่ (Responsive)
const isMobile = useMediaQuery('(max-width: 768px)');
// กำหนดการแสดง/ซ่อนคอลัมน์ตามขนาดหน้าจอ
const columnVisibility = ref<VisibilityState>({
  id: false,
  created_at: false,
  updated_at: false,
  // ซ่อนคอลัมน์ implementation_details บนมือถือ
  implementation_details: isMobile.value,
  // ซ่อนคอลัมน์ description บนมือถือ
  description: isMobile.value
});
// สถานะการเลือกแถวสำหรับทำการลบหลายรายการ
const rowSelection = ref({});
// สถานะการขยายแถวเพื่อดูรายละเอียดเพิ่มเติม
const expanded = ref<ExpandedState>({});
// คำค้นหาสำหรับค้นหาทั้งตาราง
const searchQuery = ref('');
// สถานะกำลังลบข้อมูล
const isDeleting = ref(false);

// ==================== Watch Effects ====================
// เมื่อ searchQuery เปลี่ยน ให้อัปเดตการกรองข้อมูลทันที
watch(searchQuery, (value) => {
  table.setGlobalFilter(value);
});

// ติดตามการเปลี่ยนแปลงของขนาดหน้าจอเพื่อปรับการแสดงผล (Responsive Design)
watch(isMobile, (mobile) => {
  if (mobile) {
    // ถ้าเป็นมือถือ ให้ซ่อนคอลัมน์รายละเอียด
    columnVisibility.value = {
      ...columnVisibility.value,
      implementation_details: true,
      description: true
    };
  } else {
    // ถ้าไม่ใช่มือถือ ให้แสดงคอลัมน์รายละเอียด
    columnVisibility.value = {
      ...columnVisibility.value,
      implementation_details: false,
      description: false
    };
  }
});

// ==================== สร้างและกำหนดค่าตาราง ====================
const table = useVueTable({
  // ดึงข้อมูลและโครงสร้างคอลัมน์จาก props
  get data() { return props.data; },
  get columns() { return props.columns; },
  
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
    get sorting() { return sorting.value; },
    get columnFilters() { return columnFilters.value; },
    get columnVisibility() { return columnVisibility.value; },
    get rowSelection() { return rowSelection.value; },
    get expanded() { return expanded.value; },
    get globalFilter() { return searchQuery.value; }
  },
  
  // กำหนดฟังก์ชันสำหรับกรองข้อมูลจากการค้นหา (ค้นหาจาก control_name, description, owner, implementation_details)
  globalFilterFn: (row, columnId, filterValue) => {
    const searchValue = String(filterValue).toLowerCase();
    
    // ดึงชื่อการควบคุม
    const controlName = row.original.control_name?.toLowerCase() || '';
    
    // ดึงรายละเอียดการควบคุม
    const description = row.original.description?.toLowerCase() || '';
    
    // ดึงผู้รับผิดชอบ
    const owner = row.original.owner?.toLowerCase() || '';
    
    // ดึงรายละเอียดการดำเนินการ
    const implementationDetails = row.original.implementation_details?.toLowerCase() || '';
    
    // ดึงชื่อความเสี่ยงระดับฝ่าย (ถ้ามี)
    const divisionRiskName = row.original.division_risk?.risk_name?.toLowerCase() || '';
    
    // ดึงประเภทการควบคุม
    const controlType = getControlTypeLabel(row.original.control_type || '').toLowerCase();
    
    // ดึงสถานะการควบคุม
    const status = getStatusLabel(row.original.status).toLowerCase();
    
    // คืนค่า true ถ้าพบคำค้นหาในฟิลด์ใดฟิลด์หนึ่ง
    return controlName.includes(searchValue) || 
           description.includes(searchValue) || 
           owner.includes(searchValue) ||
           implementationDetails.includes(searchValue) ||
           divisionRiskName.includes(searchValue) ||
           controlType.includes(searchValue) ||
           status.includes(searchValue);
  },
  
  // ส่ง meta ไปใช้ใน table เพื่อเรียกใช้ callback functions
  meta: props.meta,
});

// ==================== Computed Properties ====================
// คำนวณจำนวนแถวที่ถูกเลือกสำหรับ bulk actions
const selectedRowsCount = computed(() => {
  return Object.keys(rowSelection.value).length;
});

// คำนวณรายการ ID ที่ถูกเลือกทั้งหมดสำหรับการลบหลายรายการ
const selectedRowIds = computed(() => {
  return Object.keys(rowSelection.value).map(rowIndex => {
    const row = table.getRowModel().rows[parseInt(rowIndex)];
    return row?.original?.id;
  }).filter(Boolean); // กรอง null/undefined ออก
});

// ==================== Methods ====================
// ฟังก์ชันล้างตัวกรองทั้งหมด
const clearAllFilters = () => {
  // ล้าง search query
  searchQuery.value = '';
  
  // ล้างตัวกรองคอลัมน์
  table.getAllColumns().forEach(column => {
    if (column.getCanFilter()) {
      column.setFilterValue(undefined);
    }
  });
  
  // แสดง toast แจ้งเตือน
  toast.success('ล้างตัวกรองทั้งหมดแล้ว', {
    duration: 2000
  });
};

// ฟังก์ชันสำหรับลบข้อมูลที่เลือกทั้งหมด
const handleBulkDelete = async () => {
  // ตรวจสอบว่ามีรายการที่เลือกหรือไม่
  if (!selectedRowIds.value.length) {
    toast.error('ไม่มีรายการที่เลือก');
    return;
  }
  
  // ตรวจสอบว่ามีเมธอด onBulkDelete ที่ส่งมาจาก parent component หรือไม่
  if (!props.meta?.onBulkDelete) {
    console.error('ไม่พบเมธอด onBulkDelete ใน meta');
    toast.error('เกิดข้อผิดพลาด: ไม่สามารถดำเนินการลบข้อมูลพร้อมกันได้');
    return;
  }
  
  try {
    // กำหนดสถานะกำลังลบข้อมูล
    isDeleting.value = true;
    
    // เรียกใช้ onBulkDelete จาก meta
    await props.meta.onBulkDelete(selectedRowIds.value);
    
    // รีเซ็ตการเลือกหลังจากลบสำเร็จ
    rowSelection.value = {};
  } catch (error) {
    // บันทึก log และแสดง toast error เมื่อเกิดข้อผิดพลาด
    console.error('เกิดข้อผิดพลาดในการลบข้อมูล:', error);
    toast.error('เกิดข้อผิดพลาดในการลบข้อมูล โปรดลองอีกครั้ง');
  } finally {
    // รีเซ็ตสถานะการลบข้อมูล
    isDeleting.value = false;
  }
};

// ฟังก์ชันสำหรับยกเลิกการเลือกทั้งหมด
const clearRowSelection = () => {
  rowSelection.value = {};
};

// เพิ่ม state สำหรับเก็บข้อมูลตัวกรอง
const controlTypes = ref<{ value: string; label: string; count?: number }[]>([]);
const statusOptions = ref<{ value: string; label: string; count?: number }[]>([]);
const ownerOptions = ref<{ value: string; label: string; count?: number }[]>([]);
const divisionRiskOptions = ref<{ value: string; label: string; count?: number }[]>([]);

// ฟังก์ชันสำหรับสร้างตัวกรองจากข้อมูล
const generateFilters = () => {
  // สร้างตัวกรองสำหรับประเภทการควบคุม
  const typesMap = new Map<string, number>();
  props.data.forEach(item => {
    if (item.control_type) {
      typesMap.set(item.control_type, (typesMap.get(item.control_type) || 0) + 1);
    }
  });
  controlTypes.value = Array.from(typesMap.entries())
    .map(([value, count]) => ({
      value,
      label: getControlTypeLabel(value),
      count
    }))
    .sort((a, b) => a.label.localeCompare(b.label));

  // สร้างตัวกรองสำหรับสถานะ
  const statusMap = new Map<string, number>();
  props.data.forEach(item => {
    if (item.status) {
      statusMap.set(item.status, (statusMap.get(item.status) || 0) + 1);
    }
  });
  statusOptions.value = Array.from(statusMap.entries())
    .map(([value, count]) => ({
      value,
      label: getStatusLabel(value),
      count
    }))
    .sort((a, b) => a.label.localeCompare(b.label));

  // สร้างตัวกรองสำหรับผู้รับผิดชอบ
  const ownerMap = new Map<string, number>();
  props.data.forEach(item => {
    if (item.owner) {
      ownerMap.set(item.owner, (ownerMap.get(item.owner) || 0) + 1);
    }
  });
  ownerOptions.value = Array.from(ownerMap.entries())
    .map(([value, count]) => ({
      value,
      label: value,
      count
    }))
    .sort((a, b) => a.label.localeCompare(b.label));

  // สร้างตัวกรองสำหรับความเสี่ยงระดับฝ่าย
  const divisionRiskMap = new Map<string, number>();
  props.data.forEach(item => {
    if (item.division_risk?.risk_name) {
      const riskName = item.division_risk.risk_name;
      divisionRiskMap.set(riskName, (divisionRiskMap.get(riskName) || 0) + 1);
    }
  });
  divisionRiskOptions.value = Array.from(divisionRiskMap.entries())
    .map(([value, count]) => ({
      value,
      label: value,
      count
    }))
    .sort((a, b) => a.label.localeCompare(b.label));
};

// เรียกใช้ฟังก์ชันสร้างตัวกรองเมื่อข้อมูลเปลี่ยนแปลง
watch(() => props.data, () => {
  generateFilters();
}, { immediate: true, deep: true });

// เพิ่มฟังก์ชันสำหรับกรองตามความเสี่ยงระดับฝ่าย
const filterByDivisionRisk = (row: any, id: string, filterValues: string[]) => {
  if (!filterValues?.length) return true;
  const riskName = row.original.division_risk?.risk_name;
  return riskName && filterValues.includes(riskName);
};

// คำนวณว่ามีตัวกรองที่ใช้งานอยู่หรือไม่
const hasActiveTagFilters = computed(() => {
  // ตรวจสอบว่ามีการใช้ column filters หรือไม่
  return table.getState().columnFilters.length > 0;
});

// ==================== Emit Events ====================
// ส่งสัญญาณการเปลี่ยนแปลงการเลือกไปยัง parent component
const emit = defineEmits<{
  selectionChange: [selectedIds: number[]]
}>();

// Watch การเปลี่ยนแปลงของ rowSelection และส่งสัญญาณไปยัง parent
watch(selectedRowIds, (newIds) => {
  emit('selectionChange', newIds);
}, { deep: true });
</script>

<template>
  <!-- ส่วนหัวตารางสำหรับการค้นหาและกรอง -->
  <div class="flex flex-wrap items-center gap-2 py-4">
    <!-- ช่องค้นหา -->
    <Input
      class="max-w-xs sm:max-w-sm"
      placeholder="ค้นหาจากชื่อการควบคุม, รายละเอียด, ผู้รับผิดชอบ หรือความเสี่ยง..."
      v-model="searchQuery"
    />

    <!-- ปุ่มล้างตัวกรอง -->
    <Button
      v-if="searchQuery || hasActiveTagFilters"
      variant="ghost"
      class="ml-2"
      @click="clearAllFilters"
      size="sm"
    >
      ล้างตัวกรอง
    </Button>

    <!-- เพิ่มส่วนของ TagFilter -->
    <div class="flex flex-wrap items-center gap-2 mt-2 w-full">
      <!-- กรองตามประเภทการควบคุม -->
      <TagFilter
        v-if="controlTypes.length > 0"
        title="ประเภทการควบคุม"
        column="control_type"
        :options="controlTypes"
        :table="table"
      />
      
      <!-- กรองตามสถานะ -->
      <TagFilter
        v-if="statusOptions.length > 0"
        title="สถานะ"
        column="status"
        :options="statusOptions"
        :table="table"
      />
      
      <!-- กรองตามผู้รับผิดชอบ -->
      <TagFilter
        v-if="ownerOptions.length > 0"
        title="ผู้รับผิดชอบ"
        column="owner"
        :options="ownerOptions"
        :table="table"
      />
      
      <!-- กรองตามความเสี่ยงระดับฝ่าย -->
      <TagFilter
        v-if="divisionRiskOptions.length > 0"
        title="ความเสี่ยงระดับฝ่าย"
        column="division_risk"
        :options="divisionRiskOptions"
        :table="table"
        :filter-fn="filterByDivisionRisk"
      />
    </div>

    <!-- กลุ่มด้านขวา: จัดให้ปุ่ม View และปุ่มตัวเลือกอยู่ด้วยกัน -->
    <div class="flex items-center gap-2 ml-auto">
      <!-- BulkActionMenu (ตัวเลือก) -->
      <BulkActionMenu 
        v-if="selectedRowsCount > 0"
        :count="selectedRowsCount"
        :loading="isDeleting || isLoading"
        @delete="handleBulkDelete"
        @clear="clearRowSelection"
        @export="() => {}"
      />
      
      <!-- ปุ่ม View อยู่ซ้าย ถัดมาเป็นปุ่มตัวเลือก -->
      <DataTableViewOptions :table="table" />
    </div>
  </div>
  
  <!-- ตารางข้อมูล -->
  <div class="border rounded-md">
    <!-- แสดง Loading overlay เมื่อกำลังโหลดข้อมูล -->
    <div v-if="isLoading" class="relative">
      <div class="absolute inset-0 bg-gray-50 bg-opacity-50 flex items-center justify-center z-10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    </div>
    
    <Table>
      <!-- ส่วนหัวคอลัมน์ -->
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
              <div class="flex flex-col items-center justify-center space-y-2">
                <div class="text-gray-500">
                  {{ isLoading ? 'กำลังโหลดข้อมูล...' : 'ไม่พบข้อมูลการควบคุมความเสี่ยง' }}
                </div>
                <div v-if="!isLoading && searchQuery" class="text-sm text-gray-400">
                  ลองใช้คำค้นหาอื่น หรือล้างตัวกรองเพื่อดูข้อมูลทั้งหมด
                </div>
              </div>
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

<style scoped>
/* Animation สำหรับ loading */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* สไตล์สำหรับแถวที่เลือก */
[data-state="selected"] {
  background-color: rgba(59, 130, 246, 0.1);
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .flex-wrap {
    gap: 0.5rem;
  }
}
</style>
