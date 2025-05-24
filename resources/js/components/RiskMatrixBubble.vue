<template>
  <div class="w-full h-full">
    <!-- ส่วนหัวของ Risk Matrix -->
    <div class="mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
        Risk Matrix (4x4) - Bubble Chart
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400">
        แกน X = ระดับโอกาส (Likelihood), แกน Y = ระดับผลกระทบ (Impact), ขนาด bubble = จำนวนความเสี่ยง
      </p>
    </div>

    <!-- ปุ่มกรองข้อมูล -->
    <div class="flex flex-wrap gap-2 mb-4">
      <select 
        v-model="selectedRiskType" 
        @change="handleFilterChange"
        class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
      >
        <option value="all">ทุกประเภทความเสี่ยง</option>
        <option 
          v-for="option in riskTypeOptions" 
          :key="option.value" 
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
      
      <button 
        @click="refreshMatrix"
        class="inline-flex items-center px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <RefreshCw class="w-4 h-4 mr-1" />
        รีเฟรช
      </button>
    </div>

    <!-- กราฟ Bubble Chart -->
    <div class="relative" style="height: 450px;">
      <div v-if="hasData" class="h-full">
        <Bubble
          ref="bubbleRef"
          :data="bubbleChartData"
          :options="bubbleOptions"
          class="h-full"
          @click="handleBubbleClick"
        />
      </div>
      
      <!-- แสดงข้อความเมื่อไม่มีข้อมูล -->
      <div v-else class="flex flex-col items-center justify-center h-full text-gray-500">
        <Grid class="w-16 h-16 mb-4 text-gray-300" />
        <p class="text-lg font-medium">ไม่พบข้อมูล Risk Matrix</p>
        <p class="text-sm mt-1">โปรดเพิ่มการประเมินความเสี่ยงก่อน</p>
        <button 
          @click="$emit('add-assessment')"
          class="mt-3 px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
        >
          เพิ่มการประเมินความเสี่ยง
        </button>
      </div>
    </div>

    <!-- คำอธิบาย Risk Level พร้อมสถิติ -->
    <div v-if="hasData" class="mt-6 space-y-4">
      <!-- คำอธิบายสีและสถิติ -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg transition-colors hover:bg-green-100 dark:hover:bg-green-900/30">
          <div class="w-4 h-4 bg-green-500 rounded-full flex-shrink-0"></div>
          <div class="text-xs">
            <div class="font-semibold text-green-700 dark:text-green-400">ต่ำ (1-3)</div>
            <div class="text-green-600 dark:text-green-500">{{ riskStats.low }} ความเสี่ยง</div>
          </div>
        </div>
        <div class="flex items-center gap-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg transition-colors hover:bg-yellow-100 dark:hover:bg-yellow-900/30">
          <div class="w-4 h-4 bg-yellow-500 rounded-full flex-shrink-0"></div>
          <div class="text-xs">
            <div class="font-semibold text-yellow-700 dark:text-yellow-400">กลาง (4-8)</div>
            <div class="text-yellow-600 dark:text-yellow-500">{{ riskStats.medium }} ความเสี่ยง</div>
          </div>
        </div>
        <div class="flex items-center gap-2 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg transition-colors hover:bg-orange-100 dark:hover:bg-orange-900/30">
          <div class="w-4 h-4 bg-orange-500 rounded-full flex-shrink-0"></div>
          <div class="text-xs">
            <div class="font-semibold text-orange-700 dark:text-orange-400">สูง (9-12)</div>
            <div class="text-orange-600 dark:text-orange-500">{{ riskStats.high }} ความเสี่ยง</div>
          </div>
        </div>
        <div class="flex items-center gap-2 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg transition-colors hover:bg-red-100 dark:hover:bg-red-900/30">
          <div class="w-4 h-4 bg-red-500 rounded-full flex-shrink-0"></div>
          <div class="text-xs">
            <div class="font-semibold text-red-700 dark:text-red-400">สูงมาก (13-16)</div>
            <div class="text-red-600 dark:text-red-500">{{ riskStats.critical }} ความเสี่ยง</div>
          </div>
        </div>
      </div>

      <!-- แสดงสถิติรวม -->
      <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
          <div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
              {{ totalRisks }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              ความเสี่ยงทั้งหมด
            </div>
          </div>
          <div>
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">
              {{ riskStats.critical + riskStats.high }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              ต้องดูแลเร่งด่วน
            </div>
          </div>
          <div>
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
              {{ riskStats.medium }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              ต้องติดตาม
            </div>
          </div>
          <div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
              {{ riskStats.low }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              ในเกณฑ์ปกติ
            </div>
          </div>
        </div>
      </div>

      <!-- คำแนะนำการใช้งาน -->
      <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h4 class="font-semibold text-blue-900 dark:text-blue-400 mb-2">
          <Info class="w-4 h-4 inline mr-1" />
          คำแนะนำการใช้งาน
        </h4>
        <div class="text-sm text-blue-800 dark:text-blue-300 space-y-1">
          <p><strong>การอ่านกราฟ:</strong> คลิกที่ bubble เพื่อดูรายละเอียดความเสี่ยง</p>
          <p><strong>ขนาด bubble:</strong> ยิ่งใหญ่ = มีความเสี่ยงมาก, ยิ่งเล็ก = มีความเสี่ยงน้อย</p>
          <p><strong>สี bubble:</strong> แสดงระดับความเสี่ยง (เขียว=ต่ำ, เหลือง=กลาง, ส้ม=สูง, แดง=สูงมาก)</p>
          <p><strong>ตำแหน่ง:</strong> แกน X = โอกาส, แกน Y = ผลกระทบ</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Bubble } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  Title,
  Tooltip,
  Legend,
  type TooltipItem,
  type ChartEvent,
  type ActiveElement
} from 'chart.js'

// นำเข้าไอคอนจาก lucide-vue-next
import { Grid, RefreshCw, Info } from 'lucide-vue-next'

// นำเข้า toast สำหรับแจ้งเตือน
import { toast } from 'vue-sonner'

// ลงทะเบียน Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  Title,
  Tooltip,
  Legend
)

// กำหนด interface สำหรับข้อมูล Risk Assessment ตาม database schema
interface RiskAssessmentData {
  likelihood_level: number // 1-4 ตาม likelihood_criteria table
  impact_level: number     // 1-4 ตาม impact_criteria table
  risk_score: number       // likelihood_level * impact_level
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

// กำหนด interface สำหรับตัวเลือกประเภทความเสี่ยง
interface RiskTypeOption {
  value: string | number
  label: string
}

// กำหนด interface สำหรับข้อมูล bubble
interface BubbleDataPoint {
  x: number
  y: number
  r: number
  riskCount: number
  riskScore: number
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

// กำหนด interface สำหรับ dataset
interface BubbleDataset {
  label: string
  data: BubbleDataPoint[]
  backgroundColor: string
  borderColor: string
  borderWidth: number
  hoverBackgroundColor: string
  hoverBorderWidth: number
}

// กำหนด interface สำหรับ chart data
interface BubbleChartData {
  datasets: BubbleDataset[]
}

interface Props {
  data: RiskAssessmentData[]
  riskTypeOptions?: RiskTypeOption[]
}

interface Emits {
  'add-assessment': []
  'filter-change': [value: { riskType: string | number }]
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
  riskTypeOptions: () => []
})

const emit = defineEmits<Emits>()

const bubbleRef = ref()
const selectedRiskType = ref<string | number>('all')

// เพิ่ม debug log สำหรับติดตามข้อมูล
watch(() => props.data, (newData) => {
  console.log('RiskMatrixBubble received data:', newData)
  console.log('Data length:', newData?.length || 0)
  if (newData && newData.length > 0) {
    console.log('First item:', newData[0])
    console.log('Risks in first item:', newData[0].risks?.length || 0)
  }
}, { immediate: true, deep: true })

// ตรวจสอบว่ามีข้อมูลหรือไม่
const hasData = computed(() => {
  const result = props.data && props.data.length > 0 && 
         props.data.some(item => item.risks && item.risks.length > 0)
  console.log('hasData computed:', result)
  return result
})

// คำนวณสถิติความเสี่ยงแต่ละระดับ
const riskStats = computed(() => {
  const stats = { low: 0, medium: 0, high: 0, critical: 0 }
  
  if (!hasData.value) return stats
  
  props.data.forEach(item => {
    const riskScore = item.likelihood_level * item.impact_level
    const riskCount = item.risks?.length || 0
    
    if (riskScore >= 1 && riskScore <= 3) {
      stats.low += riskCount
    } else if (riskScore >= 4 && riskScore <= 8) {
      stats.medium += riskCount
    } else if (riskScore >= 9 && riskScore <= 12) {
      stats.high += riskCount
    } else if (riskScore >= 13 && riskScore <= 16) {
      stats.critical += riskCount
    }
  })
  
  return stats
})

// คำนวณจำนวนความเสี่ยงทั้งหมด
const totalRisks = computed(() => {
  return riskStats.value.low + riskStats.value.medium + 
         riskStats.value.high + riskStats.value.critical
})

// แปลงข้อมูลสำหรับ Bubble Chart
const bubbleChartData = computed((): BubbleChartData => {
  if (!hasData.value) {
    return { datasets: [] }
  }

  // กลุ่มข้อมูลตามระดับความเสี่ยง
  const riskGroups: {
    low: BubbleDataPoint[]
    medium: BubbleDataPoint[]
    high: BubbleDataPoint[]
    critical: BubbleDataPoint[]
  } = {
    low: [],      // 1-3
    medium: [],   // 4-8
    high: [],     // 9-12
    critical: []  // 13-16
  }

  props.data.forEach(item => {
    const riskScore = item.likelihood_level * item.impact_level
    const riskCount = item.risks?.length || 0
    
    if (riskCount === 0) return // ข้ามถ้าไม่มีความเสี่ยง

    const bubbleData: BubbleDataPoint = {
      x: item.likelihood_level,
      y: item.impact_level,
      r: Math.max(8, Math.min(60, riskCount * 6)), // ขนาด bubble 8-60 pixels
      riskCount: riskCount,
      riskScore: riskScore,
      risks: item.risks
    }

    if (riskScore >= 1 && riskScore <= 3) {
      riskGroups.low.push(bubbleData)
    } else if (riskScore >= 4 && riskScore <= 8) {
      riskGroups.medium.push(bubbleData)
    } else if (riskScore >= 9 && riskScore <= 12) {
      riskGroups.high.push(bubbleData)
    } else if (riskScore >= 13 && riskScore <= 16) {
      riskGroups.critical.push(bubbleData)
    }
  })

  return {
    datasets: [
      {
        label: 'ความเสี่ยงต่ำ (1-3)',
        data: riskGroups.low,
        backgroundColor: 'rgba(34, 197, 94, 0.7)',
        borderColor: 'rgb(34, 197, 94)',
        borderWidth: 2,
        hoverBackgroundColor: 'rgba(34, 197, 94, 0.9)',
        hoverBorderWidth: 3
      },
      {
        label: 'ความเสี่ยงกลาง (4-8)',
        data: riskGroups.medium,
        backgroundColor: 'rgba(245, 158, 11, 0.7)',
        borderColor: 'rgb(245, 158, 11)',
        borderWidth: 2,
        hoverBackgroundColor: 'rgba(245, 158, 11, 0.9)',
        hoverBorderWidth: 3
      },
      {
        label: 'ความเสี่ยงสูง (9-12)',
        data: riskGroups.high,
        backgroundColor: 'rgba(249, 115, 22, 0.7)',
        borderColor: 'rgb(249, 115, 22)',
        borderWidth: 2,
        hoverBackgroundColor: 'rgba(249, 115, 22, 0.9)',
        hoverBorderWidth: 3
      },
      {
        label: 'ความเสี่ยงสูงมาก (13-16)',
        data: riskGroups.critical,
        backgroundColor: 'rgba(239, 68, 68, 0.7)',
        borderColor: 'rgb(239, 68, 68)',
        borderWidth: 2,
        hoverBackgroundColor: 'rgba(239, 68, 68, 0.9)',
        hoverBorderWidth: 3
      }
    ]
  }
})

// ตัวเลือกสำหรับ Bubble Chart
const bubbleOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
      display: true,
      labels: {
        usePointStyle: true,
        padding: 15,
        font: {
          size: 12
        }
      }
    },
    title: {
      display: false
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.9)',
      titleColor: 'white',
      bodyColor: 'white',
      borderColor: 'rgba(255, 255, 255, 0.2)',
      borderWidth: 1,
      cornerRadius: 8,
      callbacks: {
        title: function(context: TooltipItem<'bubble'>[]) {
          const data = context[0].raw as BubbleDataPoint
          return `โอกาส: ${data.x}, ผลกระทบ: ${data.y}`
        },
        label: function(context: TooltipItem<'bubble'>) {
          const data = context.raw as BubbleDataPoint
          return [
            `${context.dataset.label}`,
            `Risk Score: ${data.riskScore}`,
            `จำนวนความเสี่ยง: ${data.riskCount} รายการ`,
            '',
            'คลิกเพื่อดูรายละเอียด'
          ]
        }
      }
    }
  },
  scales: {
    x: {
      display: true,
      title: {
        display: true,
        text: 'ระดับโอกาส (Likelihood Level)',
        font: {
          size: 14,
          weight: 'bold' as const
        }
      },
      min: 0.5,
      max: 4.5,
      ticks: {
        stepSize: 1,
        callback: function(value: string | number) {
          const numValue = Number(value)
          const labels: Record<number, string> = {
            1: 'น้อยมาก (1)',
            2: 'น้อย (2)', 
            3: 'มาก (3)',
            4: 'มากที่สุด (4)'
          }
          return labels[numValue] || ''
        },
        font: {
          size: 11
        }
      },
      grid: {
        color: 'rgba(0, 0, 0, 0.1)',
        lineWidth: 1
      }
    },
    y: {
      display: true,
      title: {
        display: true,
        text: 'ระดับผลกระทบ (Impact Level)',
        font: {
          size: 14,
          weight: 'bold' as const
        }
      },
      min: 0.5,
      max: 4.5,
      ticks: {
        stepSize: 1,
        callback: function(value: string | number) {
          const numValue = Number(value)
          const labels: Record<number, string> = {
            1: 'น้อยมาก (1)',
            2: 'น้อย (2)',
            3: 'มาก (3)', 
            4: 'มากที่สุด (4)'
          }
          return labels[numValue] || ''
        },
        font: {
          size: 11
        }
      },
      grid: {
        color: 'rgba(0, 0, 0, 0.1)',
        lineWidth: 1
      }
    }
  },
  interaction: {
    intersect: false,
    mode: 'point' as const
  },
  onHover: (event: ChartEvent, elements: ActiveElement[]) => {
    const target = event.native?.target as HTMLElement
    if (target) {
      target.style.cursor = elements.length > 0 ? 'pointer' : 'default'
    }
  }
}))

// ฟังก์ชันจัดการการเปลี่ยน filter
const handleFilterChange = () => {
  emit('filter-change', {
    riskType: selectedRiskType.value
  })
  
  const selectedOption = props.riskTypeOptions?.find(opt => opt.value === selectedRiskType.value)
  const filterText = selectedOption?.label || selectedRiskType.value
  
  toast.success('กรองข้อมูลแล้ว', {
    description: `แสดงข้อมูล: ${filterText}`,
    duration: 2000
  })
  
  console.log('Risk Matrix filter changed:', selectedRiskType.value)
}

// ฟังก์ชันรีเฟรช Matrix
const refreshMatrix = () => {
  if (bubbleRef.value) {
    bubbleRef.value.update()
  }
  
  toast.info('รีเฟรช Risk Matrix แล้ว', {
    duration: 1500
  })
  
  console.log('Risk Matrix refreshed')
}

// ฟังก์ชันจัดการคลิกที่ bubble
const handleBubbleClick = (event: ChartEvent) => {
  console.log('Bubble clicked:', event)
  
  const chart = bubbleRef.value?.chart
  if (!chart) return
  
  const elements = chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, false)
  
  if (elements.length > 0) {
    const element = elements[0]
    const datasetIndex = element.datasetIndex
    const index = element.index
    const data = bubbleChartData.value.datasets[datasetIndex].data[index]
    
    if (data && data.risks) {
      // แสดงรายละเอียดความเสี่ยงใน toast
      const riskNames = data.risks.slice(0, 3).map((risk: any) => risk.risk_name).join(', ')
      const moreRisks = data.risks.length > 3 ? ` และอีก ${data.risks.length - 3} รายการ` : ''
      
      toast.info(`ความเสี่ยงในจุดนี้ (${data.riskCount} รายการ)`, {
        description: `${riskNames}${moreRisks}`,
        duration: 5000
      })
      
      console.log('Risk details:', data.risks)
    }
  }
}
</script>
