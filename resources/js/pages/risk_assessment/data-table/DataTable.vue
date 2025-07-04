<!--
  ไฟล์: resources\js\pages\risk_assessment\data-table\DataTable.vue
  
  คำอธิบาย: Component หลักสำหรับแสดงตารางข้อมูลการประเมินความเสี่ยง
  ฟีเจอร์หลัก:
  - การค้นหาข้อมูลจาก assessment_year, assessment_period และ notes
  - การเรียงลำดับข้อมูลในแต่ละคอลัมน์
  - การแบ่งหน้าเพื่อแสดงผล
  - การขยายแถวเพื่อดูรายละเอียดเพิ่มเติม
  - การเลือกแถวหลายรายการเพื่อลบพร้อมกัน
  - รองรับการแสดงผลแบบ Responsive บนทุกขนาดหน้าจอ
-->

<script setup lang="ts" generic="TData extends RiskAssessment, TValue">
// ==================== นำเข้า Types และ Interfaces ====================
// นำเข้า types สำหรับโมเดลข้อมูลการประเมินความเสี่ยง
import type { DivisionRisk, RiskAssessment } from '@/types/types';

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
}>();

// ==================== ฟังก์ชันช่วยสำหรับการจัดการงวดการประเมิน ====================
// ฟังก์ชันสำหรับจัดรูปแบบงวดการประเมิน
const formatAssessmentPeriod = (year: number, period: number): string => {
  const periodText = period === 1 ? 'ครึ่งปีแรก' : 'ครึ่งปีหลัง';
  const monthRange = period === 1 ? '(ม.ค.-มิ.ย.)' : '(ก.ค.-ธ.ค.)';
  return `${periodText} ${year} ${monthRange}`;
};

// ฟังก์ชันสำหรับตรวจสอบว่าอยู่ในงวดไหน
const getCurrentPeriod = (): { year: number; period: number } => {
  const now = new Date();
  const month = now.getMonth() + 1; // getMonth() returns 0-11
  const year = now.getFullYear();
  const period = month <= 6 ? 1 : 2;
  return { year, period };
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
  // ซ่อนคอลัมน์ notes บนมือถือ
  notes: isMobile.value
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
    // ถ้าเป็นมือถือ ให้ซ่อนคอลัมน์ notes 
    columnVisibility.value = {
      ...columnVisibility.value,
      notes: true
    };
  } else {
    // ถ้าไม่ใช่มือถือ ให้แสดงคอลัมน์ notes
    columnVisibility.value = {
      ...columnVisibility.value,
      notes: false
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
  
  // กำหนดฟังก์ชันสำหรับกรองข้อมูลจากการค้นหา (ค้นหาเฉพาะ division_risk.risk_name และ notes)
  globalFilterFn: (row, columnId, filterValue) => {
    const searchValue = String(filterValue).toLowerCase();
    
    // ดึงชื่อความเสี่ยงระดับฝ่าย (ตรวจสอบว่ามี division_risk และ risk_name หรือไม่)
    const riskName = row.original.division_risk?.risk_name?.toLowerCase() || '';
    
    // ดึงบันทึก (notes)
    const notes = row.original.notes?.toLowerCase() || '';
    
    // ดึงข้อมูลงวดการประเมินเพื่อการค้นหา
    const assessmentPeriod = formatAssessmentPeriod(
      row.original.assessment_year, 
      row.original.assessment_period
    ).toLowerCase();
    
    // คืนค่า true ถ้าพบคำค้นหาในชื่อความเสี่ยงระดับฝ่าย, บันทึก, หรืองวดการประเมิน
    return riskName.includes(searchValue) || 
           notes.includes(searchValue) || 
           assessmentPeriod.includes(searchValue);
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
const assessmentPeriods = ref<{ value: string; label: string; count?: number }[]>([]);
const likelihoodLevels = ref<{ value: string; label: string; count?: number }[]>([]);
const impactLevels = ref<{ value: string; label: string; count?: number }[]>([]);
const riskScoreLevels = ref<{ value: string; label: string; count?: number }[]>([]);

// ฟังก์ชันสำหรับสร้างตัวกรองจากข้อมูล
const generateFilters = () => {
  // สร้างตัวกรองสำหรับงวดการประเมิน (ปี + งวด)
  const periodsMap = new Map<string, number>();
  props.data.forEach(item => {
    if (item.assessment_year && item.assessment_period) {
      const key = `${item.assessment_year}-${item.assessment_period}`;
      periodsMap.set(key, (periodsMap.get(key) || 0) + 1);
    }
  });
  assessmentPeriods.value = Array.from(periodsMap.entries())
    .map(([value, count]) => {
      const [year, period] = value.split('-');
      return {
        value,
        label: formatAssessmentPeriod(Number(year), Number(period)),
        count
      };
    })
    .sort((a, b) => b.value.localeCompare(a.value));

  // สร้างตัวกรองสำหรับระดับโอกาสเกิด
  const likelihoodMap = new Map<string, number>();
  props.data.forEach(item => {
    if (item.likelihood_level) {
      const key = item.likelihood_level.toString();
      likelihoodMap.set(key, (likelihoodMap.get(key) || 0) + 1);
    }
  });
  likelihoodLevels.value = Array.from(likelihoodMap.entries())
    .map(([value, count]) => ({
      value,
      label: `ระดับ ${value}`,
      count
    }))
    .sort((a, b) => parseInt(a.value) - parseInt(b.value));

  // สร้างตัวกรองสำหรับระดับผลกระทบ
  const impactMap = new Map<string, number>();
  props.data.forEach(item => {
    if (item.impact_level) {
      const key = item.impact_level.toString();
      impactMap.set(key, (impactMap.get(key) || 0) + 1);
    }
  });
  impactLevels.value = Array.from(impactMap.entries())
    .map(([value, count]) => ({
      value,
      label: `ระดับ ${value}`,
      count
    }))
    .sort((a, b) => parseInt(a.value) - parseInt(b.value));

  // สร้างตัวกรองสำหรับคะแนนความเสี่ยง
  const scoreMap = new Map<string, number>();
  props.data.forEach(item => {
    if (item.risk_score) {
      const score = item.risk_score;
      let level = '';
      
      // แบ่งระดับความเสี่ยงตามคะแนน
      if (score >= 9 && score <= 16) {
        level = 'high';
      } else if (score >= 4 && score <= 8) {
        level = 'medium';
      } else if (score >= 1 && score <= 3) {
        level = 'low';
      }
      
      if (level) {
        scoreMap.set(level, (scoreMap.get(level) || 0) + 1);
      }
    }
  });
  
  riskScoreLevels.value = [
    { value: 'high', label: 'สูง (9-16)', count: scoreMap.get('high') || 0 },
    { value: 'medium', label: 'กลาง (4-8)', count: scoreMap.get('medium') || 0 },
    { value: 'low', label: 'ต่ำ (1-3)', count: scoreMap.get('low') || 0 }
  ];
};

// เรียกใช้ฟังก์ชันสร้างตัวกรองเมื่อข้อมูลเปลี่ยนแปลง
watch(() => props.data, () => {
  generateFilters();
}, { immediate: true, deep: true });

// เพิ่มฟังก์ชันสำหรับกรองตามงวดการประเมิน
const filterByPeriod = (row: any, id: string, filterValues: string[]) => {
  if (!filterValues?.length) return true;
  const year = row.original.assessment_year;
  const period = row.original.assessment_period;
  const key = `${year}-${period}`;
  return filterValues.includes(key);
};

// เพิ่มฟังก์ชันสำหรับกรองตามระดับความเสี่ยง
const filterByRiskScore = (row: any, id: string, filterValues: string[]) => {
  if (!filterValues?.length) return true;
  
  const score = row.getValue(id);
  let level = '';
  
  if (score >= 9 && score <= 16) {
    level = 'high';
  } else if (score >= 4 && score <= 8) {
    level = 'medium';
  } else if (score >= 1 && score <= 3) {
    level = 'low';
  }
  
  return filterValues.includes(level);
};

// คำนวณว่ามีตัวกรองที่ใช้งานอยู่หรือไม่
const hasActiveTagFilters = computed(() => {
  // ตรวจสอบว่ามีการใช้ column filters หรือไม่
  return table.getState().columnFilters.length > 0;
});
</script>

<template>
  <!-- ส่วนหัวตารางสำหรับการค้นหาและกรอง -->
  <div class="flex flex-wrap items-center gap-2 py-4">
    <!-- ช่องค้นหา -->
    <Input
      class="max-w-xs sm:max-w-sm"
      placeholder="ค้นหาจากชื่อความเสี่ยงระดับฝ่าย, งวดการประเมิน หรือบันทึก..."
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
      <!-- กรองตามงวดการประเมิน -->
      <TagFilter
        v-if="assessmentPeriods.length > 0"
        title="งวดการประเมิน"
        column="assessment_period"
        :options="assessmentPeriods"
        :table="table"
        :filter-fn="filterByPeriod"
      />
      
      <!-- กรองตามระดับโอกาสเกิด -->
      <TagFilter
        v-if="likelihoodLevels.length > 0"
        title="โอกาสเกิด"
        column="likelihood_level"
        :options="likelihoodLevels"
        :table="table"
      />
      
      <!-- กรองตามระดับผลกระทบ -->
      <TagFilter
        v-if="impactLevels.length > 0"
        title="ผลกระทบ"
        column="impact_level"
        :options="impactLevels"
        :table="table"
      />
      
      <!-- กรองตามระดับความเสี่ยง -->
      <TagFilter
        v-if="riskScoreLevels.length > 0"
        title="ระดับความเสี่ยง"
        column="risk_score"
        :options="riskScoreLevels"
        :table="table"
        :filter-fn="filterByRiskScore"
      />
    </div>

    <!-- กลุ่มด้านขวา: จัดให้ปุ่ม View และปุ่มตัวเลือกอยู่ด้วยกัน -->
    <div class="flex items-center gap-2 ml-auto">
      <!-- BulkActionMenu (ตัวเลือก) -->
      <BulkActionMenu 
        v-if="selectedRowsCount > 0"
        :count="selectedRowsCount"
        :loading="isDeleting"
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
