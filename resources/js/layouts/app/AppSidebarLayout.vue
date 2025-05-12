<!-- ไฟล์: resources/js/layouts/app/AppSidebarLayout.vue -->
<script setup lang="ts">
// นำเข้า component AppContent สำหรับแสดงเนื้อหาหลักของแอปพลิเคชัน
import AppContent from '@/components/AppContent.vue';
// นำเข้า component AppShell ซึ่งเป็นโครงสร้างหลักที่ห่อหุ้มทั้งแอปพลิเคชัน
import AppShell from '@/components/AppShell.vue';
// นำเข้า component AppSidebar สำหรับแสดงเมนูด้านข้างซึ่งใช้ในการนำทางหลัก
import AppSidebar from '@/components/AppSidebar.vue';
// นำเข้า component AppSidebarHeader สำหรับแสดงส่วนหัวของพื้นที่เนื้อหาหลัก รวมถึง breadcrumb
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
// นำเข้า type สำหรับกำหนดโครงสร้างข้อมูล breadcrumb ใช้แสดงเส้นทางการนำทาง
import type { BreadcrumbItemType } from '@/types';

// กำหนดโครงสร้าง Props ที่รับเข้ามาในคอมโพเนนต์
interface Props {
    breadcrumbs?: BreadcrumbItemType[]; // รายการ breadcrumb สำหรับแสดงเส้นทางการนำทาง (optional)
}

// กำหนดค่าเริ่มต้นให้กับ props โดยถ้าไม่มีการส่ง breadcrumbs มาจะใช้อาร์เรย์ว่าง
withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <!-- 
      ใช้ AppShell เป็นโครงสร้างหลักของแอปพลิเคชัน
      กำหนด variant เป็น "sidebar" เพื่อแสดงรูปแบบที่มี sidebar ด้านข้าง
      ซึ่งจะปรับเปลี่ยนตามขนาดหน้าจอตามหลัก Responsive Design
    -->
    <AppShell variant="sidebar">
        <!-- 
          แสดง AppSidebar ซึ่งเป็นเมนูด้านข้างสำหรับนำทางไปยังหน้าต่างๆ 
          เช่น Dashboard, Organizational Risk, Division Risk ฯลฯ
        -->
        <AppSidebar />
        
        <!-- 
          AppContent คือส่วนที่แสดงเนื้อหาหลักของแอปพลิเคชัน
          กำหนด variant เป็น "sidebar" เพื่อให้สอดคล้องกับ layout ที่มี sidebar
        -->
        <AppContent variant="sidebar">
            <!-- 
              แสดง AppSidebarHeader ซึ่งเป็นส่วนหัวของพื้นที่เนื้อหา
              ส่ง breadcrumbs ไปแสดงเพื่อให้ผู้ใช้ทราบว่าอยู่ที่ส่วนใดของแอปพลิเคชัน
            -->
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            
            <!-- 
              ใช้ slot เพื่อรับเนื้อหาที่จะแสดงในพื้นที่หลักจาก component ที่เรียกใช้ Layout นี้
              เช่น หน้า Dashboard, Risk Assessment หรือหน้ารายงานต่างๆ
            -->
            <slot />
        </AppContent>
    </AppShell>
</template>
