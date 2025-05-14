<script setup lang="ts">
import { computed } from 'vue'

// รับค่า props
interface Props {
  likelihood: number
  impact: number
}

const props = defineProps<Props>()

// สร้างข้อมูลสำหรับแสดงแผนภูมิความเสี่ยง
const matrix = computed(() => {
  const rows = []
  
  // สร้าง matrix 4x4
  for (let i = 4; i >= 1; i--) {
    const row = []
    for (let j = 1; j <= 4; j++) {
      const score = i * j
      let bgColor = 'bg-white'
      let textColor = 'text-gray-800'
      
      // กำหนดสีตามระดับความเสี่ยง
      if (score <= 3) {
        bgColor = 'bg-green-100'
        textColor = 'text-green-800'
      } else if (score <= 8) {
        bgColor = 'bg-yellow-100'
        textColor = 'text-yellow-800'
      } else if (score <= 12) {
        bgColor = 'bg-orange-100'
        textColor = 'text-orange-800'
      } else {
        bgColor = 'bg-red-100'
        textColor = 'text-red-800'
      }
      
      // เพิ่มสถานะเลือกสำหรับช่องปัจจุบัน
      const isSelected = props.likelihood === j && props.impact === i
      
      row.push({
        likelihood: j,
        impact: i,
        score,
        bgColor,
        textColor,
        isSelected
      })
    }
    rows.push(row)
  }
  
  return rows
})
</script>

<template>
  <div class="overflow-x-auto">
    <table class="w-full border-collapse">
      <caption class="text-sm text-gray-600 text-center font-medium pb-2">
        แผนภูมิระดับความเสี่ยง (Risk Matrix)
      </caption>
      <!-- หัวตาราง -->
      <thead>
        <tr>
          <th class="p-2 border text-center align-middle">
            <div class="text-center text-xs font-medium">
              <div class="mb-1">ผลกระทบ</div>
              <div>โอกาสเกิด</div>
            </div>
          </th>
          <th v-for="i in 4" :key="`col-${i}`" class="p-2 border text-center w-20 align-middle">
            <span class="font-medium">{{ i }}</span>
          </th>
        </tr>
      </thead>
      
      <!-- เนื้อหาตาราง -->
      <tbody>
        <tr v-for="(row, rowIndex) in matrix" :key="`row-${rowIndex}`">
          <th class="p-2 border text-center w-20 align-middle">
            <span class="font-medium">{{ 4 - rowIndex }}</span>
          </th>
          <td 
            v-for="cell in row" 
            :key="`cell-${cell.likelihood}-${cell.impact}`" 
            class="p-0 border text-center relative"
          >
            <div 
              class="flex items-center justify-center p-4 h-16 w-full"
              :class="[
                cell.bgColor, 
                cell.textColor,
                cell.isSelected ? 'ring-2 ring-blue-500 ring-inset' : ''
              ]"
            >
              <span class="font-bold text-lg">{{ cell.score }}</span>
              
              <!-- แสดงตำแหน่งปัจจุบัน -->
              <div 
                v-if="cell.isSelected"
                class="absolute -top-2 -right-2 bg-blue-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs"
              >
                ✓
              </div>
            </div>
          </td>
        </tr>
      </tbody>
      
      <!-- คำอธิบาย -->
      <tfoot>
        <tr>
          <td colspan="5" class="pt-4 pb-2 border-0">
            <div class="flex items-center justify-start gap-4 flex-wrap">
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-green-100"></div>
                <span class="text-xs">ความเสี่ยงต่ำ (1-3)</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-yellow-100"></div>
                <span class="text-xs">ความเสี่ยงปานกลาง (4-8)</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-orange-100"></div>
                <span class="text-xs">ความเสี่ยงสูง (9-12)</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-red-100"></div>
                <span class="text-xs">ความเสี่ยงสูงมาก (16)</span>
              </div>
            </div>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</template>
