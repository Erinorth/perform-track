<!--
  ไฟล์: resources/js/components/custom/HeaderWithTitle.vue
  คำอธิบาย: Component รวมที่ผสม Title และ HeaderSection เข้าด้วยกัน
  ฟีเจอร์หลัก:
  - แสดงหัวข้อแบบสวยงามพร้อม Badge และ Description
  - มีปุ่มเพิ่มข้อมูลแบบ Quick Create และ Full Create
  - รองรับ Responsive Design สำหรับทุกขนาดหน้าจอ
  - ใช้ shadcn-vue components เป็นหลัก
-->

<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { cn } from '@/lib/utils'

// UI Components
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import AddDataButton from '@/components/custom/AddDataButton.vue'

// Interface สำหรับ Props
interface Props {
  title: string
  subtitle?: string
  description?: string
  badge?: string
  badgeVariant?: 'default' | 'secondary' | 'destructive' | 'outline'
  size?: 'sm' | 'md' | 'lg' | 'xl'
  align?: 'left' | 'center' | 'right'
  class?: HTMLAttributes['class']
  showSeparator?: boolean
  createRoute?: string // route สำหรับการเพิ่มข้อมูลแบบเต็ม
  showAddButton?: boolean // ควบคุมการแสดงปุ่มเพิ่มข้อมูล
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  align: 'left',
  badgeVariant: 'secondary',
  showSeparator: true,
  createRoute: 'organizational-risks.create',
  showAddButton: true
})

// Events ที่ component นี้จะส่งออกไป
const emit = defineEmits<{
  (e: 'quickCreate'): void
}>()

// คำนวณ class สำหรับขนาดหัวข้อ
const titleClasses = computed(() => {
  const sizeClasses = {
    sm: 'text-lg font-semibold',
    md: 'text-xl font-semibold',
    lg: 'text-2xl font-bold',
    xl: 'text-3xl font-bold'
  }
  
  const alignClasses = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right'
  }
  
  return cn(
    sizeClasses[props.size],
    alignClasses[props.align],
    'leading-tight tracking-tight text-foreground'
  )
})

// คำนวณ class สำหรับหัวข้อรอง
const subtitleClasses = computed(() => {
  const sizeClasses = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
    xl: 'text-xl'
  }
  
  const alignClasses = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right'
  }
  
  return cn(
    sizeClasses[props.size],
    alignClasses[props.align],
    'font-medium text-muted-foreground'
  )
})

// คำนวณ class สำหรับคำอธิบาย
const descriptionClasses = computed(() => {
  const alignClasses = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right'
  }
  
  return cn(
    'text-sm text-muted-foreground leading-relaxed',
    alignClasses[props.align]
  )
})

// คำนวณ class สำหรับ container หลัก
const containerClasses = computed(() => {
  const alignClasses = {
    left: 'items-start',
    center: 'items-center',
    right: 'items-end'
  }
  
  return cn(
    'flex flex-col gap-2',
    alignClasses[props.align]
  )
})

// Handler สำหรับการเพิ่มแบบด่วน
const handleQuickCreate = (): void => {
  console.log('HeaderWithTitle: Quick create requested')
  emit('quickCreate')
}

// Handler สำหรับการเพิ่มแบบเต็ม
const handleFullCreate = (): void => {
  console.log(`HeaderWithTitle: Navigating to ${props.createRoute}`)
  
  try {
    // ตรวจสอบว่า route มีอยู่จริงหรือไม่
    const url = route(props.createRoute)
    router.visit(url)
  } catch (error) {
    console.error('HeaderWithTitle: Route not found:', props.createRoute, error)
    
    // Fallback: ใช้ route แบบ hardcode
    router.visit(route('organizational-risks.create'))
  }
}
</script>

<template>
  <!-- Container หลักที่รวม Title และ Header Actions -->
  <div :class="cn('space-y-4 mb-6', props.class)">
    <!-- ส่วน Title และ Description -->
    <div :class="containerClasses">
      <!-- Main Title Section -->
      <div :class="cn('flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full')">
        <!-- Title และ Badge Group -->
        <div :class="cn('flex flex-col gap-2 min-w-0 flex-1')">
          <!-- Title Row -->
          <div :class="cn('flex items-center gap-2 flex-wrap')">
            <!-- หัวข้อหลัก -->
            <h1 :class="titleClasses">
              <slot name="title">
                {{ title }}
              </slot>
            </h1>
            
            <!-- Badge -->
            <Badge 
              v-if="badge" 
              :variant="badgeVariant"
              class="flex-shrink-0"
            >
              {{ badge }}
            </Badge>
          </div>
          
          <!-- หัวข้อรอง -->
          <p 
            v-if="subtitle" 
            :class="subtitleClasses"
          >
            <slot name="subtitle">
              {{ subtitle }}
            </slot>
          </p>
          
          <!-- คำอธิบาย -->
          <p 
            v-if="description" 
            :class="descriptionClasses"
          >
            <slot name="description">
              {{ description }}
            </slot>
          </p>
        </div>
        
        <!-- Add Button Section - แสดงในตำแหน่งด้านขวาบน Desktop -->
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
      
      <!-- Slot สำหรับ Actions เพิ่มเติม -->
      <div v-if="$slots.actions" class="flex items-center gap-2">
        <slot name="actions" />
      </div>
      
      <!-- Slot สำหรับ Content เพิ่มเติม -->
      <slot />
    </div>
    
    <!-- Separator -->
    <Separator 
      v-if="showSeparator" 
      class="bg-border" 
    />
  </div>
</template>

<style scoped>
/* การปรับแต่งสำหรับ Mobile Responsive */
@media (max-width: 640px) {
  .flex-col {
    gap: 1rem;
  }
  
  /* ปรับ title size สำหรับ mobile */
  h1 {
    word-break: break-word;
  }
}
</style>
