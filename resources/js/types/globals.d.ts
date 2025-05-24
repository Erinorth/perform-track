import type { route as routeFn } from 'ziggy-js';

declare global {
    const route: typeof routeFn;
}

// ไฟล์: resources/js/types/global.d.ts
// คำอธิบาย: Global type declarations สำหรับ TypeScript
// ทำหน้าที่: กำหนด types สำหรับ global functions และ variables
// หลักการ: แก้ปัญหา TypeScript errors และให้ IntelliSense ที่ดี

import ziggyRouteFunction from "ziggy-js";

// กำหนด route function เป็น global สำหรับใช้ใน TypeScript files ทั่วไป
declare global {
  const route: typeof ziggyRouteFunction;
  
  // กำหนด Ziggy configuration object
  interface Window {
    Ziggy?: {
      url: string;
      port: number | null;
      defaults: Record<string, any>;
      routes: Record<string, any>;
    };
  }
}

// กำหนด route function สำหรับใช้ใน Vue templates และ components
declare module "@vue/runtime-core" {
  interface ComponentCustomProperties {
    route: typeof ziggyRouteFunction;
    $route: typeof ziggyRouteFunction; // สำหรับกรณีที่มี alias
  }
}

// กำหนด types สำหรับ Inertia.js
declare module "@inertiajs/vue3" {
  interface PageProps {
    ziggy?: {
      url: string;
      port: number | null;
      defaults: Record<string, any>;
      routes: Record<string, any>;
      route?: string; // current route name
    };
    [key: string]: any;
  }
}

export {};

