<!-- resources\js\components\RiskHeatmap.vue -->
<script setup lang="ts">
// นำเข้าไอคอนจาก lucide-vue-next
import { LucideGrid, LucideAlertCircle, LucideInfo } from 'lucide-vue-next'
import { defineProps, ref, computed } from 'vue'
import { toast } from 'vue-sonner'

// กำหนด interface สำหรับข้อมูล heatmap
interface RiskDataPoint {
  likelihood: number // ค่าโอกาส (1-4)
  impact: number     // ค่าผลกระทบ (1-4)
  risks?: Array<any> // รายการความเสี่ยง (optional)
}

// props: รับข้อมูล heatmap จาก parent component
const props = defineProps<{
  data: Array<RiskDataPoint>
}>()

// สถานะแสดง/ซ่อนคำอธิบาย
const showLegend = ref(false)

// ระดับโอกาสเรียงจากมากไปน้อย (แกน Y)
const likelihoodLevels = [
  { level: 4, name: 'โอกาสสูง' },
  { level: 3, name: 'โอกาสปานกลาง' },
  { level: 2, name: 'โอกาสน้อย' },
  { level: 1, name: 'โอกาสต่ำ' }
]

// ระดับผลกระทบเรียงจากน้อยไปมาก (แกน X)
const impactLevels = [
  { level: 1, name: 'ผลกระทบต่ำ' },
  { level: 2, name: 'ผลกระทบปานกลาง' },
  { level: 3, name: 'ผลกระทบสูง' },
  { level: 4, name: 'ผลกระทบรุนแรง' }
]

// นับจำนวนความเสี่ยงแต่ละระดับ (สำหรับแสดงที่ legend)
const riskLevelCounts = computed(() => {
  let high = 0, medium = 0, low = 0
  
  props.data.forEach(item => {
    const score = item.likelihood * item.impact
    if (score >= 9) high++
    else if (score >= 4) medium++
    else low++
  })
  
  return { high, medium, low, total: props.data.length }
})

/**
 * คำนวณ risk score จากโอกาสและผลกระทบ
 * @param likelihood โอกาสเกิด (1-4)
 * @param impact ผลกระทบ (1-4)
 * @returns คะแนนความเสี่ยง (1-16)
 */
function getScore(likelihood: number, impact: number): number {
  return likelihood * impact
}

/**
 * นับจำนวนความเสี่ยงในแต่ละช่อง
 * @param likelihood ระดับโอกาส
 * @param impact ระดับผลกระทบ
 * @returns จำนวนความเสี่ยง
 */
function riskCount(likelihood: number, impact: number): number {
  return props.data.filter(
    r => r.likelihood === likelihood && r.impact === impact
  ).length
}

/**
 * กำหนดสีพื้นหลังและสีข้อความตามระดับความเสี่ยง (ปรับเป็น 3 ระดับ)
 * - สูง (9-16): สีแดง
 * - กลาง (4-8): สีเหลือง
 * - ต่ำ (1-3): สีเขียว
 * @param score คะแนนความเสี่ยง
 * @returns CSS classes สำหรับการแสดงผล
 */
function cellClass(score: number): string {
  if (score >= 9) return 'bg-red-500 bg-opacity-85 text-white hover:bg-red-600'
  if (score >= 4) return 'bg-yellow-400 bg-opacity-85 text-gray-900 hover:bg-yellow-500'
  return 'bg-green-400 bg-opacity-85 text-gray-900 hover:bg-green-500'
}

/**
 * ส่งคืนระดับความเสี่ยงเป็นข้อความ
 * @param score คะแนนความเสี่ยง
 * @returns ข้อความระดับความเสี่ยง
 */
function getRiskLevelText(score: number): string {
  if (score >= 9) return 'สูง'
  if (score >= 4) return 'กลาง'
  return 'ต่ำ'
}

/**
 * แสดง tooltip เมื่อคลิกที่ช่อง
 * @param likelihood โอกาส
 * @param impact ผลกระทบ
 */
function showRisks(likelihood: number, impact: number): void {
  const score = getScore(likelihood, impact)
  const level = getRiskLevelText(score)
  const risks = props.data.filter(
    r => r.likelihood === likelihood && r.impact === impact
  )
  
  console.log(`[RiskHeatmap] likelihood=${likelihood}, impact=${impact}, score=${score}, level=${level}, risks:`, risks)
  
  if (risks.length === 0) {
    toast.info('ไม่มีความเสี่ยงในจุดนี้', {
      description: `โอกาส: ${likelihood}, ผลกระทบ: ${impact}, ระดับ: ${level} (${score})`
    })
  } else {
    toast.info(`พบความเสี่ยง ${risks.length} รายการ`, {
      description: `โอกาส: ${likelihood}, ผลกระทบ: ${impact}, ระดับ: ${level} (${score})`
    })
  }
}

/**
 * สลับแสดง/ซ่อนคำอธิบาย
 */
function toggleLegend(): void {
  showLegend.value = !showLegend.value
}
</script>

<template>
  <div class="w-full overflow-hidden">
    <!-- ส่วนหัวของ component -->
    <div class="mb-4 flex items-center justify-between">
      <!-- หัวข้อและไอคอน -->
      <div class="flex items-center gap-2">
        <LucideGrid class="w-6 h-6 text-blue-500" />
        <span class="font-bold text-lg">Risk Matrix</span>
      </div>
      
      <!-- ปุ่มแสดงคำอธิบาย -->
      <button 
        @click="toggleLegend" 
        class="text-sm flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md"
      >
        <LucideInfo class="w-4 h-4" />
        <span>คำอธิบาย</span>
      </button>
    </div>
    
    <!-- ส่วนคำอธิบาย (แสดงเมื่อคลิกปุ่ม) -->
    <div v-if="showLegend" class="mb-4 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg text-sm">
      <div class="font-medium mb-2 flex items-center gap-1">
        <LucideAlertCircle class="w-4 h-4" />
        <span>ระดับความเสี่ยง (ใหม่ - 3 ระดับ)</span>
      </div>
      
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <div class="flex items-center">
          <div class="w-4 h-4 bg-red-500 rounded mr-2"></div>
          <span>สูง (9-16): {{ riskLevelCounts.high }} รายการ</span>
        </div>
        <div class="flex items-center">
          <div class="w-4 h-4 bg-yellow-400 rounded mr-2"></div>
          <span>กลาง (4-8): {{ riskLevelCounts.medium }} รายการ</span>
        </div>
        <div class="flex items-center">
          <div class="w-4 h-4 bg-green-400 rounded mr-2"></div>
          <span>ต่ำ (1-3): {{ riskLevelCounts.low }} รายการ</span>
        </div>
      </div>
      
      <div class="mt-2 text-xs text-gray-500">คำนวณจาก: คะแนนความเสี่ยง = โอกาส × ผลกระทบ</div>
    </div>
    
    <!-- แสดงหากไม่มีข้อมูล -->
    <div v-if="props.data.length === 0" class="flex flex-col items-center justify-center min-h-[200px] bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
      <LucideGrid class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-2" />
      <p class="text-gray-500 dark:text-gray-400">ไม่พบข้อมูลความเสี่ยง</p>
      <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">กรุณาเพิ่มการประเมินความเสี่ยง</p>
    </div>
    
    <!-- ตาราง Risk Matrix -->
    <div v-else class="overflow-x-auto bg-white dark:bg-gray-900 rounded-lg p-2">
      <table class="min-w-[340px] max-w-full mx-auto border-collapse">
        <!-- หัวตาราง (ผลกระทบ) -->
        <thead>
          <tr>
            <th class="p-2 text-left text-gray-500 dark:text-gray-400 text-xs">โอกาส / ผลกระทบ</th>
            <th 
              v-for="impact in impactLevels" 
              :key="impact.level" 
              class="p-2 text-center text-xs md:text-sm font-bold"
            >
              {{ impact.name }}<br><span class="text-xs font-normal">({{ impact.level }})</span>
            </th>
          </tr>
        </thead>
        
        <!-- เนื้อหาตาราง -->
        <tbody>
          <tr v-for="likelihood in likelihoodLevels" :key="likelihood.level">
            <!-- แถวด้านซ้าย (โอกาส) -->
            <th class="p-2 text-xs md:text-sm font-bold text-left text-gray-500 dark:text-gray-400">
              <span>{{ likelihood.name }}</span><br>
              <span class="text-xs font-normal">({{ likelihood.level }})</span>
            </th>
            
            <!-- ช่องแสดงระดับความเสี่ยง -->
            <td
              v-for="impact in impactLevels"
              :key="impact.level"
              class="w-16 h-16 md:w-20 md:h-20 text-center align-middle transition-all duration-200 cursor-pointer rounded-md border border-transparent hover:border-gray-300 dark:hover:border-gray-600 shadow-sm"
              :class="cellClass(getScore(likelihood.level, impact.level))"
              @click="showRisks(likelihood.level, impact.level)"
            >
              <!-- จำนวนความเสี่ยง -->
              <div class="font-bold text-lg">
                {{ riskCount(likelihood.level, impact.level) }}
              </div>
              
              <!-- คะแนนความเสี่ยง -->
              <div class="text-xs mt-1">
                {{ getScore(likelihood.level, impact.level) }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- คำอธิบายใต้ตาราง -->
      <div class="mt-3 text-xs text-center text-gray-500 dark:text-gray-400 flex justify-center items-center gap-2">
        <span>* แตะที่ช่องเพื่อดูรายละเอียดความเสี่ยงในแต่ละจุด</span>
      </div>
    </div>
  </div>
</template>

<!--
  - RiskHeatmap.vue: แสดงตาราง Risk Matrix สำหรับการวิเคราะห์ความเสี่ยง
  - การปรับปรุงที่สำคัญ:
    1. เปลี่ยนการแบ่งระดับความเสี่ยงเป็น 3 ระดับ:
      - สูง (9-16): สีแดง
      - กลาง (4-8): สีเหลือง
      - ต่ำ (1-3): สีเขียว
    2. เพิ่มส่วนแสดงคำอธิบายและสรุปจำนวนความเสี่ยงแต่ละระดับ
    3. เพิ่ม empty state สำหรับกรณีไม่มีข้อมูล
    4. ปรับปรุง UI ให้น่าใช้งานมากขึ้น
  - การใช้งาน: <RiskHeatmap :data="heatmapData" />
-->
