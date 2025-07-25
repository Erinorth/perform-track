<!--
  ไฟล์: resources/js/components/PageHeaderCompact.vue
  คำอธิบาย: ส่วนหัวของหน้าแบบกะทัดรัด
  ทำหน้าที่: แสดงชื่อหน้าและปุ่มเพิ่มข้อมูลแบบประหยัดพื้นที่
-->

<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

// UI Components
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { 
  PlusIcon, 
  SparklesIcon,
  FileTextIcon
} from 'lucide-vue-next'

// กำหนด props สำหรับรับข้อมูลเข้ามาแสดง
defineProps<{
  title: string;
  description?: string;
}>();

// กำหนด emit events สำหรับส่งเหตุการณ์กลับไปยัง parent
const emit = defineEmits<{
  (e: 'create'): void
  (e: 'quickCreate'): void
}>()

const isDropdownOpen = ref<boolean>(false)

const handleQuickCreate = () => {
  emit('quickCreate')
  isDropdownOpen.value = false
}

const handleCreate = () => {
  // ไปยังหน้า OrganizationalRiskForm (route: organizational-risks.create)
  router.visit(route('division-risks.create'))
  isDropdownOpen.value = false
}
</script>

<template>
  <!-- ส่วนหัวที่แสดงชื่อหน้าและปุ่มเพิ่มข้อมูล -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
    <!-- ส่วนหัวข้อหน้า -->
    <div>
      <h1 class="text-2xl font-bold">{{ title }}</h1>
      <p v-if="description" class="text-muted-foreground">{{ description }}</p>
    </div>
    
    <!-- Single Add Button with Dropdown -->
    <DropdownMenu v-model:open="isDropdownOpen">
      <DropdownMenuTrigger as-child>
        <Button class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md">
          <PlusIcon class="h-4 w-4" />
          <span class="hidden sm:inline">เพิ่มข้อมูล</span>
        </Button>
      </DropdownMenuTrigger>
      
      <DropdownMenuContent align="end" class="w-64">
        <!-- Quick Create -->
        <DropdownMenuItem @click="handleQuickCreate" class="cursor-pointer p-3">
          <div class="flex items-center gap-3">
            <SparklesIcon class="h-4 w-4 text-green-600" />
            <div>
              <div class="font-medium">เพิ่มด่วน</div>
              <div class="text-xs text-gray-500">Modal ในหน้าปัจจุบัน</div>
            </div>
          </div>
        </DropdownMenuItem>
        
        <DropdownMenuSeparator />
        
        <!-- Full Create -->
        <DropdownMenuItem @click="handleCreate" class="cursor-pointer p-3">
          <div class="flex items-center gap-3">
            <FileTextIcon class="h-4 w-4 text-blue-600" />
            <div>
              <div class="font-medium">เพิ่มแบบเต็ม</div>
              <div class="text-xs text-gray-500">หน้าใหม่แบบละเอียด</div>
            </div>
          </div>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </div>
</template>
