<script setup lang="ts">
import { computed } from 'vue'
import type { Column } from '@tanstack/vue-table'
import type { Payment } from '@/types/payment'
import type { AmountFilter } from '@/types/payment'
import { useAmountRangeFilter } from '@/composables/useTableFilters'
import { Filter, X } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

// Props definition
interface Props {
  column: Column<Payment, any>
}

const props = defineProps<Props>()

// ใช้ composable สำหรับจัดการ amount range filter
const { filterValue, facetedMinMax, hasFilter } = useAmountRangeFilter(props.column)

// Enhanced update filter function พร้อม toast notification
const updateFilter = () => {
  const hasValues = filterValue.value.min !== undefined || filterValue.value.max !== undefined
  props.column.setFilterValue(hasValues ? { ...filterValue.value } : undefined)
  
  if (hasValues) {
    const minText = filterValue.value.min ? `$${filterValue.value.min}` : '$0'
    const maxText = filterValue.value.max ? `$${filterValue.value.max}` : 'ไม่จำกัด'
    toast.success(`กรองจำนวน: ${minText} - ${maxText}`)
  }
}

// Enhanced clear filter function พร้อม toast notification
const clearFilter = () => {
  filterValue.value = {}
  props.column.setFilterValue(undefined)
  toast.info('ล้างการกรองจำนวนเงิน')
}

// Handle input updates พร้อม validation
const handleMinUpdate = (value: string | number) => {
  filterValue.value.min = value ? Number(value) : undefined
  updateFilter()
}

const handleMaxUpdate = (value: string | number) => {
  filterValue.value.max = value ? Number(value) : undefined
  updateFilter()
}
</script>

<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        :variant="hasFilter ? 'default' : 'outline'"
        size="sm"
        class="h-8"
      >
        <Filter class="w-4 h-4 mr-2" />
        จำนวน
        <Badge
          v-if="hasFilter"
          variant="secondary"
          class="ml-2 rounded-sm px-1 font-normal"
        >
          •
        </Badge>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-[320px] p-0" align="start">
      <div class="p-4 space-y-4">
        <!-- Header พร้อมปุ่มล้าง -->
        <div class="flex items-center justify-between">
          <h4 class="font-medium text-sm">กรองตามจำนวนเงิน</h4>
          <Button
            v-if="hasFilter"
            variant="ghost"
            size="sm"
            @click="clearFilter"
            class="h-auto p-1"
          >
            <X class="w-3 h-3" />
          </Button>
        </div>

        <!-- แสดงช่วงข้อมูลที่มีอยู่ -->
        <div class="text-xs text-muted-foreground mb-2">
          ช่วง: ${{ facetedMinMax[0] }} - ${{ facetedMinMax[1] }}
        </div>

        <!-- Input fields แบบ 2 คอลัมน์ -->
        <div class="grid grid-cols-2 gap-2">
          <!-- จำนวนขั้นต่ำ -->
          <div class="space-y-2">
            <label class="text-xs text-muted-foreground">จำนวนขั้นต่ำ</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">$</span>
              <Input
                type="number"
                :placeholder="`${facetedMinMax[0]}`"
                :min="facetedMinMax[0]"
                :max="facetedMinMax[1]"
                :value="filterValue.min?.toString() || ''"
                @update:model-value="handleMinUpdate"
                class="pl-7"
              />
            </div>
          </div>

          <!-- จำนวนสูงสุด -->
          <div class="space-y-2">
            <label class="text-xs text-muted-foreground">จำนวนสูงสุด</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">$</span>
              <Input
                type="number"
                :placeholder="`${facetedMinMax[1]}`"
                :min="facetedMinMax[0]"
                :max="facetedMinMax[1]"
                :value="filterValue.max?.toString() || ''"
                @update:model-value="handleMaxUpdate"
                class="pl-7"
              />
            </div>
          </div>
        </div>

        <!-- แสดงสถานะการกรองปัจจุบัน -->
        <div
          v-if="hasFilter"
          class="pt-2 mt-2 border-t text-xs text-muted-foreground"
        >
          <div class="flex items-center justify-between">
            <span>กำลังกรอง:</span>
            <span class="font-medium">
              ${{ filterValue.min || facetedMinMax[0] }} - 
              ${{ filterValue.max || facetedMinMax[1] }}
            </span>
          </div>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>
