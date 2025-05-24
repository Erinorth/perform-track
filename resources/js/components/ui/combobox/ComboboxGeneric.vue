<!-- 
  ไฟล์: resources/js/components/ui/combobox/ComboboxGeneric.vue
  คำอธิบาย: Generic Combobox Component ที่ใช้ได้กับข้อมูลหลายประเภท
  ทำหน้าที่: แสดง dropdown ที่สามารถค้นหาและเลือกได้
  หลักการ: ใช้ Radix Vue Combobox เป็นพื้นฐาน รองรับ TypeScript Generic
  ใช้ร่วมกับ: useCombobox composable, ComboboxOption type
  แก้ไข: จัดการ Type compatibility กับ Radix Vue
-->

<script setup lang="ts" generic="T extends ComboboxOption">
// ===============================
// Imports
// ===============================
import { computed, watch, nextTick, ref } from 'vue'
import { 
  ComboboxRoot, ComboboxAnchor, ComboboxInput, ComboboxTrigger, 
  ComboboxContent, ComboboxViewport, ComboboxEmpty, 
  ComboboxItem, ComboboxItemIndicator 
} from 'radix-vue'
import { Button } from '@/components/ui/button'
import { ChevronDownIcon, XIcon, CheckIcon, Loader2Icon } from 'lucide-vue-next'
import type { ComboboxOption, ComboboxProps, ComboboxEmits } from '@/types/combobox'
import { cn } from '@/lib/utils'

// ===============================
// Props & Emits
// ===============================
const props = withDefaults(defineProps<ComboboxProps<T>>(), {
  placeholder: 'เลือกรายการ...',
  searchable: true,
  searchPlaceholder: 'ค้นหา...',
  emptyMessage: 'ไม่พบรายการ',
  loading: false,
  disabled: false,
  clearable: true,
  required: false,
  size: 'md',
  variant: 'default'
})

const emit = defineEmits<ComboboxEmits<T>>()

// ===============================
// Internal State Management
// ===============================

// ใช้ internal state เพื่อหลีกเลี่ยงปัญหา Generic Type กับ Radix Vue
const internalSearchTerm = ref('')
const isOpen = ref(false)
const comboboxAnchorRef = ref<HTMLElement>()

// ===============================
// Computed Properties
// ===============================

// แปลง Generic Type T เป็น string สำหรับ Radix Vue
const internalValue = computed({
  get: (): string => {
    if (!props.modelValue) return ''
    // ใช้ JSON.stringify เพื่อแปลง object เป็น string
    return JSON.stringify(props.modelValue)
  },
  set: (value: string) => {
    if (!value) {
      emit('update:modelValue', null)
      return
    }
    
    try {
      // หา option ที่ตรงกับ value
      const parsedValue = JSON.parse(value)
      const matchedOption = props.options.find(option => 
        JSON.stringify(option) === value
      )
      
      if (matchedOption) {
        emit('update:modelValue', matchedOption)
        emit('select', matchedOption)
      }
    } catch (error) {
      console.error('Error parsing combobox value:', error)
    }
  }
})

// รายการที่ผ่านการกรอง
const filteredOptions = computed(() => {
  if (!props.searchable || !internalSearchTerm.value.trim()) {
    return props.options
  }
  
  const searchTerm = internalSearchTerm.value.toLowerCase()
  
  if (props.filterFunction) {
    return props.options.filter(option => 
      props.filterFunction!(option, internalSearchTerm.value)
    )
  }
  
  // ฟังก์ชันกรองเริ่มต้น
  return props.options.filter(option => 
    option.label.toLowerCase().includes(searchTerm)
  )
})

// คลาส CSS สำหรับขนาด
const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'h-8 text-sm px-2'
    case 'lg':
      return 'h-12 text-lg px-4'
    default:
      return 'h-10 text-base px-3'
  }
})

// คลาส CSS สำหรับรูปแบบ
const variantClasses = computed(() => {
  switch (props.variant) {
    case 'outline':
      return 'border-2 border-primary'
    case 'ghost':
      return 'border-transparent bg-transparent hover:bg-accent'
    default:
      return 'border border-input bg-background'
  }
})

// คลาส CSS รวม
const comboboxClasses = computed(() => 
  cn(
    'relative flex items-center justify-between rounded-md w-full',
    'focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
    sizeClasses.value,
    variantClasses.value,
    props.disabled && 'opacity-50 cursor-not-allowed',
    'transition-colors duration-200'
  )
)

// ความกว้างของ dropdown
const dropdownWidth = computed(() => {
  if (comboboxAnchorRef.value) {
    return `${comboboxAnchorRef.value.offsetWidth}px`
  }
  return 'auto'
})

// ===============================
// Watchers
// ===============================

// ซิงค์ค่า modelValue
watch(() => props.modelValue, (newValue) => {
  console.log('modelValue เปลี่ยน:', newValue)
}, { immediate: true })

// ส่ง event เมื่อ searchTerm เปลี่ยน
watch(internalSearchTerm, (newValue) => {
  emit('search', newValue)
})

// ส่ง event เมื่อเปิด/ปิด dropdown
watch(isOpen, (newValue) => {
  if (newValue) {
    emit('open')
  } else {
    emit('close')
  }
})

// ===============================
// Methods
// ===============================

/**
 * ฟังก์ชันแสดงค่าใน input สำหรับ Radix Vue
 */
const getDisplayValue = (value: string): string => {
  if (!value) return ''
  
  try {
    const parsedValue = JSON.parse(value)
    const matchedOption = props.options.find(option => 
      JSON.stringify(option) === value
    )
    
    if (matchedOption) {
      if (props.displayFunction) {
        return props.displayFunction(matchedOption)
      }
      return matchedOption.label
    }
  } catch (error) {
    console.error('Error parsing display value:', error)
  }
  
  return ''
}

/**
 * จัดการการเลือก option
 */
const handleSelect = (optionValue: string) => {
  try {
    const parsedValue = JSON.parse(optionValue)
    const matchedOption = props.options.find(option => 
      JSON.stringify(option) === optionValue
    )
    
    if (matchedOption) {
      console.log('เลือก option:', matchedOption)
      emit('update:modelValue', matchedOption)
      emit('select', matchedOption)
      
      // รีเซ็ต search term หลังเลือก
      if (props.searchable) {
        const displayText = props.displayFunction 
          ? props.displayFunction(matchedOption)
          : matchedOption.label
        internalSearchTerm.value = displayText
      }
    }
  } catch (error) {
    console.error('Error selecting option:', error)
  }
}

/**
 * จัดการการล้างค่า
 */
const handleClear = (event: Event) => {
  event.stopPropagation()
  console.log('ล้างการเลือก')
  emit('update:modelValue', null)
  emit('clear')
  internalSearchTerm.value = ''
}

/**
 * จัดการการเปิด/ปิด dropdown
 */
const handleOpenChange = (open: boolean) => {
  if (props.disabled) return
  
  isOpen.value = open
  
  if (open) {
    console.log('เปิด dropdown')
    if (props.searchable) {
      internalSearchTerm.value = '' // รีเซ็ตการค้นหาเพื่อแสดงทั้งหมด
    }
    
    nextTick(() => {
      // คำนวณขนาด dropdown
      console.log('คำนวณขนาด dropdown')
    })
  } else {
    console.log('ปิด dropdown')
    if (props.searchable && props.modelValue) {
      // คืนค่า display text เมื่อปิด dropdown
      const displayText = props.displayFunction 
        ? props.displayFunction(props.modelValue)
        : props.modelValue.label
      internalSearchTerm.value = displayText
    }
  }
}

/**
 * จัดการการพิมพ์ค้นหา
 */
const handleSearchTermUpdate = (value: string) => {
  internalSearchTerm.value = value
}

/**
 * สร้าง string value สำหรับ option
 */
const getOptionValue = (option: T): string => {
  return JSON.stringify(option)
}

/**
 * แสดงข้อความของ option
 */
const getOptionLabel = (option: T): string => {
  if (props.displayFunction) {
    return props.displayFunction(option)
  }
  return option.label
}
</script>

<template>
  <div class="w-full">
    <!-- Combobox หลัก -->
    <ComboboxRoot
      :model-value="internalValue"
      @update:model-value="(value: string) => internalValue = value"
      :display-value="getDisplayValue"
      :search-term="internalSearchTerm"
      @update:search-term="handleSearchTermUpdate"
      @update:open="handleOpenChange"
      :disabled="props.disabled"
      class="w-full relative"
    >
      <!-- Anchor และ Input -->
      <ComboboxAnchor
        ref="comboboxAnchorRef"
        :class="comboboxClasses"
      >
        <!-- Input field -->
        <ComboboxInput
          :class="cn(
            '!bg-transparent outline-none flex-1 h-full',
            'selection:bg-primary/20 placeholder:text-muted-foreground',
            !props.searchable && 'cursor-pointer'
          )"
          :placeholder="props.modelValue ? getOptionLabel(props.modelValue) : props.placeholder"
          :readonly="!props.searchable"
          autocomplete="off"
          aria-autocomplete="list"
        />
        
        <!-- ปุ่มและไอคอน -->
        <div class="flex items-center gap-1 flex-shrink-0">
          <!-- Loading indicator -->
          <div v-if="props.loading" class="mr-1">
            <Loader2Icon class="h-4 w-4 animate-spin text-muted-foreground" />
          </div>
          
          <!-- ปุ่มล้างค่า -->
          <Button
            v-if="props.clearable && props.modelValue && !props.disabled"
            type="button"
            variant="ghost"
            size="icon"
            class="h-6 w-6 text-muted-foreground hover:text-foreground"
            @click="handleClear"
            :aria-label="'ล้างการเลือก'"
          >
            <XIcon class="h-3 w-3" />
          </Button>
          
          <!-- ปุ่ม trigger -->
          <ComboboxTrigger :disabled="props.disabled">
            <ChevronDownIcon 
              :class="cn(
                'h-4 w-4 text-muted-foreground transition-transform duration-200',
                isOpen && 'rotate-180'
              )" 
            />
          </ComboboxTrigger>
        </div>
      </ComboboxAnchor>
      
      <!-- Dropdown content -->
      <ComboboxContent 
        class="absolute z-50 mt-2 bg-popover text-popover-foreground overflow-hidden rounded-md shadow-lg border"
        :style="{ width: dropdownWidth }"
      >
        <ComboboxViewport class="p-1 max-h-60 overflow-y-auto">
          <!-- ข้อความเมื่อไม่พบข้อมูล -->
          <ComboboxEmpty class="text-muted-foreground text-sm text-center py-3">
            {{ props.emptyMessage }}
          </ComboboxEmpty>
          
          <!-- รายการ options -->
          <template v-for="option in filteredOptions" :key="option.id">
            <ComboboxItem
              :value="getOptionValue(option)"
              @select="(event: any) => handleSelect(event)"
              :disabled="option.disabled"
              :class="cn(
                'text-sm leading-none rounded flex items-center min-h-10 px-3 pr-8',
                'relative select-none cursor-pointer transition-colors',
                'data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground',
                'data-[disabled]:opacity-50 data-[disabled]:cursor-not-allowed'
              )"
            >
              <!-- ข้อความของ option -->
              <span class="truncate flex-1">
                {{ getOptionLabel(option) }}
              </span>
              
              <!-- ไอคอนแสดงการเลือก -->
              <ComboboxItemIndicator class="absolute right-2 flex items-center">
                <CheckIcon class="h-4 w-4 text-primary" />
              </ComboboxItemIndicator>
            </ComboboxItem>
          </template>
        </ComboboxViewport>
      </ComboboxContent>
    </ComboboxRoot>
    
    <!-- ข้อความ error (ถ้ามี) -->
    <slot name="error" />
    
    <!-- ข้อความช่วยเหลือ (ถ้ามี) -->
    <slot name="help" />
  </div>
</template>

