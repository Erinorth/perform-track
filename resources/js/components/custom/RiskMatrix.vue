<!-- 
  ไฟล์: RiskMatrix.vue
  คำอธิบาย: คอมโพเนนต์แสดงแผนภูมิความเสี่ยง (Risk Matrix)
  การใช้งาน: แสดงความสัมพันธ์ระหว่างโอกาสเกิดและผลกระทบ พร้อมไฮไลต์ค่าปัจจุบัน
  props:
    - likelihood: ค่าโอกาสเกิด (1-4)
    - impact: ค่าผลกระทบ (1-4)
-->

<script setup lang="ts">
import { computed, onMounted } from 'vue'

// นิยาม interface สำหรับ props
interface Props {
  likelihood: number // ค่าโอกาสเกิด (1-4)
  impact: number     // ค่าผลกระทบ (1-4)
}

// รับค่า props และกำหนดค่าเริ่มต้น
const props = defineProps<Props>()

/**
 * ตัวแปรคงที่สำหรับระดับความเสี่ยง
 * เพื่อให้สามารถปรับเปลี่ยนเกณฑ์ได้ในที่เดียว
 */
const RISK_LEVELS = {
  LOW: { min: 1, max: 3, color: 'bg-green-100', text: 'text-green-800', label: 'ความเสี่ยงต่ำ' },
  MEDIUM: { min: 4, max: 8, color: 'bg-yellow-100', text: 'text-yellow-800', label: 'ความเสี่ยงปานกลาง' },
  HIGH: { min: 9, max: 12, color: 'bg-orange-100', text: 'text-orange-800', label: 'ความเสี่ยงสูง' },
  EXTREME: { min: 13, max: 16, color: 'bg-red-100', text: 'text-red-800', label: 'ความเสี่ยงสูงมาก' }
}

/**
 * กำหนดระดับความเสี่ยงตามคะแนน
 * @param score คะแนนความเสี่ยง (ผลคูณของโอกาสเกิดและผลกระทบ)
 * @returns ข้อมูลระดับความเสี่ยง (สี, ข้อความ)
 */
const getRiskLevel = (score: number) => {
  if (score <= RISK_LEVELS.LOW.max) return RISK_LEVELS.LOW
  if (score <= RISK_LEVELS.MEDIUM.max) return RISK_LEVELS.MEDIUM
  if (score <= RISK_LEVELS.HIGH.max) return RISK_LEVELS.HIGH
  return RISK_LEVELS.EXTREME
}

/**
 * สร้างข้อมูลสำหรับแสดงแผนภูมิความเสี่ยง
 * - matrix เป็นตาราง 4x4 แสดงระดับความเสี่ยงต่างๆ
 * - แต่ละช่องมีข้อมูล: ระดับโอกาส, ระดับผลกระทบ, คะแนน, สีพื้นหลัง, สีข้อความ และสถานะการเลือก
 */
const matrix = computed(() => {
  const rows = []
  
  // สร้าง matrix 4x4 (เริ่มจากบนลงล่าง: ระดับผลกระทบจากสูงไปต่ำ)
  for (let i = 4; i >= 1; i--) { // i คือระดับผลกระทบ
    const row = []
    
    for (let j = 1; j <= 4; j++) { // j คือระดับโอกาสเกิด
      // คำนวณคะแนนความเสี่ยง
      const score = i * j
      
      // กำหนดระดับความเสี่ยงตามคะแนน
      const riskLevel = getRiskLevel(score)
      
      // ตรวจสอบว่าเป็นช่องที่เลือกอยู่หรือไม่
      const isSelected = props.likelihood === j && props.impact === i
      
      // เพิ่มข้อมูลช่องลงในแถว
      row.push({
        likelihood: j,
        impact: i,
        score,
        bgColor: riskLevel.color,
        textColor: riskLevel.text,
        isSelected,
        label: riskLevel.label
      })
    }
    rows.push(row)
  }
  
  return rows
})

// ตรวจสอบค่าที่ส่งเข้ามา
onMounted(() => {
  if (props.likelihood < 1 || props.likelihood > 4 || props.impact < 1 || props.impact > 4) {
    console.warn('คำเตือน: Risk Matrix ควรรับค่าโอกาสเกิดและผลกระทบระหว่าง 1-4 เท่านั้น')
  }
})
</script>

<template>
  <div class="overflow-x-auto">
    <div class="mb-2 text-sm text-gray-600 text-center font-medium">
      แผนภูมิระดับความเสี่ยง (Risk Matrix)
    </div>
    
    <table class="w-full border-collapse">
      <!-- หัวตาราง: แสดงระดับโอกาสเกิด (1-4) -->
      <thead>
        <tr>
          <th class="p-2 border text-center bg-gray-50">
            <div class="text-center text-xs font-medium">
              <div class="mb-1">ผลกระทบ ↓</div>
              <div>โอกาสเกิด →</div>
            </div>
          </th>
          <th 
            v-for="i in 4" 
            :key="`col-${i}`" 
            class="p-2 border text-center w-16 sm:w-20 bg-gray-50"
            :class="props.likelihood === i ? 'bg-blue-50' : ''"
          >
            <span class="font-medium">{{ i }}</span>
          </th>
        </tr>
      </thead>
      
      <!-- เนื้อหาตาราง: แผนภูมิความเสี่ยง 4x4 -->
      <tbody>
        <tr v-for="(row, rowIndex) in matrix" :key="`row-${rowIndex}`">
          <!-- แถวซ้ายสุดแสดงระดับผลกระทบ -->
          <th 
            class="p-2 border text-center w-16 sm:w-20 bg-gray-50"
            :class="props.impact === (4 - rowIndex) ? 'bg-blue-50' : ''"
          >
            <span class="font-medium">{{ 4 - rowIndex }}</span>
          </th>
          
          <!-- เซลล์แสดงคะแนนความเสี่ยง -->
          <td 
            v-for="cell in row" 
            :key="`cell-${cell.likelihood}-${cell.impact}`" 
            class="p-0 border text-center relative"
          >
            <div 
              class="flex flex-col items-center justify-center h-16 w-full transition-all duration-200"
              :class="[
                cell.bgColor, 
                cell.textColor,
                cell.isSelected ? 'ring-2 ring-blue-500 ring-inset shadow-inner' : 'hover:opacity-80'
              ]"
              :title="`โอกาสเกิด ${cell.likelihood} x ผลกระทบ ${cell.impact} = ${cell.score} (${cell.label})`"
            >
              <!-- แสดงคะแนนความเสี่ยง -->
              <span class="font-bold text-lg">{{ cell.score }}</span>
              
              <!-- แสดงระดับความเสี่ยง (เฉพาะบนจอขนาดกลาง-ใหญ่) -->
              <span class="text-xs hidden sm:block">{{ cell.label }}</span>
              
              <!-- สัญลักษณ์แสดงตำแหน่งปัจจุบัน -->
              <div 
                v-if="cell.isSelected"
                class="absolute -top-1.5 -right-1.5 bg-blue-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs shadow-sm"
                aria-label="ตำแหน่งปัจจุบัน"
              >
                ✓
              </div>
            </div>
          </td>
        </tr>
      </tbody>
      
      <!-- ส่วนท้าย: คำอธิบายระดับความเสี่ยง -->
      <tfoot>
        <tr>
          <td colspan="5" class="pt-3 pb-1 border-0">
            <p class="text-xs text-gray-500 mb-2">คำอธิบายระดับความเสี่ยง:</p>
            <div class="flex flex-wrap items-center justify-start gap-x-4 gap-y-2">
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded-sm" :class="RISK_LEVELS.LOW.color"></div>
                <span class="text-xs">{{ RISK_LEVELS.LOW.label }} ({{ RISK_LEVELS.LOW.min }}-{{ RISK_LEVELS.LOW.max }})</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded-sm" :class="RISK_LEVELS.MEDIUM.color"></div>
                <span class="text-xs">{{ RISK_LEVELS.MEDIUM.label }} ({{ RISK_LEVELS.MEDIUM.min }}-{{ RISK_LEVELS.MEDIUM.max }})</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded-sm" :class="RISK_LEVELS.HIGH.color"></div>
                <span class="text-xs">{{ RISK_LEVELS.HIGH.label }} ({{ RISK_LEVELS.HIGH.min }}-{{ RISK_LEVELS.HIGH.max }})</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded-sm" :class="RISK_LEVELS.EXTREME.color"></div>
                <span class="text-xs">{{ RISK_LEVELS.EXTREME.label }} ({{ RISK_LEVELS.EXTREME.min }}-{{ RISK_LEVELS.EXTREME.max }})</span>
              </div>
            </div>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</template>
