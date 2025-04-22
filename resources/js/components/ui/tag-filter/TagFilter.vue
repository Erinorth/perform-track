// ไฟล์: resources/js/components/ui/TagFilter.vue
<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Check, Search, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator'

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

const emit = defineEmits(['update:selectedTags']);

const isOpen = ref(false);

const selectedValues = ref<string[]>([]);

// ติดตามการเปลี่ยนแปลงของ filter จาก table
watch(() => props.table.getColumn(props.column)?.getFilterValue(), (value) => {
  selectedValues.value = value || [];
  emit('update:selectedTags', selectedValues.value);
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
  emit('update:selectedTags', values.length ? values : []);
};

const clearFilter = () => {
  selectedValues.value = [];
  props.table.getColumn(props.column)?.setFilterValue(undefined);
  isOpen.value = false;
  emit('update:selectedTags', []);
};

const selectedCount = computed(() => selectedValues.value.length);

const getSelectedTags = computed(() => {
  return props.options.filter(option => selectedValues.value.includes(option.value));
});
</script>

<template>
  <div class="flex flex-col gap-2">
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
          <!-- แสดง Selected Tags -->
        <div v-if="selectedCount > 0" class="flex flex-wrap gap-1.5">
            <Separator orientation="vertical" />
        <Badge 
            v-for="tag in getSelectedTags" 
            :key="tag.value"
            variant="secondary"
            class="flex items-center gap-1 px-2 py-1 text-xs bg-muted"
        >
            {{ tag.label }}
        </Badge>
        </div>
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
                <div class="flex items-center gap-1">
                  <span>{{ option.label }}</span>
                </div>
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
  </div>
</template>
