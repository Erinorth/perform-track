<!-- ไฟล์: resources/js/components/data-table/DataTableSearch.vue -->
<script setup lang="ts">
// ใช้ v-model เพื่อให้ reactivity สมบูรณ์
import { computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Search, X } from 'lucide-vue-next'

// กำหนด props และ events
const props = defineProps<{
  modelValue: string
  columns: string[]
  placeholder?: string
}>()

const emit = defineEmits(['update:modelValue'])

// สร้าง computed property สำหรับ two-way binding
const internalValue = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

// ฟังก์ชันล้างค่าการค้นหา
function clear() {
  emit('update:modelValue', '')
}
</script>

<template>
  <div class="flex items-center gap-2 max-w-sm">
    <!-- Input field พร้อม search icon -->
    <div class="relative flex-1">
      <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
      <Input
        v-model="internalValue"
        :placeholder="placeholder || `ค้นหา ${columns.join(' หรือ ')}`"
        class="pl-10 pr-10"
      />
      <!-- ปุ่มล้างค่า -->
      <Button
        v-if="modelValue"
        variant="ghost"
        size="sm"
        @click="clear"
        class="absolute right-1 top-1/2 transform -translate-y-1/2 h-6 w-6 p-0 hover:bg-muted"
      >
        <X class="h-3 w-3" />
      </Button>
    </div>
  </div>
</template>
