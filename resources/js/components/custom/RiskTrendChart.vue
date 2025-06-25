<template>
  <div class="w-full h-full">
    <!-- ส่วนหัวของกราฟพร้อม Filter Controls -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          แนวโน้มความเสี่ยง
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          การเปลี่ยนแปลงของความเสี่ยงตามเวลา
        </p>
      </div>
      
      <!-- ปุ่มกรองข้อมูล -->
      <div class="flex flex-col sm:flex-row gap-2">
        <!-- ตัวเลือกช่วงเวลา -->
        <select 
          v-model="selectedTimeRange" 
          @change="handleFilterChange"
          class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
        >
          <option value="7">7 วันล่าสุด</option>
          <option value="30">30 วันล่าสุด</option>
          <option value="90">90 วันล่าสุด</option>
          <option value="365">1 ปีล่าสุด</option>
        </select>

        <!-- ตัวเลือกประเภทความเสี่ยง -->
        <select 
          v-model="selectedRiskType" 
          @change="handleFilterChange"
          class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
        >
          <option value="all">ทุกประเภท</option>
          <option 
            v-for="option in riskTypeOptions" 
            :key="option.value" 
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>

        <!-- ปุ่มรีเฟรช -->
        <button 
          @click="refreshChart"
          class="inline-flex items-center px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <RefreshCw class="w-4 h-4 mr-1" />
          รีเฟรช
        </button>
      </div>
    </div>

    <!-- กราฟแสดงแนวโน้ม -->
    <div class="relative" style="height: 400px;">
      <!-- แสดงกราฟเมื่อมีข้อมูล -->
      <div v-if="hasData" class="h-full">
        <Line
          ref="chartRef"
          :data="chartData"
          :options="chartOptions"
          class="h-full"
        />
      </div>
      
      <!-- แสดงข้อความเมื่อไม่มีข้อมูล -->
      <div v-else class="flex flex-col items-center justify-center h-full text-gray-500">
        <TrendingUp class="w-16 h-16 mb-4 text-gray-300" />
        <p class="text-lg font-medium">ไม่พบข้อมูลแนวโน้ม</p>
        <p class="text-sm mt-1">โปรดเพิ่มการประเมินความเสี่ยงก่อน</p>
      </div>
    </div>

    <!-- แสดงสถิติสรุป -->
    <div v-if="hasData" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
          {{ totalHighRisk }}
        </div>
        <div class="text-sm text-red-600 dark:text-red-400">
          ความเสี่ยงสูงรวม
        </div>
      </div>
      <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
          {{ totalMediumRisk }}
        </div>
        <div class="text-sm text-yellow-600 dark:text-yellow-400">
          ความเสี่ยงกลางรวม
        </div>
      </div>
      <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
          {{ totalLowRisk }}
        </div>
        <div class="text-sm text-green-600 dark:text-green-400">
          ความเสี่ยงต่ำรวม
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'

// นำเข้าไอคอนจาก lucide-vue-next
import { TrendingUp, RefreshCw } from 'lucide-vue-next'

// นำเข้า toast สำหรับแจ้งเตือน
import { toast } from 'vue-sonner'

// นำเข้า types และ composable
import type { TrendData, FilterParams } from '@/types/chart'
import { useChartData } from '@/composables/useChartData'

// ลงทะเบียน Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

// กำหนด interface สำหรับตัวเลือก
interface Option {
  value: string | number
  label: string
}

// กำหนด props
interface Props {
  data: TrendData[]
  riskTypeOptions?: Option[]
}

// กำหนด emits
interface Emits {
  filter: [value: FilterParams]
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
  riskTypeOptions: () => []
})

const emit = defineEmits<Emits>()

// ใช้ composable สำหรับจัดการข้อมูลกราฟ
const {
  chartData,
  chartOptions,
  selectedTimeRange,
  selectedRiskType,
  updateFilter,
  filteredData
} = useChartData(props.data)

// สร้าง ref สำหรับกราฟ
const chartRef = ref()

// ตรวจสอบว่ามีข้อมูลหรือไม่
const hasData = computed(() => {
  return props.data && props.data.length > 0
})

// คำนวณสถิติรวม
const totalHighRisk = computed(() => {
  return filteredData.value.reduce((sum, item) => sum + item.high, 0)
})

const totalMediumRisk = computed(() => {
  return filteredData.value.reduce((sum, item) => sum + item.medium, 0)
})

const totalLowRisk = computed(() => {
  return filteredData.value.reduce((sum, item) => sum + item.low, 0)
})

// ฟังก์ชันจัดการการเปลี่ยน filter
const handleFilterChange = () => {
  // อัปเดต filter ใน composable
  updateFilter(selectedTimeRange.value, selectedRiskType.value)
  
  // ส่ง event ไปยัง parent component
  emit('filter', {
    timeRange: selectedTimeRange.value,
    riskType: selectedRiskType.value
  })
  
  // แสดง toast แจ้งการเปลี่ยนแปลง
  toast.success('อัปเดตการกรองข้อมูลแล้ว', {
    description: `ช่วงเวลา: ${selectedTimeRange.value} วัน, ประเภท: ${selectedRiskType.value}`,
    duration: 2000
  })
  
  console.log('Filter changed:', {
    timeRange: selectedTimeRange.value,
    riskType: selectedRiskType.value
  })
}

// ฟังก์ชันรีเฟรชกราฟ
const refreshChart = () => {
  if (chartRef.value) {
    chartRef.value.update()
  }
  
  toast.info('รีเฟรชกราฟแล้ว', {
    duration: 1500
  })
  
  console.log('Chart refreshed')
}

// เฝ้าติดตามการเปลี่ยนแปลงข้อมูล
watch(() => props.data, (newData) => {
  console.log('Trend data updated:', newData)
  
  if (newData && newData.length > 0) {
    toast.success('ข้อมูลแนวโน้มอัปเดตแล้ว')
  }
}, { deep: true })
</script>
