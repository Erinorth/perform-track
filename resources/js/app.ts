/* resources\js\app.ts */
// นำเข้าไฟล์ CSS หลักสำหรับสไตล์ของแอปพลิเคชัน (Tailwind CSS)
import '../css/app.css';

// นำเข้า library สำหรับสร้าง SPA ด้วย Inertia.js ที่เชื่อมต่อ Laravel กับ Vue 3
import { createInertiaApp } from '@inertiajs/vue3';
// ฟังก์ชันแก้ไขเส้นทางของ component จาก Laravel Vite Plugin
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
// ประเภทข้อมูลสำหรับ Vue Component
import type { DefineComponent } from 'vue';
// ฟังก์ชันหลักจาก Vue สำหรับสร้างแอปพลิเคชันและ render function
import { createApp, h } from 'vue';
// Ziggy สำหรับใช้ Laravel named routes ใน JavaScript/TypeScript
import { ZiggyVue } from 'ziggy-js';
// นำเข้าฟังก์ชันกำหนดธีม (light/dark mode) จาก composable
import { initializeTheme } from './composables/useAppearance';

// กำหนดชื่อแอปพลิเคชันจากตัวแปรสภาพแวดล้อมใน .env หรือใช้ค่าเริ่มต้น 'Laravel'
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// ตั้งค่าและเริ่มต้น Inertia.js application
createInertiaApp({
    // กำหนดชื่อเพจที่แสดงบน browser tab (รูปแบบ: "หน้าปัจจุบัน - ชื่อแอป")
    title: (title) => `${title} - ${appName}`,
    
    // กำหนดวิธีการโหลด Vue component
    // แปลงชื่อเพจจาก controller (เช่น 'organizational_risk/OrganizationalRisk') 
    // เป็นเส้นทางไฟล์ Vue component
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    
    // ฟังก์ชันที่ทำงานหลังจาก Inertia โหลดและพร้อมใช้งาน
    setup({ el, App, props, plugin }) {
        // สร้าง Vue application instance
        createApp({ 
            // กำหนด render function สำหรับแสดงผล Inertia App component
            render: () => h(App, props) 
        })
            .use(plugin)    // ลงทะเบียน Inertia plugin
            .use(ZiggyVue)  // ลงทะเบียน Ziggy สำหรับเรียกใช้ route() ใน Vue
            .mount(el);     // ติดตั้ง Vue app ลงใน DOM element ที่ Inertia.js กำหนด
    },
    
    // กำหนดค่าสำหรับ progress bar ที่แสดงระหว่างการนำทางระหว่างหน้า
    progress: {
        color: '#4B5563',  // สีของ progress bar (สีเทาเข้ม)
    },
});

// เรียกใช้ฟังก์ชันกำหนดธีม (light/dark mode) ตั้งแต่โหลดหน้าเว็บ
// ทำให้สามารถบันทึกและเรียกคืนการตั้งค่าธีมของผู้ใช้ได้
initializeTheme();
