import { computed, ref } from 'vue'
import type { ChartData, ChartOptions, TrendData } from '@/types/chart'

// Composable สำหรับจัดการข้อมูลกราฟ
export function useChartData(trendData: TrendData[]) {
  // สถานะสำหรับ filter
  const selectedTimeRange = ref('30')
  const selectedRiskType = ref('all')

  // ข้อมูลกราฟที่ผ่านการกรอง
  const filteredData = computed(() => {
    if (!trendData || trendData.length === 0) return []
    
    // กรองข้อมูลตามช่วงเวลาที่เลือก
    const days = parseInt(selectedTimeRange.value)
    const cutoffDate = new Date()
    cutoffDate.setDate(cutoffDate.getDate() - days)
    
    return trendData.filter(item => {
      const itemDate = new Date(item.date)
      return itemDate >= cutoffDate
    })
  })

  // ข้อมูลสำหรับ Chart.js (เพิ่ม critical)
  const chartData = computed((): ChartData => {
    const data = filteredData.value
    
    return {
      labels: data.map(item => {
        const date = new Date(item.date)
        return date.toLocaleDateString('th-TH', { 
          month: 'short', 
          day: 'numeric' 
        })
      }),
      datasets: [
        {
          label: 'ความเสี่ยงสูงมาก',
          data: data.map(item => item.critical || 0),
          backgroundColor: 'rgba(220, 38, 38, 0.1)',
          borderColor: 'rgb(220, 38, 38)',
          borderWidth: 2,
          tension: 0.1
        },
        {
          label: 'ความเสี่ยงสูง',
          data: data.map(item => item.high),
          backgroundColor: 'rgba(249, 115, 22, 0.1)',
          borderColor: 'rgb(249, 115, 22)',
          borderWidth: 2,
          tension: 0.1
        },
        {
          label: 'ความเสี่ยงกลาง',
          data: data.map(item => item.medium),
          backgroundColor: 'rgba(245, 158, 11, 0.1)',
          borderColor: 'rgb(245, 158, 11)',
          borderWidth: 2,
          tension: 0.1
        },
        {
          label: 'ความเสี่ยงต่ำ',
          data: data.map(item => item.low),
          backgroundColor: 'rgba(34, 197, 94, 0.1)',
          borderColor: 'rgb(34, 197, 94)',
          borderWidth: 2,
          tension: 0.1
        }
      ]
    }
  })

  // ตัวเลือกสำหรับกราฟ
  const chartOptions = computed((): ChartOptions => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
        display: true
      },
      title: {
        display: true,
        text: 'แนวโน้มความเสี่ยงตามช่วงเวลา'
      }
    },
    scales: {
      x: {
        display: true,
        title: {
          display: true,
          text: 'วันที่'
        }
      },
      y: {
        display: true,
        title: {
          display: true,
          text: 'จำนวนความเสี่ยง'
        },
        beginAtZero: true
      }
    }
  }))

  // ฟังก์ชันสำหรับอัปเดต filter
  const updateFilter = (timeRange: string, riskType: string | number) => {
    selectedTimeRange.value = timeRange
    selectedRiskType.value = riskType.toString()
  }

  return {
    chartData,
    chartOptions,
    selectedTimeRange,
    selectedRiskType,
    updateFilter,
    filteredData
  }
}
