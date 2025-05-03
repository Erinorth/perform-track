<!-- ไฟล์: resources\js\components\ui\data-table\TagFilter.vue -->
<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Check, Search } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import { toast } from 'vue-sonner';

// ประกาศโครงสร้างข้อมูลสำหรับตัวเลือกการกรอง
interface FilterOption {
  value: string;
  label: string;
  count?: number;
}

// กำหนด props ที่รับเข้ามา
const props = defineProps<{
  title: string;
  column: string;
  options: FilterOption[];
  table: any;
}>();

// กำหนด events ที่ส่งออกไป
const emit = defineEmits(['update:selectedTags']);

// สถานะการเปิด/ปิด popover
const isOpen = ref(false);

// ค่าที่ถูกเลือกปัจจุบัน
const selectedValues = ref<string[]>([]);

// ข้อความสำหรับค้นหาตัวกรอง
const searchQuery = ref('');

// บันทึกข้อมูลเมื่อ component ถูกสร้าง
onMounted(() => {
  console.log(`[TagFilter] คอมโพเนนต์ถูกสร้าง - ${props.title}`);
  console.log(`[TagFilter] จำนวนตัวเลือกเริ่มต้น: ${props.options?.length || 0}`);
});

// ติดตามการเปลี่ยนแปลงของ filter จาก table
watch(() => props.table.getColumn(props.column)?.getFilterValue(), (value) => {
  console.log(`[TagFilter] ค่าตัวกรองเปลี่ยนแปลง: ${JSON.stringify(value)}`);
  selectedValues.value = value || [];
  emit('update:selectedTags', selectedValues.value);
}, { immediate: true });

// กรองตัวเลือกตามข้อความค้นหา
const filteredOptions = computed(() => {
  if (!props.options || props.options.length === 0) {
    console.log('[TagFilter] ไม่มีตัวเลือกที่ใช้ได้');
    return [];
  }
  
  if (!searchQuery.value) return props.options;
  
  const filtered = props.options.filter(option => 
    option.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
  
  console.log(`[TagFilter] ตัวเลือกที่กรองแล้ว: ${filtered.length}/${props.options.length}`);
  return filtered;
});

// จัดการเมื่อมีการเลือกหรือยกเลิกตัวกรอง
const handleSelect = (value: string) => {
  const values = [...selectedValues.value];
  const index = values.indexOf(value);
  
  if (index !== -1) {
    values.splice(index, 1);
    console.log(`[TagFilter] ลบตัวกรอง: ${value}`);
  } else {
    values.push(value);
    console.log(`[TagFilter] เพิ่มตัวกรอง: ${value}`);
  }
  
  props.table.getColumn(props.column)?.setFilterValue(values.length ? values : undefined);
  emit('update:selectedTags', values.length ? values : []);
  
  // แสดงการแจ้งเตือนเมื่อเปลี่ยนแปลงตัวกรอง
  if (values.length) {
    toast.success(`กรองข้อมูลจำนวน ${values.length} รายการ`);
  } else {
    toast.info('ล้างตัวกรองทั้งหมด');
  }
};

// ล้างตัวกรองทั้งหมด
const clearFilter = () => {
  selectedValues.value = [];
  props.table.getColumn(props.column)?.setFilterValue(undefined);
  isOpen.value = false;
  emit('update:selectedTags', []);
  console.log('[TagFilter] ล้างตัวกรองทั้งหมด');
  toast.info('ล้างตัวกรองทั้งหมด');
};

// นับจำนวนตัวกรองที่เลือก
const selectedCount = computed(() => selectedValues.value.length);

// ดึงตัวกรองที่เลือกไว้มาแสดง
const getSelectedTags = computed(() => {
  return props.options?.filter(option => selectedValues.value.includes(option.value)) || [];
});
</script>

<template>
  <div class="flex flex-col gap-2">
    <Popover v-model:open="isOpen">
      <PopoverTrigger as-child>
        <!-- ปุ่มสำหรับเปิดตัวกรอง - ปรับขนาดให้เหมาะสมกับหน้าจอ -->
        <Button 
          variant="outline"
          :class="[
            'h-9 border-dashed min-w-[120px] sm:min-w-[150px]',
            selectedCount > 0 ? 'text-foreground font-medium' : 'text-muted-foreground'
          ]"
        >
          <!-- ชื่อตัวกรอง -->
          <span class="truncate">{{ title }}</span>
          
          <!-- แสดงตัวกรองที่เลือกแล้ว - ปรับแต่งสำหรับหน้าจอขนาดเล็ก -->
          <div v-if="selectedCount > 0" class="flex flex-nowrap overflow-hidden ml-2 max-w-[100px] sm:max-w-[200px]">
            <Separator class="mx-2" orientation="vertical" />
            <div class="flex flex-nowrap items-center gap-1 overflow-hidden">
              <Badge 
                v-for="tag in getSelectedTags.slice(0, 2)" 
                :key="tag.value"
                variant="secondary"
                class="flex items-center gap-1 px-2 py-0.5 text-xs bg-muted whitespace-nowrap"
              >
                {{ tag.label }}
              </Badge>
              
              <!-- แสดงจำนวนที่เหลือ ถ้ามีการเลือกมากกว่า 2 รายการ -->
              <Badge 
                v-if="selectedCount > 2" 
                variant="secondary"
                class="flex items-center px-2 py-0.5 text-xs bg-primary text-primary-foreground"
              >
                +{{ selectedCount - 2 }}
              </Badge>
            </div>
          </div>
        </Button>
      </PopoverTrigger>
      
      <!-- เนื้อหาของ Popover - ปรับขนาดตามหน้าจอ -->
      <PopoverContent class="w-[250px] max-w-[90vw] p-0 sm:w-[300px]" align="start">
        <div class="p-2">
          <!-- ช่องค้นหาตัวกรอง -->
          <div class="relative px-2 mb-2">
            <Search class="absolute left-4 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              v-model="searchQuery"
              class="h-8 pl-8"
              :placeholder="`ค้นหา${title}`"
            />
          </div>
          
          <!-- แสดงข้อความเมื่อไม่มีตัวเลือก -->
          <div v-if="filteredOptions.length === 0" class="px-2 py-4 text-center text-sm text-muted-foreground">
            ไม่พบรายการที่ตรงกับการค้นหา
          </div>
          
          <!-- รายการตัวเลือกสำหรับกรอง -->
          <div v-else class="border-t pt-2 space-y-1 mt-2 max-h-[200px] overflow-y-auto">
            <div
              v-for="option in filteredOptions"
              :key="option.value"
              @click="handleSelect(option.value)"
              class="flex items-center justify-between px-2 py-1.5 text-sm rounded-md cursor-pointer hover:bg-accent"
            >
              <div class="flex items-center gap-2">
                <!-- ช่องเลือก -->
                <div class="flex-shrink-0 w-4 h-4 border rounded-sm" :class="selectedValues.includes(option.value) ? 'bg-primary border-primary' : 'border-input'">
                  <Check v-if="selectedValues.includes(option.value)" class="w-3 h-3 text-primary-foreground" />
                </div>
                <!-- ชื่อตัวเลือก -->
                <div class="flex items-center gap-1">
                  <span class="truncate max-w-[150px]">{{ option.label }}</span>
                </div>
              </div>
              <!-- จำนวนรายการ (ถ้ามี) -->
              <span v-if="option.count !== undefined" class="text-muted-foreground text-xs">{{ option.count }}</span>
            </div>
          </div>
          
          <!-- ปุ่มล้างตัวกรอง -->
          <div class="border-t mt-2 pt-2">
            <Button
              variant="ghost"
              class="w-full justify-center text-sm"
              @click="clearFilter"
            >
              ล้างตัวกรอง
            </Button>
          </div>
        </div>
      </PopoverContent>
    </Popover>
  </div>
</template>
