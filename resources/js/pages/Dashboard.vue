<script setup lang="ts">
// นำเข้า Inertia และ Head สำหรับจัดการ page
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

// นำเข้า Layout หลักของแอป
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types';

// นำเข้า HeaderSection component
import HeaderSection from './HeaderSection.vue'

// นำเข้าไอคอนจาก lucide-vue-next
import { LucideActivity } from 'lucide-vue-next'

// นำเข้า component ที่สร้างเอง
import SummaryCard from '@/components/SummaryCard.vue'
import RiskHeatmap from '@/components/RiskHeatmap.vue'
import RiskTrendChart from '@/components/RiskTrendChart.vue'

// นำเข้า toast สำหรับแจ้งเตือน
import { toast } from 'vue-sonner'

// กำหนด interface สำหรับข้อมูลแนวโน้ม
interface TrendData {
  date: string
  high: number
  medium: number
  low: number
}

// กำหนด interface สำหรับ props ที่รับจาก Laravel
interface Props {
  // แก้ไขให้ทุก property เป็น optional (?) เพื่อรองรับกรณีไม่มีข้อมูล
  trendData?: TrendData[]
  riskSummary?: {
    high: number
    medium: number
    low: number
  }
  heatmapData?: Array<{
    likelihood: number
    impact: number
    risks: any[]
  }>
  recentIncidents?: number
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'ภาพรวมความเสี่ยง',
        href: '/dashboard',
    },
];

// รับ props จาก Laravel controller ผ่าน Inertia
// แทนที่จะใช้ defineProps<Props>() ให้กำหนดค่าเริ่มต้นด้วย
const props = withDefaults(defineProps<Props>(), {
  trendData: () => [],
  heatmapData: () => [],
  recentIncidents: 0
})

// log การเข้าใช้งาน dashboard
console.log('Dashboard loaded', props)

// ฟังก์ชันสำหรับแสดง toast เมื่อคลิกที่กราฟ/ข้อมูล
const showRiskSummary = () => {
  const summary = props.riskSummary || { high: 0, medium: 0, low: 0 }
  const total = summary.high + summary.medium + summary.low
  toast.info(`จำนวนความเสี่ยงทั้งหมด ${total} รายการ`, {
    description: `แยกเป็น: สูง (9-16) ${summary.high}, กลาง (4-8) ${summary.medium}, ต่ำ (1-3) ${summary.low}`,
    duration: 3000
  })
}
</script>

<template>
  <!-- กำหนดชื่อเรื่องของหน้าเว็บ -->
  <Head title="ภาพรวมความเสี่ยง" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- ส่วนหัวที่แยกเป็น Component ต่างหาก -->
      <HeaderSection 
        title="ภาพรวมความเสี่ยง"
        description="ภาพรวมความเสี่ยงทั้งหมดในระบบ"
      />
      
      <!-- เนื้อหาหลักของหน้า -->
      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <!-- การ์ดสรุปข้อมูลความเสี่ยง -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <SummaryCard 
              icon="alert-triangle" 
              title="ความเสี่ยงสูง (9-16)" 
              :value="(props.riskSummary?.high || 0)" 
              color="red" 
              @click="showRiskSummary"
            />
            <SummaryCard 
              icon="alert-circle" 
              title="ความเสี่ยงกลาง (4-8)" 
              :value="(props.riskSummary?.medium || 0)" 
              color="yellow" 
              @click="showRiskSummary"
            />
            <SummaryCard 
              icon="alert-octagon" 
              title="ความเสี่ยงต่ำ (1-3)" 
              :value="(props.riskSummary?.low || 0)" 
              color="green" 
              @click="showRiskSummary"
            />
            <SummaryCard 
              icon="activity" 
              title="เหตุการณ์ล่าสุด" 
              :value="props.recentIncidents || 0" 
              color="blue"
            />
          </div>

          <!-- กราฟ Heatmap และ Trend -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Heat Map (หากไม่มีข้อมูลจะแสดงข้อความ) -->
            <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-4">
              <div v-if="props.heatmapData && props.heatmapData.length > 0">
                <RiskHeatmap :data="props.heatmapData" />
              </div>
              <div v-else class="flex flex-col items-center justify-center h-64 text-gray-500">
                <div class="text-center p-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                  </svg>
                  <p class="font-medium">ไม่พบข้อมูล Risk Matrix</p>
                  <p class="mt-1 text-sm">โปรดเพิ่มการประเมินความเสี่ยงก่อน</p>
                </div>
              </div>
            </div>

            <!-- Trend Chart (หากไม่มีข้อมูลจะแสดงข้อความ) -->
            <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-4">
              <div v-if="props.trendData && props.trendData.length > 0">
                <RiskTrendChart :data="props.trendData" />
              </div>
              <div v-else class="flex flex-col items-center justify-center h-64 text-gray-500">
                <div class="text-center p-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                  </svg>
                  <p class="font-medium">ไม่พบข้อมูลแนวโน้ม</p>
                  <p class="mt-1 text-sm">โปรดเพิ่มการประเมินความเสี่ยงก่อน</p>
                </div>
              </div>
            </div>
          </div>

          <!-- แสดงวันที่อัปเดตล่าสุด -->
          <div class="text-center text-sm text-gray-500">
            <p>ข้อมูล ณ วันที่: {{ new Date().toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
