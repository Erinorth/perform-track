// ไฟล์: resources/js/components/ui/TagFilter.vue
<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Check } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

interface FilterOption {
  value: string;
  label: string;
  count?: number;
}

const props = defineProps<{
  title: string;
  column: string;
  options: FilterOption[];
  table: any;
}>();

const isOpen = ref(false);

const selectedValues = ref<string[]>([]);

// ติดตามการเปลี่ยนแปลงของ filter จาก table
watch(() => props.table.getColumn(props.column)?.getFilterValue(), (value) => {
  selectedValues.value = value || [];
}, { immediate: true });

const handleSelect = (value: string) => {
  const values = [...selectedValues.value];
  const index = values.indexOf(value);
  
  if (index !== -1) {
    values.splice(index, 1);
  } else {
    values.push(value);
  }
  
  props.table.getColumn(props.column)?.setFilterValue(values.length ? values : undefined);
};

const clearFilter = () => {
  selectedValues.value = [];
  props.table.getColumn(props.column)?.setFilterValue(undefined);
  isOpen.value = false;
};

const selectedCount = computed(() => selectedValues.value.length);
</script>

<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button 
        variant="outline" 
        :class="[
          'h-9 border-dashed',
          selectedCount > 0 ? 'text-foreground font-medium' : 'text-muted-foreground'
        ]"
      >
        {{ title }}
        <span v-if="selectedCount > 0" class="ml-1 rounded-full bg-primary/10 px-1.5 py-0.5 text-xs font-semibold text-primary">
          {{ selectedCount }}
        </span>
      </Button>
    </PopoverTrigger>
    
    <PopoverContent class="w-[200px] p-0" align="start">
      <div class="p-2">
        <div class="px-2 py-1.5 text-sm font-semibold">
          {{ title }}
        </div>
        <div class="space-y-1 mt-2">
          <div
            v-for="option in options"
            :key="option.value"
            @click="handleSelect(option.value)"
            class="flex items-center justify-between px-2 py-1.5 text-sm rounded-md cursor-pointer hover:bg-accent"
          >
            <div class="flex items-center gap-2">
              <div class="flex-shrink-0 w-4 h-4 border rounded-sm" :class="selectedValues.includes(option.value) ? 'bg-primary border-primary' : 'border-input'">
                <Check v-if="selectedValues.includes(option.value)" class="w-3 h-3 text-primary-foreground" />
              </div>
              <span>{{ option.label }}</span>
            </div>
            <span v-if="option.count !== undefined" class="text-muted-foreground text-xs">{{ option.count }}</span>
          </div>
        </div>
        <div class="border-t mt-2 pt-2">
          <Button
            variant="ghost"
            class="w-full justify-center text-sm"
            @click="clearFilter"
          >
            Clear filters
          </Button>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>
