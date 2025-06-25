<script setup lang="ts">
import { computed } from 'vue'
import type { Table } from '@tanstack/vue-table'
import type { Payment } from '@/types/payment'
import { X, Filter, Group, Ungroup } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import StatusFilter from './StatusFilter.vue'
import AmountRangeFilter from './AmountRangeFilter.vue'
import EmailFilter from './EmailFilter.vue'

// Props definition
interface Props {
  table: Table<Payment>
  activeFiltersCount: number
  activeGroupingCount: number
  grouping: string[]
}

const props = defineProps<Props>()

// Events
const emit = defineEmits<{
  clearFilters: []
}>()

// ตรวจสอบว่ามี columns หรือไม่
const statusColumn = computed(() => props.table.getColumn('status'))
const amountColumn = computed(() => props.table.getColumn('amount'))
const emailColumn = computed(() => props.table.getColumn('email'))

// แสดงรายการ grouping ที่ active
const activeGroupings = computed(() => {
  return props.grouping.map(groupId => {
    const column = props.table.getColumn(groupId)
    return {
      id: groupId,
      name: column?.columnDef.header || groupId
    }
  })
})

const clearGrouping = () => {
  try {
    props.table.resetGrouping()
  } catch (error) {
    console.warn('Clear grouping failed:', error)
  }
}
</script>

<template>
  <Card v-if="statusColumn || amountColumn || emailColumn || activeFiltersCount > 0 || activeGroupingCount > 0">
    <CardContent class="p-4">
      <div class="flex flex-col gap-4">
        <!-- Filter Header -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Filter class="w-4 h-4 text-muted-foreground" />
            <h3 class="font-medium text-sm">ตัวกรองและการจัดกลุ่ม</h3>
            <Badge 
              v-if="activeFiltersCount > 0" 
              variant="secondary" 
              class="text-xs"
            >
              {{ activeFiltersCount }} กรอง
            </Badge>
            <Badge 
              v-if="activeGroupingCount > 0" 
              variant="outline" 
              class="text-xs"
            >
              {{ activeGroupingCount }} กลุ่ม
            </Badge>
          </div>
          
          <div class="flex items-center gap-2">
            <!-- Clear Grouping Button -->
            <Button
              v-if="activeGroupingCount > 0"
              variant="ghost"
              size="sm"
              @click="clearGrouping"
              class="h-8 text-xs"
            >
              <Ungroup class="w-3 h-3 mr-1" />
              ยกเลิกการจัดกลุ่ม
            </Button>
            
            <!-- Clear Filters Button -->
            <Button
              v-if="activeFiltersCount > 0"
              variant="ghost"
              size="sm"
              @click="emit('clearFilters')"
              class="h-8 text-xs"
            >
              <X class="w-3 h-3 mr-1" />
              ล้างตัวกรอง
            </Button>
          </div>
        </div>

        <!-- Active Groupings Display -->
        <div v-if="activeGroupings.length > 0" class="space-y-2">
          <div class="flex items-center gap-2 text-xs text-muted-foreground">
            <Group class="w-3 h-3" />
            <span>จัดกลุ่มตาม:</span>
          </div>
          <div class="flex flex-wrap gap-2">
            <Badge
              v-for="group in activeGroupings"
              :key="group.id"
              variant="outline"
              class="text-xs"
            >
              {{ group.name }}
            </Badge>
          </div>
          <Separator />
        </div>

        <!-- Filters Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <!-- Status Filter -->
          <div v-if="statusColumn">
            <label class="text-xs font-medium text-muted-foreground mb-2 block">
              กรองตามสถานะ
            </label>
            <StatusFilter :column="statusColumn" />
          </div>

          <!-- Amount Range Filter -->
          <div v-if="amountColumn">
            <label class="text-xs font-medium text-muted-foreground mb-2 block">
              กรองตามจำนวนเงิน
            </label>
            <AmountRangeFilter :column="amountColumn" />
          </div>

          <!-- Email Autocomplete Filter -->
          <div v-if="emailColumn">
            <label class="text-xs font-medium text-muted-foreground mb-2 block">
              กรองตามอีเมล
            </label>
            <EmailFilter :column="emailColumn" />
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
