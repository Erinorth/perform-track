<!-- 
  ไฟล์: resources/js/components/forms/DivisionRiskCombobox.vue
  คำอธิบาย: Combobox เฉพาะสำหรับเลือกความเสี่ยงระดับหน่วยงาน
  ทำหน้าที่: Wrapper component สำหรับใช้ในการเลือกความเสี่ยงหน่วยงาน
  รองรับ: การแสดงชื่อความเสี่ยงองค์กรที่เกี่ยวข้อง, การค้นหาหลายเงื่อนไข
-->

<script setup lang="ts">
import { computed } from 'vue'
import ComboboxGeneric from '@/components/ui/combobox/ComboboxGeneric.vue'
import type { DivisionRisk, OrganizationalRisk } from '@/types/types'
import type { ComboboxOption } from '@/types/combobox'

// ===============================
// Types
// ===============================
interface DivisionRiskOption extends ComboboxOption {
  id: number;
  label: string;
  risk_name: string;
  description: string;
  organizational_risk_id?: number | null;
  organizational_risk_name?: string | null;
}

// ===============================
// Props & Emits
// ===============================
const props = defineProps<{
  divisionRisks: DivisionRisk[];
  modelValue?: DivisionRisk | null;
  placeholder?: string;
  disabled?: boolean;
  required?: boolean;
  showOrganizationalRisk?: boolean; // แสดงชื่อความเสี่ยงองค์กรด้วย
  filterByOrganizationalRisk?: number | null; // กรองตามความเสี่ยงองค์กร
  loading?: boolean;
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: DivisionRisk | null): void;
  (e: 'select', value: DivisionRisk): void;
  (e: 'clear'): void;
}>()

// ===============================
// Computed Properties
// ===============================

// กรองความเสี่ยงหน่วยงานตามเงื่อนไข
const filteredDivisionRisks = computed(() => {
  let risks = props.divisionRisks

  // กรองตามความเสี่ยงองค์กร (ถ้าระบุ)
  if (props.filterByOrganizationalRisk !== null && props.filterByOrganizationalRisk !== undefined) {
    risks = risks.filter(risk => risk.organizational_risk_id === props.filterByOrganizationalRisk)
  }

  return risks
})

// แปลง DivisionRisk เป็น ComboboxOption
const options = computed((): DivisionRiskOption[] => {
  return filteredDivisionRisks.value.map(risk => ({
    id: risk.id,
    label: risk.risk_name,
    risk_name: risk.risk_name,
    description: risk.description,
    organizational_risk_id: risk.organizational_risk_id,
    organizational_risk_name: risk.organizational_risk?.risk_name || null
  }))
})

// แปลง modelValue เป็น ComboboxOption
const selectedOption = computed((): DivisionRiskOption | null => {
  if (!props.modelValue) return null
  return {
    id: props.modelValue.id,
    label: props.modelValue.risk_name,
    risk_name: props.modelValue.risk_name,
    description: props.modelValue.description,
    organizational_risk_id: props.modelValue.organizational_risk_id,
    organizational_risk_name: props.modelValue.organizational_risk?.risk_name || null
  }
})

// ===============================
// Methods
// ===============================

/**
 * ฟังก์ชันกรองข้อมูล
 */
const filterFunction = (option: DivisionRiskOption, searchTerm: string): boolean => {
  const search = searchTerm.toLowerCase()
  const searchableFields = [
    option.risk_name.toLowerCase(),
    option.description.toLowerCase()
  ]

  // เพิ่มการค้นหาในชื่อความเสี่ยงองค์กร (ถ้ามี)
  if (option.organizational_risk_name) {
    searchableFields.push(option.organizational_risk_name.toLowerCase())
  }

  return searchableFields.some(field => field.includes(search))
}

/**
 * ฟังก์ชันแสดงข้อความ
 */
const displayFunction = (option: DivisionRiskOption): string => {
  if (props.showOrganizationalRisk && option.organizational_risk_name) {
    return `${option.risk_name} (${option.organizational_risk_name})`
  }
  return option.risk_name
}

/**
 * จัดการการเลือก (รองรับ null value)
 */
const handleUpdateModelValue = (option: DivisionRiskOption | null) => {
  if (!option) {
    // กรณีที่ค่าเป็น null (การล้างค่า)
    emit('update:modelValue', null)
    return
  }

  // กรณีที่มีการเลือก option
  const originalRisk = props.divisionRisks.find(risk => risk.id === option.id)
  if (originalRisk) {
    console.log('เลือกความเสี่ยงระดับหน่วยงาน:', {
      id: originalRisk.id,
      risk_name: originalRisk.risk_name,
      organizational_risk: originalRisk.organizational_risk?.risk_name || 'ไม่ระบุ'
    })
    emit('update:modelValue', originalRisk)
    emit('select', originalRisk)
  }
}

/**
 * จัดการการล้างค่า
 */
const handleClear = () => {
  console.log('ล้างความเสี่ยงระดับหน่วยงาน')
  emit('update:modelValue', null)
  emit('clear')
}

/**
 * สร้างข้อความ placeholder แบบไดนามิก
 */
const dynamicPlaceholder = computed(() => {
  if (props.placeholder) return props.placeholder
  
  if (props.filterByOrganizationalRisk !== null && props.filterByOrganizationalRisk !== undefined) {
    return 'เลือกความเสี่ยงหน่วยงานที่เกี่ยวข้อง...'
  }
  
  return 'เลือกความเสี่ยงระดับหน่วยงาน...'
})

/**
 * สร้างข้อความ empty แบบไดนามิก
 */
const dynamicEmptyMessage = computed(() => {
  if (props.filterByOrganizationalRisk !== null && props.filterByOrganizationalRisk !== undefined) {
    return 'ไม่พบความเสี่ยงหน่วยงานที่เกี่ยวข้องกับความเสี่ยงองค์กรนี้'
  }
  
  return 'ไม่พบความเสี่ยงหน่วยงานที่ค้นหา'
})
</script>

<template>
  <ComboboxGeneric
    :options="options"
    :model-value="selectedOption"
    :placeholder="dynamicPlaceholder"
    :disabled="disabled"
    :required="required"
    :loading="loading"
    :filter-function="filterFunction"
    :display-function="displayFunction"
    searchable
    clearable
    :empty-message="dynamicEmptyMessage"
    @update:model-value="handleUpdateModelValue"
    @clear="handleClear"
  >
    <!-- Slot สำหรับ error message -->
    <template #error>
      <slot name="error" />
    </template>
    
    <!-- Slot สำหรับ help text -->
    <template #help>
      <slot name="help" />
    </template>
  </ComboboxGeneric>
</template>
