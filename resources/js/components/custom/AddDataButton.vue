<!--
  ไฟล์: resources/js/components/ui/AddDataButton.vue
  คำอธิบาย: ปุ่มเพิ่มข้อมูลที่มี dropdown menu
  ทำหน้าที่: แสดงตัวเลือกการเพิ่มข้อมูลแบบด่วนและแบบเต็ม
-->

<script setup lang="ts">
import { ref, computed } from 'vue'

// UI Components
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

// Icons
import { 
  PlusIcon, 
  SparklesIcon,
  FileTextIcon
} from 'lucide-vue-next'

// Props สำหรับกำหนดการแสดงผลและพฤติกรรม
interface Props {
  buttonText?: string
  variant?: 'default' | 'outline' | 'ghost'
  size?: 'default' | 'sm' | 'lg'
  quickCreateLabel?: string
  quickCreateDescription?: string
  fullCreateLabel?: string
  fullCreateDescription?: string
  disabled?: boolean
  compact?: boolean // สำหรับแสดงแบบกะทัดรัด (ไม่แสดงข้อความในหน้าจอเล็ก)
}

const props = withDefaults(defineProps<Props>(), {
  buttonText: 'เพิ่มข้อมูล',
  variant: 'default',
  size: 'default',
  quickCreateLabel: 'เพิ่มด่วน',
  quickCreateDescription: 'Modal ในหน้าปัจจุบัน',
  fullCreateLabel: 'เพิ่มแบบเต็ม',
  fullCreateDescription: 'หน้าใหม่แบบละเอียด',
  disabled: false,
  compact: false
})

// Events ที่ component นี้จะส่งออกไป
const emit = defineEmits<{
  (e: 'quickCreate'): void
  (e: 'fullCreate'): void
}>()

// State สำหรับควบคุม dropdown
const isDropdownOpen = ref<boolean>(false)

// Handler สำหรับการเพิ่มแบบด่วน
const handleQuickCreate = (): void => {
  console.log('AddDataButton: Quick create clicked')
  emit('quickCreate')
  isDropdownOpen.value = false
}

// Handler สำหรับการเพิ่มแบบเต็ม
const handleFullCreate = (): void => {
  console.log('AddDataButton: Full create clicked')
  emit('fullCreate')
  isDropdownOpen.value = false
}

// คำนวณ class ของปุ่มตาม variant
const buttonClasses = computed(() => {
  const baseClasses = 'flex items-center gap-2 shadow-md transition-all duration-200'
  
  switch (props.variant) {
    case 'outline':
      return `${baseClasses} border border-blue-600 text-blue-600 hover:bg-blue-50`
    case 'ghost':
      return `${baseClasses} text-blue-600 hover:bg-blue-50`
    default:
      return `${baseClasses} bg-blue-600 hover:bg-blue-700 text-white`
  }
})
</script>

<template>
  <DropdownMenu v-model:open="isDropdownOpen">
    <DropdownMenuTrigger as-child>
      <Button 
        :class="buttonClasses"
        :size="size"
        :disabled="disabled"
        class="px-4 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
      >
        <!-- Icon -->
        <PlusIcon class="h-4 w-4" />
        
        <!-- Text - ซ่อนในหน้าจอเล็กถ้าเป็น compact mode -->
        <span 
          v-if="!compact" 
          class="font-medium"
        >
          {{ buttonText }}
        </span>
        <span 
          v-else 
          class="hidden sm:inline font-medium"
        >
          {{ buttonText }}
        </span>
      </Button>
    </DropdownMenuTrigger>
    
    <DropdownMenuContent 
      align="end" 
      class="w-64 shadow-lg border border-gray-200 rounded-lg bg-white"
    >
      <!-- Quick Create Option -->
      <DropdownMenuItem 
        @click="handleQuickCreate" 
        :disabled="disabled"
        class="cursor-pointer p-3 hover:bg-gray-50 focus:bg-gray-50 transition-colors duration-150"
      >
        <div class="flex items-center gap-3 w-full">
          <div class="flex-shrink-0">
            <SparklesIcon class="h-4 w-4 text-green-600" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-medium text-gray-900">{{ quickCreateLabel }}</div>
            <div class="text-xs text-gray-500 truncate">{{ quickCreateDescription }}</div>
          </div>
        </div>
      </DropdownMenuItem>
      
      <DropdownMenuSeparator class="bg-gray-100" />
      
      <!-- Full Create Option -->
      <DropdownMenuItem 
        @click="handleFullCreate" 
        :disabled="disabled"
        class="cursor-pointer p-3 hover:bg-gray-50 focus:bg-gray-50 transition-colors duration-150"
      >
        <div class="flex items-center gap-3 w-full">
          <div class="flex-shrink-0">
            <FileTextIcon class="h-4 w-4 text-blue-600" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-medium text-gray-900">{{ fullCreateLabel }}</div>
            <div class="text-xs text-gray-500 truncate">{{ fullCreateDescription }}</div>
          </div>
        </div>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
