<!-- resources\js\pages\settings\Permission.vue -->
<script setup lang="ts">
import { onMounted } from 'vue';
import { toast } from 'vue-sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';
import { columns } from '@/pages/settings/permission/data-table/columns';
import DataTable from '@/pages/settings/permission/data-table/DataTable.vue';
import { usePermissionData } from '@/composables/usePermissionData';
import type { UserPermission } from '@/types/permission';
import { AlertCircle } from 'lucide-vue-next';

// รับค่า props จาก Inertia - ข้อมูลผู้ใช้และสิทธิ์จาก Backend
const props = defineProps<{
  users: UserPermission[]; // รายการผู้ใช้งานพร้อมข้อมูลสิทธิ์
}>();

// ใช้ composable สำหรับจัดการข้อมูลสิทธิ์ต่างๆ
const { data, updatePermission } = usePermissionData(props.users);

// บันทึก log เมื่อหน้าถูกโหลด เพื่อตรวจสอบข้อมูล
onMounted(() => {
  console.log('Permission page loaded with users data:', data.value);
});

// สร้างฟังก์ชันจัดการอัพเดตสิทธิ์พร้อมแสดงข้อความแจ้งเตือน
const handlePermissionUpdate = async (userId: number, permission: string, value: boolean) => {
  try {
    // เรียกใช้ฟังก์ชันอัพเดตสิทธิ์จาก composable
    await updatePermission(userId, permission, value);
    // แสดงข้อความแจ้งเตือนเมื่อสำเร็จ
    toast.success('อัพเดตสิทธิ์สำเร็จ');
    console.log(`Updated permission for user ${userId}: ${permission} = ${value}`);
  } catch (error) {
    // จัดการกรณีเกิดข้อผิดพลาด
    console.error('Failed to update permission:', error);
    toast.error('ไม่สามารถอัพเดตสิทธิ์ได้ กรุณาลองใหม่อีกครั้ง');
  }
};

// กำหนด breadcrumbs สำหรับการนำทาง
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Permission Settings',
    href: '/settings/permission',
  },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- กำหนดชื่อ title ของหน้า -->
    <Head title="Permission" />

    <!-- ใช้ Layout สำหรับหน้า Settings -->
    <SettingsLayout>
      <!-- ส่วนแสดงผลหลัก - ปรับให้รองรับการแสดงผลทุกขนาดหน้าจอ -->
      <div class="space-y-6 px-4 sm:px-6 lg:px-8">
        <!-- หัวข้อและคำอธิบายหน้า -->
        <HeadingSmall 
          title="การจัดการสิทธิ์ผู้ใช้งาน" 
          description="กำหนดสิทธิ์และบทบาทของผู้ใช้งานในระบบประเมินความเสี่ยง" 
        />
        
        <!-- แสดงข้อความคำแนะนำการใช้งาน -->
        <div class="rounded-md bg-blue-50 p-4 text-sm text-blue-800 flex items-start md:items-center gap-2">
          <AlertCircle class="h-5 w-5 flex-shrink-0" />
          <div>
            <p>การเปลี่ยนแปลงสิทธิ์จะมีผลทันทีที่บันทึก โปรดตรวจสอบให้แน่ใจก่อนดำเนินการ</p>
          </div>
        </div>
        
        <!-- ตารางแสดงและจัดการข้อมูลสิทธิ์ -->
        <DataTable 
          :columns="columns" 
          :data="data" 
          :meta="{ updateData: handlePermissionUpdate }" 
        />
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
