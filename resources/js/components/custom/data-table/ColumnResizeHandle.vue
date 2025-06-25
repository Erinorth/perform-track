<script setup lang="ts">
import { ref, computed } from 'vue'
import { GripVertical } from 'lucide-vue-next'

// Props definition
interface Props {
  header: any
  table: any
}

const props = defineProps<Props>()

// State สำหรับการ resize
const isResizing = ref(false)

// ตรวจสอบว่าสามารถ resize ได้หรือไม่
const canResize = computed(() => {
  try {
    return props.header.column.getCanResize()
  } catch {
    return false
  }
})

// ฟังก์ชันสำหรับจัดการการ resize
const getResizeHandler = () => {
  try {
    return props.header.getResizeHandler()
  } catch {
    return () => {}
  }
}

const handleMouseDown = (event: MouseEvent) => {
  isResizing.value = true
  const resizeHandler = getResizeHandler()
  if (resizeHandler) {
    resizeHandler(event)
  }
}

const handleMouseUp = () => {
  isResizing.value = false
}

// ติดตาม mouse events
document.addEventListener('mouseup', handleMouseUp)
</script>

<template>
  <div
    v-if="canResize"
    class="absolute right-0 top-0 h-full w-1 cursor-col-resize select-none touch-none bg-transparent hover:bg-border opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center group"
    :class="{
      'opacity-100 bg-border': isResizing
    }"
    @mousedown="handleMouseDown"
  >
    <div class="w-px h-4 bg-border group-hover:bg-foreground transition-colors" />
  </div>
</template>
