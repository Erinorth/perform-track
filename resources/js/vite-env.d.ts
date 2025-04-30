// ไฟล์: resources/js/vite-env.d.ts
// ไฟล์นี้ใช้กำหนด TypeScript type declarations สำหรับ Vite
// ช่วยให้ IDE แสดง intellisense และตรวจสอบประเภทข้อมูลในโปรเจคได้อย่างถูกต้อง
// โดยเฉพาะการทำงานกับตัวแปรสภาพแวดล้อม (environment variables) และฟีเจอร์ของ Vite

/// <reference types="vite/client" />
// อ้างอิง type definitions จาก Vite เพื่อรองรับคุณสมบัติของ Vite โดยตรง
// เช่น HMR (Hot Module Replacement), env variables, และอื่นๆ

// กำหนดโครงสร้างของตัวแปรสภาพแวดล้อม (environment variables)
// ที่เข้าถึงได้ผ่าน import.meta.env ในโค้ด
interface ImportMetaEnv {
    readonly VITE_APP_NAME: string;  // ชื่อแอปพลิเคชันที่กำหนดใน .env file
    [key: string]: string | boolean | undefined;  // รองรับตัวแปรสภาพแวดล้อมอื่นๆ ที่ขึ้นต้นด้วย VITE_
}

// กำหนดโครงสร้างของ import.meta ซึ่งเป็น object พิเศษที่ Vite ใช้สำหรับฟีเจอร์พิเศษต่างๆ
interface ImportMeta {
    readonly env: ImportMetaEnv;  // เข้าถึงตัวแปรสภาพแวดล้อมผ่าน import.meta.env
    readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;  // ฟังก์ชันสำหรับการโหลดไฟล์หลายไฟล์พร้อมกัน โดยใช้ pattern matching
}
