// ===============================
// Composable สำหรับจัดการ Combobox Logic
// ===============================

import { ref, computed, watch, nextTick, type Ref } from 'vue'
import type { ComboboxOption } from '@/types/combobox'

export function useCombobox<T extends ComboboxOption>(
  options: Ref<T[]>,
  props: {
    searchable?: boolean;
    filterFunction?: (option: T, searchTerm: string) => boolean;
    displayFunction?: (option: T) => string;
  }
) {
  // ===============================
  // Reactive State
  // ===============================
  const searchTerm = ref('')
  const isOpen = ref(false)
  const selectedOption = ref<T | null>(null)
  const comboboxAnchorRef = ref<HTMLElement>()
  const dropdownWidth = ref('auto')

  // ===============================
  // Computed Properties
  // ===============================
  
  // ฟังก์ชันกรองข้อมูลเริ่มต้น
  const defaultFilterFunction = (option: T, search: string): boolean => {
    if (!search.trim()) return true
    const searchLower = search.toLowerCase()
    return option.label.toLowerCase().includes(searchLower)
  }

  // ฟังก์ชันแสดงข้อความเริ่มต้น
  const defaultDisplayFunction = (option: T): string => option.label

  // รายการที่ผ่านการกรอง
  const filteredOptions = computed(() => {
    if (!props.searchable || !searchTerm.value.trim()) {
      return options.value
    }
    
    const filterFn = props.filterFunction || defaultFilterFunction
    return options.value.filter((option: T) => filterFn(option, searchTerm.value))
  })

  // ข้อความที่แสดงใน input
  const displayValue = computed(() => {
    if (!selectedOption.value) return ''
    const displayFn = props.displayFunction || defaultDisplayFunction
    return displayFn(selectedOption.value)
  })

  // ===============================
  // Methods
  // ===============================
  
  /**
   * เลือก option
   */
  const selectOption = (option: T) => {
    console.log('เลือก option:', option)
    selectedOption.value = option
    if (props.searchable) {
      searchTerm.value = displayValue.value
    }
    closeDropdown()
  }

  /**
   * ล้างการเลือก
   */
  const clearSelection = () => {
    console.log('ล้างการเลือก')
    selectedOption.value = null
    searchTerm.value = ''
  }

  /**
   * เปิด dropdown
   */
  const openDropdown = () => {
    isOpen.value = true
    if (props.searchable) {
      searchTerm.value = '' // รีเซ็ตการค้นหาเพื่อแสดงทั้งหมด
    }
    // คำนวณขนาด dropdown
    nextTick(() => {
      updateDropdownWidth()
    })
  }

  /**
   * ปิด dropdown
   */
  const closeDropdown = () => {
    isOpen.value = false
    if (props.searchable && selectedOption.value) {
      searchTerm.value = displayValue.value
    }
  }

  /**
   * สลับเปิด/ปิด dropdown
   */
  const toggleDropdown = () => {
    if (isOpen.value) {
      closeDropdown()
    } else {
      openDropdown()
    }
  }

  /**
   * คำนวณขนาด dropdown
   */
  const updateDropdownWidth = () => {
    if (comboboxAnchorRef.value) {
      const anchorWidth = comboboxAnchorRef.value.offsetWidth
      dropdownWidth.value = `${anchorWidth}px`
      console.log('อัพเดทขนาด dropdown:', dropdownWidth.value)
    }
  }

  /**
   * จัดการเมื่อมีการพิมพ์ค้นหา
   */
  const handleSearch = (value: string) => {
    searchTerm.value = value
  }

  // ===============================
  // Watchers
  // ===============================
  
  // เมื่อเปิด/ปิด dropdown
  watch(isOpen, (newValue: boolean) => {
    if (newValue) {
      console.log('เปิด dropdown')
    } else {
      console.log('ปิด dropdown')
    }
  })

  return {
    // State
    searchTerm,
    isOpen,
    selectedOption,
    comboboxAnchorRef,
    dropdownWidth,
    
    // Computed
    filteredOptions,
    displayValue,
    
    // Methods
    selectOption,
    clearSelection,
    openDropdown,
    closeDropdown,
    toggleDropdown,
    updateDropdownWidth,
    handleSearch
  }
}
