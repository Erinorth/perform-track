<!-- ไฟล์: resources/js/layouts/AppLayout.vue -->
<script setup lang="ts">
// นำเข้า Layout หลักที่มี Sidebar สำหรับใช้เป็นโครงสร้างหลักของแอปพลิเคชัน
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
// นำเข้า type สำหรับกำหนดโครงสร้างข้อมูล breadcrumb ในการนำทาง
import type { BreadcrumbItemType } from '@/types';
// นำเข้า Toaster component สำหรับแสดงการแจ้งเตือนแบบ toast (ข้อความที่แสดงชั่วคราว)
import { Toaster } from '@/components/ui/sonner'

// กำหนด interface สำหรับ props ที่รับเข้ามาในคอมโพเนนต์
interface Props {
    breadcrumbs?: BreadcrumbItemType[]; // รายการ breadcrumb สำหรับแสดงเส้นทางการนำทาง (optional)
}

// กำหนดค่าเริ่มต้นให้กับ props ที่รับเข้ามา กรณีที่ไม่มีการส่งค่ามา
withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [], // กำหนดค่าเริ่มต้นเป็นอาร์เรย์ว่าง
});
</script>

<template>
    <!-- ใช้ Layout หลักและส่ง breadcrumbs เพื่อแสดงเส้นทางการนำทางให้ผู้ใช้ -->
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- แสดงเนื้อหาหลักของหน้าที่ถูกส่งผ่าน slot จาก component ที่ใช้ Layout นี้ -->
        <slot />
    </AppLayout>

    <!-- เพิ่ม Toaster component สำหรับการแสดงข้อความแจ้งเตือนชั่วคราว (เช่น ข้อความสำเร็จ, ข้อผิดพลาด) -->
    <Toaster />
</template>
