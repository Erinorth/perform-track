<!-- resources\js\components\SummaryCard.vue -->
<script setup lang="ts">
// import ไอคอน lucide-vue-next แบบ dynamic
import * as lucideIcons from 'lucide-vue-next'
import { defineProps, computed } from 'vue'
import { toast } from 'vue-sonner'

// กำหนด props ให้ generic และรองรับ type
const props = defineProps<{
  icon: string,        // ชื่อไอคอน lucide เช่น 'alert-triangle'
  title: string,       // หัวข้อ เช่น 'ความเสี่ยงสูง'
  value: number|string,// ค่าตัวเลขหรือข้อความ
  color?: string       // สีพื้นหลังหลัก เช่น 'red', 'yellow', 'green', 'blue'
}>()

// สร้าง icon component จากชื่อ
const iconComponent = computed(() => {
  // ถ้าไม่พบไอคอน จะใช้ 'Circle' แทน
  return (lucideIcons as any)[
    props.icon.charAt(0).toUpperCase() + props.icon.slice(1).replace(/-([a-z])/g, g => g[1].toUpperCase())
  ] || lucideIcons.Circle
})

// สีไอคอน
const iconColor = computed(() => {
  switch (props.color) {
    case 'red': return 'text-red-500'
    case 'yellow': return 'text-yellow-500'
    case 'green': return 'text-green-500'
    case 'blue': return 'text-blue-500'
    default: return 'text-gray-400'
  }
})

// สีพื้นหลัง card (เน้นขอบ/hover)
const bgClass = computed(() => {
  switch (props.color) {
    case 'red': return 'border-l-4 border-red-500'
    case 'yellow': return 'border-l-4 border-yellow-500'
    case 'green': return 'border-l-4 border-green-500'
    case 'blue': return 'border-l-4 border-blue-500'
    default: return 'border-l-4 border-gray-300'
  }
})

// log และ toast เมื่อคลิก card
function handleClick() {
  console.log(`[SummaryCard] ${props.title} clicked, value: ${props.value}`)
  toast(`${props.title}: ${props.value}`)
}
</script>

<!--
  - ส่วนนี้เป็น card สรุปข้อมูลแต่ละประเภท
  - รองรับ responsive, ใช้ tailwind, icon จาก lucide-vue-next
  - รองรับการเปลี่ยนสีตาม props.color
  - เมื่อคลิกจะแสดง toast และ log
-->

<template>
  <!-- card สรุปข้อมูล ใช้ tailwind และ responsive -->
  <div
    class="flex items-center gap-4 p-4 rounded-lg shadow bg-white dark:bg-gray-900 transition hover:scale-105 hover:shadow-lg cursor-pointer"
    :class="bgClass"
    @click="handleClick"
  >
    <!-- ไอคอน lucide-vue-next -->
    <component
      :is="iconComponent"
      class="w-10 h-10"
      :class="iconColor"
    />
    <div class="flex flex-col flex-1">
      <!-- ชื่อหัวข้อ -->
      <span class="text-sm font-medium text-gray-500 dark:text-gray-300">{{ title }}</span>
      <!-- ค่าตัวเลข -->
      <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ value }}</span>
    </div>
  </div>
</template>

