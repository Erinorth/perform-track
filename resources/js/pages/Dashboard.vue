<script setup lang="ts">
// นำเข้า Inertia และ Head สำหรับจัดการ page
import { Head } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

// นำเข้า Layout หลักของแอป
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

// นำเข้า HeaderSection component
import HeaderSection from './HeaderSection.vue'

// นำเข้า component ที่สร้างเอง
import SummaryCard from '@/components/SummaryCard.vue'
import RiskMatrixBubble from '@/components/custom/RiskMatrixBubble.vue'
import RiskTrendChart from '@/components/custom/RiskTrendChart.vue'

// นำเข้า toast สำหรับแจ้งเตือน
import { toast } from 'vue-sonner'

// นำเข้าไอคอนจาก lucide-vue-next
import { TrendingUp, AlertTriangle, Activity, Users } from 'lucide-vue-next'

// นำเข้า types
import type { TrendData, FilterParams } from '@/types/chart'

// กำหนด interface สำหรับตัวเลือกประเภทความเสี่ยง
interface RiskTypeOption {
  value: string | number
  label: string
}

// กำหนด interface สำหรับข้อมูล Risk Assessment ตาม database schema
interface RiskAssessmentData {
  likelihood_level: number
  impact_level: number
  risk_score: number
  risks: Array<{
    id: number
    risk_name: string
    description?: string
    division_risk_id?: number
    organizational_risk_id?: number
    org_risk_name?: string
    assessment_date?: string
    notes?: string
  }>
}

// กำหนด interface สำหรับสถิติความเสี่ยง
interface RiskSummaryData {
  critical: number // 13-16
  high: number     // 9-12
  medium: number   // 4-8
  low: number      // 1-3
}

// กำหนด interface สำหรับ props ที่รับจาก Laravel
interface Props {
  trendData?: TrendData[]
  riskSummary?: RiskSummaryData
  riskMatrixData?: RiskAssessmentData[] // เปลี่ยนจาก heatmapData เป็น riskMatrixData
  recentIncidents?: number
  riskTypeOptions?: RiskTypeOption[]
  totalUsers?: number // จำนวนผู้ใช้ในระบบ
  lastUpdated?: string // วันที่อัปเดตล่าสุด
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'ภาพรวมความเสี่ยง',
    href: '/dashboard',
  },
]

// รับ props จาก Laravel controller ผ่าน Inertia
const props = withDefaults(defineProps<Props>(), {
  trendData: () => [],
  riskMatrixData: () => [],
  recentIncidents: 0,
  riskTypeOptions: () => [],
  totalUsers: 0,
  lastUpdated: ''
})

// เพิ่ม debug log สำหรับติดตามข้อมูล
watch(() => props, (newProps) => {
  console.log('Dashboard props updated:', newProps)
  console.log('riskMatrixData:', newProps.riskMatrixData)
  console.log('riskSummary:', newProps.riskSummary)
  console.log('trendData:', newProps.trendData)
}, { immediate: true, deep: true })

// คำนวณสถิติรวม
const totalRisks = computed(() => {
  const summary = props.riskSummary || { critical: 0, high: 0, medium: 0, low: 0 }
  return summary.critical + summary.high + summary.medium + summary.low
})

const urgentRisks = computed(() => {
  const summary = props.riskSummary || { critical: 0, high: 0, medium: 0, low: 0 }
  return summary.critical + summary.high
})

const normalRisks = computed(() => {
  const summary = props.riskSummary || { critical: 0, high: 0, medium: 0, low: 0 }
  return summary.medium + summary.low
})

// สีสำหรับ SummaryCard ตามระดับความเสี่ยง
const riskLevelColors = {
  critical: 'red',
  high: 'orange', 
  medium: 'yellow',
  low: 'green'
} as const

// ฟังก์ชันสำหรับจัดการ filter จาก RiskTrendChart
const handleTrendFilter = (filterParams: FilterParams) => {
  console.log('Trend filter changed:', filterParams)
  
  // แสดง toast แจ้งการเปลี่ยนแปลง filter
  const timeRangeText = filterParams.timeRange === '7' ? '7 วัน' :
                       filterParams.timeRange === '30' ? '30 วัน' :
                       filterParams.timeRange === '90' ? '90 วัน' : '1 ปี'
  
  const riskTypeText = filterParams.riskType === 'all' ? 'ทุกประเภท' :
                      props.riskTypeOptions?.find(opt => opt.value === filterParams.riskType)?.label || filterParams.riskType
  
  toast.info(`กรองข้อมูลแนวโน้ม: ${timeRangeText}, ${riskTypeText}`, {
    duration: 2000
  })
}

// ฟังก์ชันสำหรับจัดการ filter จาก RiskMatrixBubble
const handleMatrixFilter = (filterParams: { riskType: string | number }) => {
  console.log('Matrix filter changed:', filterParams)
  
  const riskTypeText = filterParams.riskType === 'all' ? 'ทุกประเภท' :
                      props.riskTypeOptions?.find(opt => opt.value === filterParams.riskType)?.label || filterParams.riskType
  
  toast.info(`กรอง Risk Matrix: ${riskTypeText}`, {
    duration: 2000
  })
}

// ฟังก์ชันสำหรับแสดง toast เมื่อคลิกที่กราฟ/ข้อมูล
const showRiskSummary = (level: keyof RiskSummaryData) => {
  const summary = props.riskSummary || { critical: 0, high: 0, medium: 0, low: 0 }
  const levelNames = {
    critical: 'สูงมาก (13-16)',
    high: 'สูง (9-12)',
    medium: 'กลาง (4-8)',
    low: 'ต่ำ (1-3)'
  }
  
  const count = summary[level]
  const levelName = levelNames[level]
  
  toast.info(`ความเสี่ยงระดับ${levelName}`, {
    description: `จำนวน ${count} รายการ จากทั้งหมด ${totalRisks.value} รายการ`,
    duration: 3000
  })
  
  console.log(`Risk level ${level} clicked:`, count)
}

// ฟังก์ชันสำหรับจัดการการเพิ่มการประเมิน
const handleAddAssessment = () => {
  toast.info('กำลังเปิดหน้าเพิ่มการประเมินความเสี่ยง', {
    duration: 2000
  })
  
  // ในอนาคตอาจใช้ router.push หรือ Inertia.visit
  console.log('Navigate to add assessment page')
}

// ฟังก์ชันสำหรับแสดงข้อมูลผู้ใช้ระบบ
const showSystemInfo = () => {
  toast.info('ข้อมูลระบบ', {
    description: `ผู้ใช้ในระบบ: ${props.totalUsers} คน, อัปเดตล่าสุด: ${props.lastUpdated || 'ไม่ระบุ'}`,
    duration: 4000
  })
}

// ฟังก์ชันสำหรับจัดรูปแบบวันที่แสดงผล
const formatDisplayDate = () => {
  const now = new Date()
  return now.toLocaleDateString('th-TH', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head title="ภาพรวมความเสี่ยง - Risk Assessment System" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- ส่วนหัวที่แยกเป็น Component ต่างหาก -->
      <HeaderSection 
        title="ภาพรวมความเสี่ยง"
        description="ภาพรวมความเสี่ยงทั้งหมดในระบบ พัฒนาด้วย Vue 3 + TypeScript + Vue-ChartJS"
      />
      
      <!-- เนื้อหาหลักของหน้า -->
      <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
          
          <!-- การ์ดสรุปข้อมูลความเสี่ยง 4 ระดับ -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <SummaryCard 
              icon="alert-triangle" 
              title="ความเสี่ยงสูงมาก (13-16)" 
              :value="(props.riskSummary?.critical || 0)" 
              :color="riskLevelColors.critical"
              @click="() => showRiskSummary('critical')"
              class="transform hover:scale-105 transition-transform duration-200"
            />
            <SummaryCard 
              icon="alert-circle" 
              title="ความเสี่ยงสูง (9-12)" 
              :value="(props.riskSummary?.high || 0)" 
              :color="riskLevelColors.high"
              @click="() => showRiskSummary('high')"
              class="transform hover:scale-105 transition-transform duration-200"
            />
            <SummaryCard 
              icon="alert-octagon" 
              title="ความเสี่ยงกลาง (4-8)" 
              :value="(props.riskSummary?.medium || 0)" 
              :color="riskLevelColors.medium"
              @click="() => showRiskSummary('medium')"
              class="transform hover:scale-105 transition-transform duration-200"
            />
            <SummaryCard 
              icon="shield-check" 
              title="ความเสี่ยงต่ำ (1-3)" 
              :value="(props.riskSummary?.low || 0)" 
              :color="riskLevelColors.low"
              @click="() => showRiskSummary('low')"
              class="transform hover:scale-105 transition-transform duration-200"
            />
          </div>

          <!-- กราฟ Risk Matrix และ Trend -->
          <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            <!-- Risk Matrix Bubble Chart -->
            <div class="bg-white dark:bg-gray-900 shadow-xl rounded-lg p-6 border border-gray-200 dark:border-gray-700">
              <RiskMatrixBubble 
                :data="props.riskMatrixData || []" 
                :risk-type-options="props.riskTypeOptions || []"
                @add-assessment="handleAddAssessment"
                @filter-change="handleMatrixFilter"
              />
            </div>

            <!-- Trend Chart -->
            <div class="bg-white dark:bg-gray-900 shadow-xl rounded-lg p-6 border border-gray-200 dark:border-gray-700">
              <RiskTrendChart 
                :data="props.trendData || []"
                :risk-type-options="props.riskTypeOptions || []"
                @filter="handleTrendFilter"
              />
            </div>
          </div>

          <!-- การ์ดแสดงสถิติเพิ่มเติมและข้อมูลระบบ -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- สรุปข้อมูลความเสี่ยง -->
            <div class="bg-white dark:bg-gray-900 shadow-xl rounded-lg p-6 border border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <TrendingUp class="w-5 h-5 mr-2 text-blue-600" />
                สรุปข้อมูลความเสี่ยง
              </h3>
              <div class="space-y-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                  <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ totalRisks }}
                  </div>
                  <div class="text-sm text-blue-600 dark:text-blue-400">
                    ความเสี่ยงทั้งหมดในระบบ
                  </div>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                  <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ urgentRisks }}
                  </div>
                  <div class="text-sm text-red-600 dark:text-red-400">
                    ความเสี่ยงที่ต้องดูแลเร่งด่วน
                  </div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                  <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ normalRisks }}
                  </div>
                  <div class="text-sm text-green-600 dark:text-green-400">
                    ความเสี่ยงที่อยู่ในเกณฑ์ปกติ
                  </div>
                </div>
              </div>
            </div>

            <!-- กิจกรรมล่าสุด -->
            <div class="bg-white dark:bg-gray-900 shadow-xl rounded-lg p-6 border border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <Activity class="w-5 h-5 mr-2 text-green-600" />
                กิจกรรมล่าสุด
              </h3>
              <div class="space-y-4">
                <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                  <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                    {{ props.recentIncidents || 0 }}
                  </div>
                  <div class="text-sm text-orange-600 dark:text-orange-400">
                    การประเมินใหม่ในสัปดาห์นี้
                  </div>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                  <p>• การประเมินความเสี่ยงล่าสุด</p>
                  <p>• อัปเดต Risk Matrix</p>
                  <p>• รายงานสถานะความเสี่ยง</p>
                </div>
              </div>
            </div>

            <!-- ข้อมูลระบบ -->
            <div class="bg-white dark:bg-gray-900 shadow-xl rounded-lg p-6 border border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <Users class="w-5 h-5 mr-2 text-purple-600" />
                ข้อมูลระบบ
              </h3>
              <div class="space-y-4">
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg cursor-pointer hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors" @click="showSystemInfo">
                  <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    {{ props.totalUsers || 0 }}
                  </div>
                  <div class="text-sm text-purple-600 dark:text-purple-400">
                    ผู้ใช้ในระบบ
                  </div>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                  <p>• ระบบ Risk Assessment</p>
                  <p>• Laravel 12 + Vue 3</p>
                  <p>• สถานะ: ออนไลน์</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
              การดำเนินการด่วน
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <button 
                @click="handleAddAssessment"
                class="flex items-center justify-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
              >
                <AlertTriangle class="w-4 h-4 mr-2" />
                เพิ่มการประเมิน
              </button>
              <button 
                class="flex items-center justify-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
              >
                <TrendingUp class="w-4 h-4 mr-2" />
                ดูรายงาน
              </button>
              <button 
                class="flex items-center justify-center px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
              >
                <Users class="w-4 h-4 mr-2" />
                จัดการผู้ใช้
              </button>
              <button 
                class="flex items-center justify-center px-4 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
              >
                <Activity class="w-4 h-4 mr-2" />
                ตั้งค่าระบบ
              </button>
            </div>
          </div>

          <!-- แสดงวันที่อัปเดตล่าสุด -->
          <div class="text-center text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-6">
            <p class="mb-2">
              ข้อมูล ณ วันที่: {{ formatDisplayDate() }}
            </p>
            <p class="text-xs">
              Risk Assessment System | พัฒนาด้วย Vue 3 + TypeScript + Vue-ChartJS | 
              รันบน Laravel 12 + XAMPP Windows Server 2016
            </p>
            <p class="text-xs mt-1 text-blue-600 dark:text-blue-400">
              http://10.40.67.84/project-name
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* เพิ่ม custom styles สำหรับ responsive design */
@media (max-width: 640px) {
  .grid {
    gap: 1rem;
  }
  
  .p-6 {
    padding: 1rem;
  }
}

/* เพิ่ม smooth transition สำหรับ dark mode */
* {
  transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

/* เพิ่ม hover effects */
.hover\:scale-105:hover {
  transform: scale(1.05);
}

/* เพิ่ม loading state (สำหรับอนาคต) */
.loading {
  opacity: 0.6;
  pointer-events: none;
}
</style>
