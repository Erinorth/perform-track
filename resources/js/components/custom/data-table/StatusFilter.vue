<script setup lang="ts">
import { h } from 'vue'
import type { Column } from '@tanstack/vue-table'
import type { Payment } from '@/types/payment'
import { useStatusFilter } from '@/composables/useTableFilters'
import { Filter, X } from 'lucide-vue-next'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'

// Define props
interface Props {
  column: Column<Payment, any>
}

const props = defineProps<Props>()

// ใช้ composable สำหรับจัดการ status filter
const { selectedValues, facetedValues, toggleValue, clearFilter } = useStatusFilter(props.column)
</script>

<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        :variant="selectedValues.length > 0 ? 'default' : 'outline'"
        size="sm"
        class="h-8"
      >
        <Filter class="w-4 h-4 mr-2" />
        สถานะ
        <Badge
          v-if="selectedValues.length > 0"
          variant="secondary"
          class="ml-2 rounded-sm px-1 font-normal"
        >
          {{ selectedValues.length }}
        </Badge>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-[280px] p-0" align="start">
      <div class="p-4 space-y-3">
        <!-- Header พร้อม Clear button -->
        <div class="flex items-center justify-between">
          <h4 class="font-medium text-sm">กรองตามสถานะ</h4>
          <Button
            v-if="selectedValues.length > 0"
            variant="ghost"
            size="sm"
            @click="clearFilter"
            class="h-auto p-1 text-muted-foreground hover:text-foreground"
          >
            <X class="w-3 h-3 mr-1" />
            ล้าง
          </Button>
        </div>
        
        <!-- Status options -->
        <div class="space-y-2">
          <div
            v-for="option in facetedValues"
            :key="option.value"
            class="flex items-center justify-between space-x-3 p-2 rounded-md hover:bg-muted/50 cursor-pointer"
            @click="toggleValue(option.value)"
          >
            <div class="flex items-center space-x-3">
              <label
                :for="`status-${option.value}`"
                class="text-sm font-normal cursor-pointer select-none"
              >
                {{ option.label }}
              </label>
            </div>
            <span class="text-xs text-muted-foreground bg-muted px-2 py-1 rounded-full">
              {{ option.count }}
            </span>
          </div>
        </div>
        
        <!-- แสดงสถานะที่เลือกอยู่ -->
        <div
          v-if="selectedValues.length > 0"
          class="pt-2 mt-2 border-t text-xs text-muted-foreground"
        >
          <span>กำลังกรอง: {{ selectedValues.length }} สถานะ</span>
          <div class="flex flex-wrap gap-1 mt-1">
            <Badge
              v-for="value in selectedValues"
              :key="value"
              variant="secondary"
              class="text-xs px-1.5 py-0.5"
            >
              {{ facetedValues.find(f => f.value === value)?.label || value }}
            </Badge>
          </div>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>
