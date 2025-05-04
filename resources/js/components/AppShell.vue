<!-- ไฟล์: resources/js/components/AppShell.vue -->
<script setup lang="ts">
// นำเข้า SidebarProvider ซึ่งเป็น Context Provider สำหรับจัดการสถานะและพฤติกรรมของ Sidebar
import { SidebarProvider } from '@/components/ui/sidebar';
// นำเข้า usePage สำหรับเข้าถึงข้อมูลที่ส่งมาจาก Laravel ผ่าน Inertia
import { usePage } from '@inertiajs/vue3';
// นำเข้า type สำหรับกำหนดโครงสร้างข้อมูลที่แชร์ระหว่างหน้าต่างๆ ของแอปพลิเคชัน
import { SharedData } from '@/types';

// กำหนดโครงสร้าง Props ที่รับเข้ามาในคอมโพเนนต์
interface Props {
    variant?: 'header' | 'sidebar'; // กำหนดรูปแบบของ layout ว่าเป็นแบบมี header หรือมี sidebar
}

// รับ props จาก parent component
defineProps<Props>();

// ดึงค่า sidebarOpen จาก props ของ Inertia page เพื่อกำหนดสถานะเริ่มต้นของ sidebar
// ว่าควรแสดงหรือซ่อนอยู่ตอนโหลดหน้าเว็บ
const isOpen = usePage<SharedData>().props.sidebarOpen;
</script>

<template>
    <!-- 
      ถ้า variant เป็น 'header' จะใช้โครงสร้างแบบมี header บน-ล่าง
      โดยใช้ flex direction column (แนวตั้ง) และกำหนดความสูงขั้นต่ำเท่ากับความสูงหน้าจอ
    -->
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <!-- แสดงเนื้อหาที่ส่งเข้ามาผ่าน slot -->
        <slot />
    </div>
    <!--
      ถ้า variant ไม่ใช่ 'header' (เป็น 'sidebar') จะใช้ SidebarProvider
      ที่จัดการสถานะและพฤติกรรมของ sidebar โดยกำหนดค่าเริ่มต้นจาก isOpen
    -->
    <SidebarProvider v-else :default-open="isOpen">
        <!-- แสดงเนื้อหาที่ส่งเข้ามาผ่าน slot ซึ่งควรประกอบด้วย Sidebar และ Content -->
        <slot />
    </SidebarProvider>
</template>
