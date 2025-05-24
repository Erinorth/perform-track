<!-- 
  ไฟล์: resources/js/components/forms/OrganizationalRiskCombobox.vue
  คำอธิบาย: Combobox เฉพาะสำหรับเลือกความเสี่ยงระดับองค์กร
  ทำหน้าที่: Wrapper component สำหรับใช้ในการเลือกความเสี่ยงระดับองค์กร
  แก้ไข: จัดการ null value ใน event handler
-->

<script setup lang="ts">
import { computed } from 'vue'
import ComboboxGeneric from '@/components/ui/combobox/ComboboxGeneric.vue'
import type { OrganizationalRisk } from '@/types/types'
import type { ComboboxOption } from '@/types/combobox'

// ===============================
// Types
// ===============================
interface OrganizationalRiskOption extends ComboboxOption {
  id: number;
  label: string;
  risk_name: string;
  description: string;
}

// ===============================
// Props & Emits
// ===============================
const props = defineProps<{
  organizationalRisks: OrganizationalRisk[];
  modelValue?: OrganizationalRisk | null;
  placeholder?: string;
  disabled?: boolean;
  required?: boolean;
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: OrganizationalRisk | null): void;
  (e: 'select', value: OrganizationalRisk): void;
  (e: 'clear'): void;
}>()

// ===============================
// Computed Properties
// ===============================

// แปลง OrganizationalRisk เป็น ComboboxOption
const options = computed((): OrganizationalRiskOption[] => {
  return props.organizationalRisks.map(risk => ({
    id: risk.id,
    label: risk.risk_name,
    risk_name: risk.risk_name,
    description: risk.description
  }))
})

// แปลง modelValue เป็น ComboboxOption
const selectedOption = computed((): OrganizationalRiskOption | null => {
  if (!props.modelValue) return null
  return {
    id: props.modelValue.id,
    label: props.modelValue.risk_name,
    risk_name: props.modelValue.risk_name,
    description: props.modelValue.description
  }
})

// ===============================
// Methods
// ===============================

/**
 * ฟังก์ชันกรองข้อมูล
 */
const filterFunction = (option: OrganizationalRiskOption, searchTerm: string): boolean => {
  const search = searchTerm.toLowerCase()
  return option.risk_name.toLowerCase().includes(search) ||
         option.description.toLowerCase().includes(search)
}

/**
 * ฟังก์ชันแสดงข้อความ
 */
const displayFunction = (option: OrganizationalRiskOption): string => {
  return option.risk_name
}

/**
 * จัดการการเลือก (รองรับ null value)
 */
const handleUpdateModelValue = (option: OrganizationalRiskOption | null) => {
  if (!option) {
    // กรณีที่ค่าเป็น null (การล้างค่า)
    emit('update:modelValue', null)
    return
  }

  // กรณีที่มีการเลือก option
  const originalRisk = props.organizationalRisks.find(risk => risk.id === option.id)
  if (originalRisk) {
    console.log('เลือกความเสี่ยงระดับองค์กร:', originalRisk)
    emit('update:modelValue', originalRisk)
    emit('select', originalRisk)
  }
}

/**
 * จัดการการล้างค่า
 */
const handleClear = () => {
  console.log('ล้างความเสี่ยงระดับองค์กร')
  emit('update:modelValue', null)
  emit('clear')
}
</script>

<template>
  <ComboboxGeneric
    :options="options"
    :model-value="selectedOption"
    :placeholder="placeholder || 'เลือกความเสี่ยงระดับองค์กร...'"
    :disabled="disabled"
    :required="required"
    :filter-function="filterFunction"
    :display-function="displayFunction"
    searchable
    clearable
    empty-message="ไม่พบความเสี่ยงที่ค้นหา"
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
