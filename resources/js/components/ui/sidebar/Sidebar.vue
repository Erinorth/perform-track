<!-- ไฟล์: resources/js/components/ui/sidebar/Sidebar.vue -->
<script setup lang="ts">
// นำเข้า type สำหรับกำหนดโครงสร้าง Props ของ Sidebar
import type { SidebarProps } from '.'
// นำเข้าฟังก์ชัน cn สำหรับการจัดการ class names แบบมีเงื่อนไข
import { cn } from '@/lib/utils'
// นำเข้า Sheet components จาก shadcn-vue สำหรับแสดง sidebar บนมือถือ
import { Sheet, SheetContent } from '@/components/ui/sheet'
import SheetDescription from '@/components/ui/sheet/SheetDescription.vue'
import SheetHeader from '@/components/ui/sheet/SheetHeader.vue'
import SheetTitle from '@/components/ui/sheet/SheetTitle.vue'
// นำเข้าค่าคงที่สำหรับความกว้างของ sidebar บนมือถือและ composable สำหรับจัดการสถานะ sidebar
import { SIDEBAR_WIDTH_MOBILE, useSidebar } from './utils'

// กำหนดให้ไม่นำ attributes ที่ไม่ได้ระบุใน props ไปใส่ที่ root element โดยอัตโนมัติ
defineOptions({
  inheritAttrs: false,
})

// กำหนด props และค่าเริ่มต้น
const props = withDefaults(defineProps<SidebarProps>(), {
  side: 'left',            // ตำแหน่งของ sidebar (ซ้าย/ขวา)
  variant: 'sidebar',      // รูปแบบการแสดงผล (sidebar/floating/inset)
  collapsible: 'offcanvas', // รูปแบบการย่อ/ขยาย (none/offcanvas/icon)
})

// ใช้ composable useSidebar เพื่อจัดการสถานะและพฤติกรรมของ sidebar
const { isMobile, state, openMobile, setOpenMobile } = useSidebar()
</script>

<template>
  <!-- 
    กรณีที่ 1: ไม่สามารถย่อ/ขยายได้ (collapsible="none")
    แสดง sidebar แบบคงที่ ไม่สามารถย่อหรือซ่อนได้
  -->
  <div
    v-if="collapsible === 'none'"
    data-slot="sidebar"
    :class="cn('bg-sidebar text-sidebar-foreground flex h-full w-(--sidebar-width) flex-col', props.class)"
    v-bind="$attrs"
  >
    <slot />
  </div>

  <!-- 
    กรณีที่ 2: แสดงบนอุปกรณ์มือถือ
    ใช้ Sheet component จาก shadcn-vue ที่เลื่อนออกมาจากด้านข้างหน้าจอ
  -->
  <Sheet v-else-if="isMobile" :open="openMobile" v-bind="$attrs" @update:open="setOpenMobile">
    <SheetContent
      data-sidebar="sidebar"
      data-slot="sidebar"
      data-mobile="true"
      :side="side"
      class="bg-sidebar text-sidebar-foreground w-(--sidebar-width) p-0 [&>button]:hidden"
      :style="{
        '--sidebar-width': SIDEBAR_WIDTH_MOBILE, // กำหนดความกว้างของ sidebar สำหรับมือถือ
      }"
    >
      <!-- ส่วนหัวของ Sheet ที่มองไม่เห็น (สำหรับ screen reader) -->
      <SheetHeader class="sr-only">
        <SheetTitle>Sidebar</SheetTitle>
        <SheetDescription>Displays the mobile sidebar.</SheetDescription>
      </SheetHeader>
      <!-- ส่วนเนื้อหาของ sidebar -->
      <div class="flex h-full w-full flex-col">
        <slot />
      </div>
    </SheetContent>
  </Sheet>

  <!-- 
    กรณีที่ 3: แสดงบนเดสก์ท็อป (ไม่ใช่มือถือ)
    มีความสามารถในการย่อ/ขยายได้ตามการกำหนดใน prop collapsible
  -->
  <!-- สถานะปัจจุบัน: open หรือ collapsed -->
  <!-- รูปแบบการย่อ: offcanvas หรือ icon -->
  <!-- รูปแบบการแสดงผล: sidebar, floating, inset -->
  <!-- ตำแหน่ง: left หรือ right -->
  <div
    v-else
    class="group peer text-sidebar-foreground hidden md:block"
    data-slot="sidebar"
    :data-state="state"                                      
    :data-collapsible="state === 'collapsed' ? collapsible : ''" 
    :data-variant="variant"                                  
    :data-side="side"                                        
  >
    <!-- ส่วนนี้จัดการพื้นที่ว่างของ sidebar บนเดสก์ท็อป -->
    <div
      :class="cn(
        'relative w-(--sidebar-width) bg-transparent transition-[width] duration-200 ease-linear',
        'group-data-[collapsible=offcanvas]:w-0',  // ซ่อนทั้งหมดเมื่อเป็นแบบ offcanvas และถูกย่อ
        'group-data-[side=right]:rotate-180',      // พลิกด้านเมื่อแสดงทางขวา
        variant === 'floating' || variant === 'inset'
          ? 'group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)+(--spacing(4)))]'  // ปรับความกว้างสำหรับแบบ floating/inset เมื่อแสดงเฉพาะไอคอน
          : 'group-data-[collapsible=icon]:w-(--sidebar-width-icon)',  // ปรับความกว้างสำหรับแบบปกติเมื่อแสดงเฉพาะไอคอน
      )"
    />
    <!-- ส่วนที่แสดง sidebar จริง ๆ บนเดสก์ท็อป -->
    <div
      :class="cn(
        'fixed inset-y-0 z-10 hidden h-svh w-(--sidebar-width) transition-[left,right,width] duration-200 ease-linear md:flex',
        side === 'left'
          ? 'left-0 group-data-[collapsible=offcanvas]:left-[calc(var(--sidebar-width)*-1)]'  // กำหนดตำแหน่งและการเลื่อนซ่อนทางซ้าย
          : 'right-0 group-data-[collapsible=offcanvas]:right-[calc(var(--sidebar-width)*-1)]',  // กำหนดตำแหน่งและการเลื่อนซ่อนทางขวา
        // ปรับ padding และความกว้างสำหรับ variant แบบ floating และ inset
        variant === 'floating' || variant === 'inset'
          ? 'p-2 group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)+(--spacing(4))+2px)]'
          : 'group-data-[collapsible=icon]:w-(--sidebar-width-icon) group-data-[side=left]:border-r group-data-[side=right]:border-l',
        props.class,
      )"
      v-bind="$attrs"
    >
      <!-- ส่วนเนื้อหาภายใน sidebar -->
      <div
        data-sidebar="sidebar"
        class="bg-sidebar group-data-[variant=floating]:border-sidebar-border flex h-full w-full flex-col group-data-[variant=floating]:rounded-lg group-data-[variant=floating]:border group-data-[variant=floating]:shadow-sm"
      >
        <slot />
      </div>
    </div>
  </div>
</template>
