<!-- 
  ไฟล์: resources\js\features\organizational_risk\DataTableColumnHeader.vue
  Component สำหรับแสดงหัวคอลัมน์ของ DataTable
  รองรับการ sort, dropdown menu สำหรับ sort/ซ่อนคอลัมน์
  ใช้ icon จาก lucide-vue-next และ shadcn-vue UI
-->

<script setup lang="ts">
// นำเข้า type Column จาก @tanstack/vue-table สำหรับจัดการข้อมูลคอลัมน์
import type { Column } from '@tanstack/vue-table'
// นำเข้า icon ที่ใช้แสดงสถานะการ sort และซ่อนคอลัมน์ จาก lucide-vue-next
import { 
  ArrowDown as ArrowDownIcon,
  ArrowUp as ArrowUpIcon,
  ArrowUpDown as CaretSortIcon,
  EyeOff as EyeNoneIcon
} from 'lucide-vue-next'

// นำเข้า cn สำหรับรวม class และ Button, DropdownMenu จาก shadcn-vue
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

// กำหนด props ที่ต้องการรับ: column (ข้อมูลคอลัมน์) และ title (ชื่อหัวคอลัมน์)
interface DataTableColumnHeaderProps {
  column: Column<any>
  title: string
}
defineProps<DataTableColumnHeaderProps>()
</script>

<script lang="ts">
// ปิดการสืบทอด attribute อัตโนมัติ เพื่อควบคุม class เอง
export default {
  inheritAttrs: false,
}
</script>

<template>
  <!-- กรณีคอลัมน์นี้สามารถ sort ได้ -->
  <div v-if="column.getCanSort()" :class="cn('flex items-center space-x-2', $attrs.class ?? '')">
    <!-- DropdownMenu สำหรับจัดการ sort และซ่อนคอลัมน์ -->
    <DropdownMenu>
      <!-- ปุ่มกดเปิดเมนู ใช้ชื่อคอลัมน์และ icon แสดงสถานะการ sort -->
      <DropdownMenuTrigger as-child>
        <Button
          variant="ghost"
          size="sm"
          class="-ml-3 h-8 data-[state=open]:bg-accent"
        >
          <span>{{ title }}</span>
          <!-- แสดง icon ตามสถานะการ sort: มาก -> น้อย, น้อย -> มาก, หรือยังไม่ sort -->
          <ArrowDownIcon v-if="column.getIsSorted() === 'desc'" class="w-4 h-4 ml-2" />
          <ArrowUpIcon v-else-if=" column.getIsSorted() === 'asc'" class="w-4 h-4 ml-2" />
          <CaretSortIcon v-else class="w-4 h-4 ml-2" />
        </Button>
      </DropdownMenuTrigger>
      <!-- เนื้อหาเมนู dropdown -->
      <DropdownMenuContent align="start">
        <!-- เมนูเลือก sort น้อย -> มาก -->
        <DropdownMenuItem @click="column.toggleSorting(false)">
          <ArrowUpIcon class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" />
          Asc
        </DropdownMenuItem>
        <!-- เมนูเลือก sort มาก -> น้อย -->
        <DropdownMenuItem @click="column.toggleSorting(true)">
          <ArrowDownIcon class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" />
          Desc
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <!-- เมนูซ่อนคอลัมน์นี้ -->
        <DropdownMenuItem @click="column.toggleVisibility(false)">
          <EyeNoneIcon class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" />
          Hide
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </div>

  <!-- กรณีคอลัมน์นี้ไม่สามารถ sort ได้ แสดงชื่อหัวคอลัมน์ธรรมดา -->
  <div v-else :class="$attrs.class">
    {{ title }}
  </div>
</template>