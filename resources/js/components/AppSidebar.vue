<!-- ไฟล์: resources/js/components/AppSidebar.vue -->
<script setup lang="ts">
// นำเข้า component สำหรับแสดงส่วนท้ายของ sidebar (เมนูล่าง)
import NavFooter from '@/components/NavFooter.vue';
// นำเข้า component สำหรับแสดงเมนูหลัก
import NavMain from '@/components/NavMain.vue';
// นำเข้า component สำหรับแสดงข้อมูลผู้ใช้งานและเมนูที่เกี่ยวข้อง
import NavUser from '@/components/NavUser.vue';
// นำเข้า components จาก shadcn-vue สำหรับสร้าง sidebar
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
// นำเข้า type สำหรับกำหนดโครงสร้างข้อมูลรายการเมนู
import { type NavItem } from '@/types';
// นำเข้า Link จาก Inertia.js สำหรับการนำทางโดยไม่ต้องโหลดหน้าใหม่
import { Link } from '@inertiajs/vue3';
// นำเข้าไอคอนจาก lucide-vue-next สำหรับประกอบรายการเมนู
import { 
    LayoutGrid, 
    CircleAlert, 
    List,
    Info
} from 'lucide-vue-next';
// นำเข้า AppLogo สำหรับแสดงโลโก้ของแอปพลิเคชัน
import AppLogo from './AppLogo.vue';

// กำหนดรายการเมนูหลักของระบบประเมินความเสี่ยง
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid, // ไอคอนตารางเลย์เอาท์เหมาะสมสำหรับหน้า Dashboard
    },
    {
        title: 'Organizational Risk',
        href: '/organizational-risks',
        icon: CircleAlert, // ไอคอนเตือนแบบวงกลมเหมาะกับความเสี่ยงระดับองค์กร
    },
    {
        title: 'Division Risk',
        href: '/division-risks',
        icon: Info, // ไอคอนข้อมูลเหมาะสำหรับความเสี่ยงระดับแผนก
    },
    {
        title: 'Risk Assessment',
        href: '/risk-assessments',
        icon: List, // ไอคอนรายการเหมาะสำหรับการประเมินความเสี่ยง
    },
    {
        title: 'Risk Control',
        href: '/risk-controls',
        icon: List, // ไอคอนรายการเหมาะสำหรับการจัดการทะเบียนความเสี่ยง
    },
];

// กำหนดรายการเมนูส่วนล่างของ sidebar (ปัจจุบันถูก comment ไว้ไม่ได้ใช้งาน)
const footerNavItems: NavItem[] = [
    /* {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
    }, */
];
</script>

<template>
    <!-- 
      Sidebar หลักของแอปพลิเคชัน
      - collapsible="icon" ทำให้สามารถย่อ/ขยาย sidebar ได้ โดยแสดงเฉพาะไอคอนเมื่อย่อ
      - variant="inset" กำหนดรูปแบบการแสดงผลให้มีขอบเขตที่ชัดเจน (มีขอบและพื้นที่ว่างโดยรอบ)
    -->
    <Sidebar collapsible="icon" variant="inset">
        <!-- ส่วนหัวของ sidebar แสดงโลโก้ของแอปพลิเคชัน -->
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <!-- 
                      ปุ่มสำหรับกลับไปยังหน้า Dashboard เมื่อคลิกที่โลโก้
                      - size="lg" กำหนดขนาดปุ่มให้ใหญ่
                      - as-child ทำให้ Link ทำงานภายในปุ่ม
                    -->
                    <SidebarMenuButton size="lg" as-child>
                        <!-- ใช้ Inertia Link เพื่อนำทางโดยไม่ต้องโหลดหน้าใหม่ -->
                        <Link :href="route('dashboard')">
                            <!-- แสดงโลโก้ของแอปพลิเคชัน -->
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <!-- ส่วนเนื้อหาหลักของ sidebar แสดงเมนูหลัก -->
        <SidebarContent>
            <!-- ใช้ NavMain component เพื่อแสดงรายการเมนูหลัก (Dashboard, Organizational Risk, ฯลฯ) -->
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <!-- ส่วนท้ายของ sidebar -->
        <SidebarFooter>
            <!-- แสดงเมนูเพิ่มเติมที่ส่วนล่าง (ปัจจุบันไม่มี) -->
            <NavFooter :items="footerNavItems" />
            <!-- แสดงข้อมูลผู้ใช้งานและเมนูที่เกี่ยวข้อง (เช่น ข้อมูลโปรไฟล์, ออกจากระบบ) -->
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <!-- รองรับการแสดงเนื้อหาเพิ่มเติมที่ส่งผ่าน slot -->
    <slot />
</template>
