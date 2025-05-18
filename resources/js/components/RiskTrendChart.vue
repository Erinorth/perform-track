<script setup lang="ts">
// นำเข้าไอคอนและฟังก์ชันที่จำเป็น
import { LucideTrendingUp, LucideRefreshCw } from 'lucide-vue-next'
import { ref, onMounted, watch, defineProps, computed } from 'vue'
import { toast } from 'vue-sonner'
// นำเข้า type สำหรับ TypeScript
import type ApexCharts from 'apexcharts'

// นำเข้า ApexCharts แบบ dynamic import เพื่อป้องกันปัญหาใน SSR
let ApexChartsLib: typeof ApexCharts | null = null
const chartSupported = ref(false)
const chart = ref<ApexCharts | null>(null)
const isLoading = ref(false)

// กำหนด interface สำหรับตัวเลือก
interface Option {
  value: string | number;
  label: string;
}

// กำหนด props สำหรับรับข้อมูลจากภายนอก
const props = defineProps<{
  data: Array<{
    date: string
    high: number
    medium: number
    low: number
    [key: string]: any
  }>,
  riskTypeOptions?: Option[]
}>()

// สร้าง event emitter
const emit = defineEmits(['filter'])

// ตัวกรองและการตั้งค่าเริ่มต้น
const selectedTimeRange = ref('month') // Default: 30 วันล่าสุด
const selectedRiskType = ref('all')    // Default: ทั้งหมด

// ตัวเลือกสำหรับตัวกรอง
const timeRangeOptions = [
  { value: 'week', label: '7 วันล่าสุด' },
  { value: 'month', label: '30 วันล่าสุด' },
  { value: 'quarter', label: '3 เดือนล่าสุด' },
  { value: 'year', label: '1 ปีล่าสุด' }
]

// ตรวจสอบและใช้ข้อมูลประเภทความเสี่ยงที่ได้รับจาก backend
const riskTypeOptionsList = computed(() => {
  if (props.riskTypeOptions && props.riskTypeOptions.length > 0) {
    return props.riskTypeOptions;
  }
  
  // ถ้าไม่มีข้อมูลประเภทความเสี่ยงจาก backend ให้ใช้ค่าเริ่มต้น
  return [
    { value: 'all', label: 'ทั้งหมด' }
  ];
});

// คำนวณจำนวนสูงสุดสำหรับกราฟเพื่อให้แสดงผลอย่างเหมาะสม
const maxValue = computed(() => {
  if (!props.data || props.data.length === 0) return 10
  
  // หาค่าสูงสุดของความเสี่ยงรวม (high + medium + low)
  const max = Math.max(...props.data.map(item => item.high + item.medium + item.low))
  // เพิ่มค่าอีก 20% เพื่อให้กราฟมีช่องว่างด้านบน
  return Math.ceil(max * 1.2)
})

// คำนวณจำนวนรวม
const totalRisks = computed(() => {
  if (!props.data || props.data.length === 0) return 0
  
  // ใช้ข้อมูลล่าสุด (index สุดท้าย)
  const latestData = props.data[props.data.length - 1]
  return latestData.high + latestData.medium + latestData.low
})

// ฟังก์ชันสำหรับสร้าง/อัปเดตกราฟ
const createChart = () => {
  // log การสร้างกราฟ
  console.log('[RiskTrendChart] Creating chart with data:', props.data)
  
  if (!props.data || props.data.length === 0) {
    console.warn('[RiskTrendChart] No data provided for chart')
    return
  }
  
  if (ApexChartsLib && document.getElementById('risk-trend-chart')) {
    // option สำหรับกราฟ
    const options = {
      series: [
        {
          name: 'ความเสี่ยงสูง (9-16)',
          data: props.data.map(item => item.high),
          color: '#EF4444' // red-500
        },
        {
          name: 'ความเสี่ยงกลาง (4-8)',
          data: props.data.map(item => item.medium),
          color: '#F59E0B' // yellow-500
        },
        {
          name: 'ความเสี่ยงต่ำ (1-3)',
          data: props.data.map(item => item.low),
          color: '#10B981' // green-500
        }
      ],
      chart: {
        type: 'line',
        height: 300,
        toolbar: {
          show: false // ซ่อน toolbar เพื่อความสะอาด
        },
        fontFamily: 'Sarabun, sans-serif',
        animations: {
          enabled: true,
          easing: 'easeinout',
          speed: 800
        }
      },
      dataLabels: {
        enabled: false // ไม่แสดง label บนจุดข้อมูล
      },
      stroke: {
        curve: 'smooth',
        width: 3
      },
      grid: {
        borderColor: '#e0e0e0',
        row: {
          colors: ['transparent', 'transparent']
        }
      },
      xaxis: {
        categories: props.data.map(item => item.date),
        labels: {
          style: {
            fontFamily: 'Sarabun, sans-serif',
          }
        },
        axisBorder: {
          show: true,
          color: '#e0e0e0'
        },
        tickAmount: Math.min(props.data.length, 6) // แสดง tick ตามความเหมาะสม
      },
      yaxis: {
        title: {
          text: 'จำนวนความเสี่ยง'
        },
        labels: {
          formatter: function (value: number) {
            return Math.round(value)
          }
        },
        min: 0,
        max: maxValue.value, // ใช้ค่าสูงสุดแบบไดนามิก
        forceNiceScale: true
      },
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: function (y: number | undefined) {
            if (typeof y !== "undefined") {
              return y.toFixed(0) + " รายการ";
            }
            return y;
          }
        },
        theme: 'light',
        style: {
          fontSize: '12px',
          fontFamily: 'Sarabun, sans-serif'
        }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'center',
        fontFamily: 'Sarabun, sans-serif',
        itemMargin: {
          horizontal: 10,
          vertical: 5
        }
      },
      responsive: [{
        breakpoint: 480,
        options: {
          legend: {
            position: 'bottom',
            offsetY: 5
          }
        }
      }]
    }

    // สร้าง/อัปเดตกราฟ
    if (chart.value) {
      chart.value.updateOptions(options)
    } else {
      // ใช้ non-null assertion (!) เพราะเราได้ตรวจสอบแล้วว่ามี element นี้
      chart.value = new ApexChartsLib(document.getElementById('risk-trend-chart')!, options)
      chart.value.render()
    }
  } else {
    console.warn('[RiskTrendChart] ApexCharts not available or DOM element not found')
  }
}

// อัปเดตกราฟเมื่อมีการเปลี่ยนตัวกรอง
const updateChart = () => {
  isLoading.value = true
  console.log(`[RiskTrendChart] Updating chart - Time range: ${selectedTimeRange.value}, Risk type: ${selectedRiskType.value}`)
  toast.info(`กำลังโหลดข้อมูลแนวโน้มความเสี่ยง (${selectedTimeRange.value})`, {
    duration: 1500 // แสดง toast 1.5 วินาที
  })
  
  // ส่ง event ไปยัง parent component เพื่อดึงข้อมูลใหม่ตามตัวกรอง
  emit('filter', {
    timeRange: selectedTimeRange.value,
    riskType: selectedRiskType.value
  })
  
  // จำลองการรอข้อมูล
  setTimeout(() => {
    isLoading.value = false
  }, 600)
}

// โหลด ApexCharts เมื่อ component ถูก mount
onMounted(async () => {
  try {
    // Dynamic import for ApexCharts
    const ApexChartsModule = await import('apexcharts')
    ApexChartsLib = ApexChartsModule.default
    chartSupported.value = true
    console.log('[RiskTrendChart] ApexCharts loaded successfully')
    
    // สร้างกราฟเมื่อ component ถูก mount
    if (props.data && props.data.length > 0) {
      createChart()
    } else {
      console.warn('[RiskTrendChart] No initial data for chart')
    }
  } catch (error) {
    // กรณีที่ไม่สามารถโหลด ApexCharts ได้
    console.error('[RiskTrendChart] Failed to load ApexCharts:', error)
    chartSupported.value = false
    toast.error('ไม่สามารถโหลด ApexCharts ได้', {
      description: 'กำลังแสดงข้อมูลในรูปแบบทดแทน',
    })
  }
})

// เมื่อ props.data มีการเปลี่ยนแปลง ให้อัปเดตกราฟ
watch(() => props.data, (newData) => {
  if (newData && newData.length > 0) {
    createChart()
  }
}, { deep: true })
</script>

<template>
  <div class="w-full bg-white dark:bg-gray-800 rounded-lg shadow-md transition-all duration-300 hover:shadow-lg p-5">
    <!-- ส่วนหัวกราฟ -->
    <div class="mb-4 font-bold text-lg flex items-center justify-between">
      <div class="flex items-center gap-2">
        <!-- ไอคอนจาก lucide-vue-next -->
        <LucideTrendingUp class="w-6 h-6 text-blue-500" />
        <span>แนวโน้มความเสี่ยง</span>
      </div>
      
      <!-- Badge แสดงจำนวนรวม -->
      <div class="text-sm font-normal py-1 px-2 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 rounded-full flex items-center">
        <span>ความเสี่ยงทั้งหมด: {{ totalRisks }}</span>
      </div>
    </div>
    
    <!-- ส่วนแสดงกราฟ -->
    <div class="h-72 relative">
      <!-- สถานะกำลังโหลด -->
      <div v-if="isLoading" class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 flex items-center justify-center z-10">
        <div class="flex flex-col items-center">
          <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <span class="mt-2 text-sm text-gray-500">กำลังโหลดข้อมูล...</span>
        </div>
      </div>
      
      <!-- ถ้าไม่มีข้อมูล -->
      <div v-if="!props.data || props.data.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-gray-500 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
        <div class="p-4 text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
          </svg>
          <p class="font-medium">ไม่พบข้อมูลแนวโน้ม</p>
          <p class="mt-1 text-sm">โปรดเพิ่มการประเมินความเสี่ยงก่อน</p>
        </div>
      </div>
      
      <!-- container สำหรับกราฟ -->
      <div id="risk-trend-chart" v-show="props.data && props.data.length > 0"></div>
    </div>
    
    <!-- คำอธิบายสี -->
    <div class="mt-6 flex flex-wrap justify-center items-center gap-x-6 gap-y-2 bg-gray-50 dark:bg-gray-700/30 py-2 px-3 rounded-md">
      <div class="flex items-center">
        <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
        <span class="text-sm">ความเสี่ยงสูง (9-16)</span>
      </div>
      <div class="flex items-center">
        <div class="w-3 h-3 rounded-full bg-yellow-400 mr-2"></div>
        <span class="text-sm">ความเสี่ยงกลาง (4-8)</span>
      </div>
      <div class="flex items-center">
        <div class="w-3 h-3 rounded-full bg-green-400 mr-2"></div>
        <span class="text-sm">ความเสี่ยงต่ำ (1-3)</span>
      </div>
    </div>
    
    <!-- ส่วนตัวกรอง (filter) -->
    <div class="mt-6 flex flex-wrap gap-3">
      <div class="flex-1">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ช่วงเวลา</label>
        <select 
          v-model="selectedTimeRange" 
          class="w-full h-10 px-3 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-blue-500 dark:focus:border-blue-600 transition-all"
          @change="updateChart"
        >
          <option v-for="option in timeRangeOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>
      <div class="flex-1">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทความเสี่ยง</label>
        <select 
          v-model="selectedRiskType" 
          class="w-full h-10 px-3 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-blue-500 dark:focus:border-blue-600 transition-all"
          @change="updateChart"
        >
          <option v-for="option in riskTypeOptionsList" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>

<!--
  - RiskTrendChart.vue: แสดงแนวโน้มความเสี่ยงในช่วงเวลาต่างๆ
  - ปรับปรุงเพื่อรองรับการแบ่งระดับความเสี่ยงเป็น 3 ระดับ:
    * สูง (9-16): สีแดง
    * กลาง (4-8): สีเหลือง
    * ต่ำ (1-3): สีเขียว
  - คุณสมบัติที่สำคัญ:
    1. ใช้ข้อมูลจริงจาก API และรับ props.riskTypeOptions จาก controller
    2. ไม่ใช้ dummyChartData อีกต่อไป
    3. รับตัวเลือกประเภทความเสี่ยงจาก organizational_risks.risk_name
    4. แสดงผลเฉพาะกราฟหรือข้อความแจ้งเตือนเมื่อไม่มีข้อมูล
    5. รองรับการกรองข้อมูลตามช่วงเวลาและประเภทความเสี่ยง
-->
