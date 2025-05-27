<!--
  ไฟล์: resources\js\pages\risk_control\data-table\HeaderSection.vue
  คำอธิบาย: ส่วนหัวของหน้าแบบกะทัดรัด สำหรับการควบคุมความเสี่ยง
  ทำหน้าที่: แสดงชื่อหน้าและปุ่มเพิ่มข้อมูลการควบคุมความเสี่ยงแบบประหยัดพื้นที่
  ฟีเจอร์หลัก:
  - แสดงชื่อหน้าและคำอธิบายแบบ responsive
  - ปุ่ม dropdown สำหรับเพิ่มการควบคุมความเสี่ยงแบบ modal หรือหน้าใหม่
  - รองรับการปิดใช้งานปุ่มเมื่อไม่มีข้อมูลที่จำเป็น
  - แสดงสถิติย่อของการควบคุมความเสี่ยง (ถ้ามี)
-->

<script setup lang="ts">
// ==================== นำเข้า Vue Composition API ====================
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

// ==================== นำเข้า Toast Notification ====================
import { toast } from 'vue-sonner'

// ==================== นำเข้า UI Components ====================
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Badge } from '@/components/ui/badge'

// ==================== นำเข้า Icons ====================
import { 
  PlusIcon, 
  SparklesIcon,
  FileTextIcon,
  ShieldIcon,
  AlertCircleIcon,
  InfoIcon
} from 'lucide-vue-next'

// ==================== กำหนด Interface สำหรับ Props ====================
interface ControlStatistics {
  total_controls?: number
  active_controls?: number
  inactive_controls?: number
  division_risks_count?: number
}

// ==================== กำหนด Props ====================
const props = defineProps<{
  title: string                           // ชื่อหน้า
  description?: string                    // คำอธิบายหน้า
  canCreate?: boolean                     // สามารถสร้างการควบคุมใหม่ได้หรือไม่
  statistics?: ControlStatistics          // สถิติการควบคุมความเสี่ยง
  showStatistics?: boolean                // แสดงสถิติหรือไม่
}>()

// ==================== กำหนด Default Values ====================
const defaultProps = {
  canCreate: true,
  showStatistics: false
}

// ==================== กำหนด Emit Events ====================
const emit = defineEmits<{
  (e: 'create'): void          // เหตุการณ์เพิ่มการควบคุมความเสี่ยงแบบหน้าใหม่
  (e: 'quickCreate'): void     // เหตุการณ์เพิ่มการควบคุมความเสี่ยงแบบ modal
  (e: 'refresh'): void         // เหตุการณ์รีเฟรชข้อมูล
}>()

// ==================== Reactive States ====================
// สถานะการเปิด/ปิด dropdown menu
const isDropdownOpen = ref<boolean>(false)

// สถานะการประมวลผล (เช่น กำลังบันทึกข้อมูล)
const isProcessing = ref<boolean>(false)

// ==================== Computed Properties ====================
// ตรวจสอบว่าสามารถสร้างการควบคุมใหม่ได้หรือไม่
const canCreateControl = computed(() => {
  return (props.canCreate ?? defaultProps.canCreate) && !isProcessing.value
})

// คำนวณข้อความเตือนเมื่อไม่สามารถสร้างได้
const createDisabledReason = computed(() => {
  if (isProcessing.value) {
    return 'กำลังประมวลผล กรุณารอสักครู่'
  }
  if (!props.canCreate) {
    return 'ไม่สามารถสร้างการควบคุมได้ กรุณาตรวจสอบข้อมูลความเสี่ยงระดับฝ่าย'
  }
  return ''
})

// คำนวณสถิติสำหรับแสดงผล
const displayStatistics = computed(() => {
  if (!props.statistics || !props.showStatistics) return null
  
  return {
    total: props.statistics.total_controls || 0,
    active: props.statistics.active_controls || 0,
    inactive: props.statistics.inactive_controls || 0,
    risks: props.statistics.division_risks_count || 0
  }
})

// ==================== Methods ====================
/**
 * ฟังก์ชันจัดการการเพิ่มการควบคุมความเสี่ยงแบบ modal (Quick Create)
 * เรียกใช้ event quickCreate และปิด dropdown
 */
const handleQuickCreate = () => {
  try {
    // ตรวจสอบว่าสามารถสร้างได้หรือไม่
    if (!canCreateControl.value) {
      toast.warning('ไม่สามารถสร้างการควบคุมความเสี่ยงได้', {
        description: createDisabledReason.value
      })
      return
    }

    // ปิด dropdown
    isDropdownOpen.value = false

    // ส่งเหตุการณ์ไปยัง parent component
    emit('quickCreate')

    // แสดง toast แจ้งเตือน
    toast.info('เปิด Modal เพิ่มการควบคุมความเสี่ยง', {
      description: 'กรอกข้อมูลการควบคุมความเสี่ยงในหน้าต่างที่เปิดขึ้น'
    })

    // บันทึก log เพื่อการตรวจสอบ
    console.log('📝 เปิด Modal เพิ่มการควบคุมความเสี่ยงแบบด่วน:', {
      timestamp: new Date().toISOString(),
      action: 'quick_create_control'
    })

  } catch (error) {
    console.error('❌ เกิดข้อผิดพลาดในการเปิด Modal:', error)
    toast.error('เกิดข้อผิดพลาดในการเปิด Modal')
  }
}

/**
 * ฟังก์ชันจัดการการเพิ่มการควบคุมความเสี่ยงแบบหน้าใหม่ (Full Create)
 * นำทางไปยังหน้าฟอร์มเพิ่มการควบคุมความเสี่ยงแบบเต็ม
 */
const handleCreate = () => {
  try {
    // ตรวจสอบว่าสามารถสร้างได้หรือไม่
    if (!canCreateControl.value) {
      toast.warning('ไม่สามารถสร้างการควบคุมความเสี่ยงได้', {
        description: createDisabledReason.value
      })
      return
    }

    // ปิด dropdown
    isDropdownOpen.value = false

    // กำหนดสถานะกำลังประมวลผล
    isProcessing.value = true

    // นำทางไปยังหน้าสร้างการควบคุมความเสี่ยงใหม่
    router.visit(route('risk-controls.create'), {
      onStart: () => {
        // แสดง toast เมื่อเริ่มการนำทาง
        toast.info('กำลังเปิดหน้าเพิ่มการควบคุมความเสี่ยง...', {
          description: 'กรุณารอสักครู่'
        })
      },
      onFinish: () => {
        // รีเซ็ตสถานะเมื่อเสร็จสิ้นการนำทาง
        isProcessing.value = false
      },
      onError: (errors) => {
        // จัดการข้อผิดพลาดในการนำทาง
        isProcessing.value = false
        console.error('❌ เกิดข้อผิดพลาดในการนำทาง:', errors)
        toast.error('เกิดข้อผิดพลาดในการเปิดหน้า')
      }
    })

    // บันทึก log เพื่อการตรวจสอบ
    console.log('📄 นำทางไปยังหน้าเพิ่มการควบคุมความเสี่ยงแบบเต็ม:', {
      route: 'risk-controls.create',
      timestamp: new Date().toISOString(),
      action: 'full_create_control'
    })

  } catch (error) {
    isProcessing.value = false
    console.error('❌ เกิดข้อผิดพลาดในการนำทาง:', error)
    toast.error('เกิดข้อผิดพลาดในการเปิดหน้า')
  }
}

/**
 * ฟังก์ชันจัดการการรีเฟรชข้อมูล
 */
const handleRefresh = () => {
  try {
    // ปิด dropdown
    isDropdownOpen.value = false

    // ส่งเหตุการณ์ไปยัง parent component
    emit('refresh')

    // แสดง toast แจ้งเตือน
    toast.info('กำลังรีเฟรชข้อมูล...', {
      description: 'อัปเดตข้อมูลการควบคุมความเสี่ยงล่าสุด'
    })

    // บันทึก log เพื่อการตรวจสอบ
    console.log('🔄 รีเฟรชข้อมูลการควบคุมความเสี่ยง:', {
      timestamp: new Date().toISOString(),
      action: 'refresh_controls'
    })

  } catch (error) {
    console.error('❌ เกิดข้อผิดพลาดในการรีเฟรช:', error)
    toast.error('เกิดข้อผิดพลาดในการรีเฟรชข้อมูล')
  }
}

/**
 * ฟังก์ชันจัดการเมื่อ dropdown เปิด/ปิด
 */
const handleDropdownOpenChange = (open: boolean) => {
  isDropdownOpen.value = open
  
  if (open) {
    console.log('📂 เปิด dropdown menu การควบคุมความเสี่ยง')
  }
}
</script>

<template>
  <!-- ส่วนหัวที่แสดงชื่อหน้าและปุ่มเพิ่มข้อมูล -->
  <div class="flex flex-col gap-4 mb-4">
    <!-- แถวหลัก: ชื่อหน้าและปุ่ม -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <!-- ส่วนหัวข้อหน้า -->
      <div class="space-y-1">
        <div class="flex items-center gap-2">
          <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
          <!-- ไอคอนการควบคุมความเสี่ยง -->
          <ShieldIcon class="h-6 w-6 text-blue-600" />
        </div>
        <p v-if="description" class="text-muted-foreground text-sm">
          {{ description }}
        </p>
      </div>
      
      <!-- ปุ่มเพิ่มข้อมูลพร้อม Dropdown -->
      <div class="flex items-center gap-2">
        <!-- ปุ่มรีเฟรช (แสดงเฉพาะเมื่อมีข้อมูล) -->
        <Button
          v-if="displayStatistics"
          variant="outline"
          size="sm"
          @click="handleRefresh"
          :disabled="isProcessing"
          class="flex items-center gap-2"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span class="hidden sm:inline">รีเฟรช</span>
        </Button>

        <!-- Dropdown Menu สำหรับเพิ่มข้อมูล -->
        <DropdownMenu v-model:open="isDropdownOpen" @update:open="handleDropdownOpenChange">
          <DropdownMenuTrigger as-child>
            <Button 
              :disabled="!canCreateControl"
              :class="[
                'flex items-center gap-2 px-4 py-2 rounded-lg shadow-md transition-colors',
                canCreateControl 
                  ? 'bg-blue-600 hover:bg-blue-700 text-white' 
                  : 'bg-gray-300 text-gray-500 cursor-not-allowed'
              ]"
              :title="canCreateControl ? 'เพิ่มการควบคุมความเสี่ยง' : createDisabledReason"
            >
              <PlusIcon class="h-4 w-4" />
              <span class="hidden sm:inline">
                {{ isProcessing ? 'กำลังประมวลผล...' : 'เพิ่มการควบคุม' }}
              </span>
            </Button>
          </DropdownMenuTrigger>
          
          <DropdownMenuContent align="end" class="w-72">
            <!-- ส่วนหัว Dropdown -->
            <div class="px-3 py-2 border-b">
              <h3 class="font-medium text-sm">เพิ่มการควบคุมความเสี่ยง</h3>
              <p class="text-xs text-gray-500 mt-1">เลือกวิธีการเพิ่มข้อมูล</p>
            </div>

            <!-- ตัวเลือกเพิ่มแบบด่วน (Modal) -->
            <DropdownMenuItem 
              @click="handleQuickCreate" 
              :disabled="!canCreateControl"
              class="cursor-pointer p-3 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                  <SparklesIcon class="h-4 w-4 text-green-600" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm">เพิ่มแบบด่วน</div>
                  <div class="text-xs text-gray-500">Modal ในหน้าปัจจุบัน • เหมาะสำหรับข้อมูลพื้นฐาน</div>
                </div>
              </div>
            </DropdownMenuItem>
            
            <DropdownMenuSeparator />
            
            <!-- ตัวเลือกเพิ่มแบบเต็ม (หน้าใหม่) -->
            <DropdownMenuItem 
              @click="handleCreate" 
              :disabled="!canCreateControl"
              class="cursor-pointer p-3 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                  <FileTextIcon class="h-4 w-4 text-blue-600" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm">เพิ่มแบบเต็ม</div>
                  <div class="text-xs text-gray-500">หน้าใหม่แบบละเอียด • รองรับไฟล์แนบและข้อมูลครบถ้วน</div>
                </div>
              </div>
            </DropdownMenuItem>

            <!-- แสดงข้อความเตือนเมื่อไม่สามารถสร้างได้ -->
            <div v-if="!canCreateControl" class="px-3 py-2 border-t">
              <div class="flex items-center gap-2 text-amber-600">
                <AlertCircleIcon class="h-4 w-4 flex-shrink-0" />
                <span class="text-xs">{{ createDisabledReason }}</span>
              </div>
            </div>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <!-- แถวสถิติ (แสดงเมื่อมีข้อมูลและเปิดใช้งาน) -->
    <div v-if="displayStatistics" class="flex flex-wrap items-center gap-3 text-sm">
      <!-- สถิติรวม -->
      <div class="flex items-center gap-2">
        <Badge variant="outline" class="bg-blue-50 text-blue-700 border-blue-200">
          <ShieldIcon class="h-3 w-3 mr-1" />
          รวม {{ displayStatistics.total }} การควบคุม
        </Badge>
      </div>

      <!-- สถิติใช้งาน -->
      <div v-if="displayStatistics.active > 0" class="flex items-center gap-2">
        <Badge variant="outline" class="bg-green-50 text-green-700 border-green-200">
          <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          ใช้งาน {{ displayStatistics.active }} รายการ
        </Badge>
      </div>

      <!-- สถิติไม่ใช้งาน -->
      <div v-if="displayStatistics.inactive > 0" class="flex items-center gap-2">
        <Badge variant="outline" class="bg-red-50 text-red-700 border-red-200">
          <AlertCircleIcon class="h-3 w-3 mr-1" />
          ไม่ใช้งาน {{ displayStatistics.inactive }} รายการ
        </Badge>
      </div>

      <!-- สถิติความเสี่ยงระดับฝ่าย -->
      <div v-if="displayStatistics.risks > 0" class="flex items-center gap-2">
        <Badge variant="outline" class="bg-gray-50 text-gray-700 border-gray-200">
          <InfoIcon class="h-3 w-3 mr-1" />
          {{ displayStatistics.risks }} ความเสี่ยงฝ่าย
        </Badge>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animation สำหรับ loading */
.processing {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

/* สไตล์สำหรับ dropdown items */
.dropdown-item:hover {
  background-color: rgba(59, 130, 246, 0.1);
}

.dropdown-item:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .flex-wrap {
    gap: 0.5rem;
  }
}
</style>
