<!-- ไฟล์: resources/js/layouts/AppLayout.vue -->
<script setup lang="ts">
// นำเข้า Layout หลักของแอปพลิเคชัน
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
// นำเข้า type สำหรับ breadcrumb
import type { BreadcrumbItemType } from '@/types';
// นำเข้า ConfirmDialog component
import ConfirmDialog from '@/components/ConfirmDialog.vue';
// นำเข้า composable สำหรับจัดการ confirm dialog
import { useConfirm } from '@/composables/useConfirm';
// นำเข้า Toaster
import { Toaster } from '@/components/ui/sonner'

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

    <!-- เพิ่ม Toaster component สำหรับการแสดง toast -->
    <Toaster />

    <!-- ConfirmDialog สำหรับการยืนยันการลบ -->
    <ConfirmDialog
        v-model:show="isOpen"
        :title="options?.title || ''"
        :message="options?.message || ''"
        :confirm-text="options?.confirmText"
        :cancel-text="options?.cancelText"
        @confirm="handleConfirm"
        @cancel="handleCancel"
    />
</template>
