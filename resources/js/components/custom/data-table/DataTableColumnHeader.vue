<script setup lang="ts">
import { h } from 'vue'
import type { Column } from '@tanstack/vue-table'
import type { Payment } from '@/types/payment'
import { cn } from '@/lib/utils'
import { 
  ArrowDown, 
  ArrowUp, 
  ArrowUpDown, 
  EyeOff,
  Group,
  Ungroup
} from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import ColumnResizeHandle from './ColumnResizeHandle.vue'

// Define props
interface Props {
  column: Column<Payment, any>
  title: string
  class?: string
  header?: any
  table?: any
}

const props = defineProps<Props>()
</script>

<template>
  <div 
    v-if="column.getCanSort()" 
    :class="cn('flex items-center space-x-2 relative pr-2', props.class ?? '')"
  >
    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button
          variant="ghost"
          size="sm"
          class="-ml-3 h-8 data-[state=open]:bg-accent"
        >
          <span>{{ title }}</span>
          <ArrowDown 
            v-if="column.getIsSorted() === 'desc'" 
            class="w-4 h-4 ml-2" 
          />
          <ArrowUp 
            v-else-if="column.getIsSorted() === 'asc'" 
            class="w-4 h-4 ml-2" 
          />
          <ArrowUpDown 
            v-else 
            class="w-4 h-4 ml-2" 
          />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start">
        <DropdownMenuItem @click="column.toggleSorting(false)">
          <ArrowUp class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" />
          เรียงจากน้อยไปมาก
        </DropdownMenuItem>
        <DropdownMenuItem @click="column.toggleSorting(true)">
          <ArrowDown class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" />
          เรียงจากมากไปน้อย
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        
        <!-- Grouping options หากคอลัมน์สามารถจัดกลุ่มได้ -->
        <template v-if="column.getCanGroup && column.getCanGroup()">
          <DropdownMenuItem @click="column.toggleGrouping()">
            <Ungroup 
              v-if="column.getIsGrouped()" 
              class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" 
            />
            <Group 
              v-else 
              class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" 
            />
            {{ column.getIsGrouped() ? 'ยกเลิกการจัดกลุ่ม' : 'จัดกลุ่ม' }}
          </DropdownMenuItem>
          <DropdownMenuSeparator />
        </template>
        
        <DropdownMenuItem @click="column.toggleVisibility(false)">
          <EyeOff class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" />
          ซ่อนคอลัมน์
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
    
    <ColumnResizeHandle 
      v-if="header && table" 
      :header="header" 
      :table="table" 
    />
  </div>
  
  <div v-else :class="cn('relative pr-2', props.class)">
    {{ title }}
    <ColumnResizeHandle 
      v-if="header && table" 
      :header="header" 
      :table="table" 
    />
  </div>
</template>
