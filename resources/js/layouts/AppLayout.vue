<!-- 
  ไฟล์: resources\js\layouts\AppLayout.vue
  Layout หลักสำหรับแอปพลิเคชัน
  เพิ่ม ConfirmDialog สำหรับใช้งานทั่วทั้งแอปพลิเคชัน
-->
<script setup lang="ts">
// นำเข้า Layout หลักของแอปพลิเคชัน
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
// นำเข้า type สำหรับ breadcrumb
import type { BreadcrumbItemType } from '@/types';
// นำเข้า ConfirmDialog component - แก้ไขเส้นทางให้ถูกต้อง
import ConfirmDialog from '@/components/ConfirmDialog.vue';
// นำเข้า composable สำหรับจัดการ confirm dialog
import { useConfirm } from '@/composables/useConfirm';

// กำหนด props ที่รับจาก parent component
interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

// กำหนดค่าเริ่มต้นให้ props
withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// ใช้ composable useConfirm เพื่อจัดการกับการแสดง dialog
const { isOpen, options, handleConfirm, handleCancel } = useConfirm();
</script>

<template>
    <!-- ใช้ Layout หลักและส่ง breadcrumbs ไปยัง component -->
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- แสดง content ที่ส่งผ่าน slot -->
        <slot />
    </AppLayout>

    <!-- เพิ่ม ConfirmDialog ไว้ที่ส่วนล่างของ Layout -->
    <!-- แสดงเฉพาะเมื่อมีการเรียกใช้งานจาก useConfirm -->
    <ConfirmDialog
        v-if="isOpen && options"
        v-model:show="isOpen"
        :title="options.title"
        :message="options.message"
        :confirm-text="options.confirmText"
        :cancel-text="options.cancelText"
        @confirm="handleConfirm"
        @cancel="handleCancel"
    />
</template>
