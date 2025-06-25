<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Column } from '@tanstack/vue-table'
import type { Payment } from '@/types/payment'
import { Search, X } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'

// Props definition
interface Props {
  column: Column<Payment, any>
}

const props = defineProps<Props>()

// Reactive state สำหรับจัดการ filter
const inputValue = ref(props.column.getFilterValue() as string || '')

// ฟังก์ชันจัดการการเปลี่ยนแปลงของ input
const handleInputChange = (value: string | number) => {
  const stringValue = String(value)
  inputValue.value = stringValue
  props.column.setFilterValue(stringValue)
}

// ฟังก์ชันล้าง filter
const clearFilter = () => {
  inputValue.value = ''
  props.column.setFilterValue('')
  toast.info('ล้างการกรองอีเมล')
}

// ตรวจสอบว่ามี filter หรือไม่
const hasFilter = computed(() => inputValue.value !== '')

// ฟร็อม events
const handleKeydown = (event: KeyboardEvent) => {
  // กด Escape เพื่อล้าง filter
  if (event.key === 'Escape' && hasFilter.value) {
    clearFilter()
    event.preventDefault()
  }
}
</script>

<template>
  <div class="relative max-w-sm">
    <!-- Input Field พร้อม Search Icon และ Clear Button -->
    <div class="relative">
      <Input
        :value="inputValue"
        @update:model-value="handleInputChange"
        @keydown="handleKeydown"
        placeholder="กรองอีเมล..."
        class="pr-8"
        type="email"
      />
      
      <!-- Search Icon หรือ Clear Button -->
      <div class="absolute right-3 top-1/2 -translate-y-1/2">
        <Button
          v-if="hasFilter"
          variant="ghost"
          size="sm"
          @click="clearFilter"
          class="h-6 w-6 p-0 hover:bg-muted"
          title="ล้างการกรอง (Esc)"
        >
          <X class="h-3 w-3" />
        </Button>
        <Search 
          v-else
          class="h-4 w-4 text-muted-foreground" 
        />
      </div>
    </div>

    <!-- Status Indicator -->
    <div
      v-if="hasFilter"
      class="mt-1 text-xs text-muted-foreground flex items-center gap-1"
    >
      <span>กรองด้วย:</span>
      <span class="font-medium text-foreground">{{ inputValue }}</span>
    </div>
  </div>
</template>
