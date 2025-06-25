<!--
  ไฟล์: resources/js/components/PageHeaderCompact.vue
  คำอธิบาย: ส่วนหัวของหน้าแบบกะทัดรัด
  ทำหน้าที่: แสดงชื่อหน้าและปุ่มเพิ่มข้อมูลแบบประหยัดพื้นที่
-->

<script setup lang="ts">
import { router } from '@inertiajs/vue3'

// UI Components
import AddDataButton from '@/components/custom/AddDataButton.vue'

// Props สำหรับกำหนดข้อมูลหัวข้อ
interface Props {
  title: string
  description?: string
  createRoute?: string // route สำหรับการเพิ่มข้อมูลแบบเต็ม
  showAddButton?: boolean // ควบคุมการแสดงปุ่มเพิ่มข้อมูล
}

const props = withDefaults(defineProps<Props>(), {
  createRoute: 'organizational-risks.create',
  showAddButton: true
})

// Events ที่ component นี้จะส่งออกไป
const emit = defineEmits<{
  (e: 'quickCreate'): void
}>()

// Handler สำหรับการเพิ่มแบบด่วน
const handleQuickCreate = (): void => {
  console.log('PageHeaderCompact: Quick create requested')
  emit('quickCreate')
}

// Handler สำหรับการเพิ่มแบบเต็ม
const handleFullCreate = (): void => {
  console.log(`PageHeaderCompact: Navigating to ${props.createRoute}`)
  
  try {
    // ตรวจสอบว่า route มีอยู่จริงหรือไม่
    const url = route(props.createRoute)
    router.visit(url)
  } catch (error) {
    console.error('PageHeaderCompact: Route not found:', props.createRoute, error)
    
    // Fallback: ใช้ route แบบ hardcode
    router.visit(route('organizational-risks.create'))
  }
}
</script>

<template>
  <!-- Compact Header Container -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4 p-4 sm:p-0">
    <!-- Title Section -->
    <div class="flex-1 min-w-0">
      <!-- หัวข้อหลัก -->
      <h1 class="text-xl sm:text-2xl font-bold text-gray-900 truncate">
        {{ title }}
      </h1>
      
      <!-- คำอธิบาย (ถ้ามี) -->
      <p 
        v-if="description" 
        class="text-sm text-gray-500 truncate mt-1 leading-relaxed"
      >
        {{ description }}
      </p>
    </div>
    
    <!-- Add Button Section -->
    <div v-if="showAddButton" class="flex-shrink-0">
      <AddDataButton
        button-text="เพิ่มข้อมูล"
        variant="default"
        size="default"
        compact
        quick-create-label="เพิ่มด่วน"
        quick-create-description="Modal ในหน้าปัจจุบัน"
        full-create-label="เพิ่มแบบเต็ม"
        full-create-description="หน้าใหม่แบบละเอียด"
        @quick-create="handleQuickCreate"
        @full-create="handleFullCreate"
      />
    </div>
  </div>
</template>

<style scoped>
/* สำหรับการปรับแต่ง responsive ถ้าจำเป็น */
@media (max-width: 640px) {
  .flex-col {
    gap: 1rem;
  }
}
</style>
