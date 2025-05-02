/* 
 * ไฟล์: resources\js\lib\utils.ts
 * ไฟล์รวบรวมฟังก์ชันอรรถประโยชน์ (Utility Functions) สำหรับใช้ทั่วไปในโปรเจค
 * มีฟังก์ชันสำหรับจัดการ CSS classes, อัปเดตค่า reactive ใน Vue และสร้างช่วงปี
 */

// นำเข้าประเภทข้อมูลที่จำเป็นจากไลบรารีต่างๆ
import type { Updater } from '@tanstack/vue-table'  // ประเภทข้อมูล Updater จาก @tanstack/vue-table
import type { ClassValue } from 'clsx'              // ประเภทข้อมูล ClassValue จาก clsx

import type { Ref } from 'vue'                      // ประเภทข้อมูล Ref จาก Vue
import { clsx } from 'clsx'                         // ฟังก์ชัน clsx สำหรับจัดการ class names
import { twMerge } from 'tailwind-merge'            // ฟังก์ชัน twMerge สำหรับรวม tailwind classes

/**
 * ฟังก์ชันรวม class names ของ Tailwind CSS อย่างชาญฉลาด
 * ใช้ clsx เพื่อรวม class names และใช้ tailwind-merge เพื่อจัดการกับความขัดแย้งของ classes
 * 
 * @param inputs - รายการ class names ที่ต้องการรวม
 * @returns string ของ class names ที่รวมแล้ว
 * 
 * ตัวอย่าง: cn('text-red-500', 'bg-blue-500', isActive && 'font-bold')
 */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

/**
 * ฟังก์ชันอัปเดตค่าใน Ref ของ Vue
 * รองรับทั้งการส่งค่าโดยตรงหรือส่งฟังก์ชันที่รับค่าปัจจุบันและคืนค่าใหม่
 * ใช้สำหรับอัปเดตสถานะต่างๆ ใน @tanstack/vue-table
 * 
 * @param updaterOrValue - ค่าใหม่หรือฟังก์ชันที่รับค่าปัจจุบันและคืนค่าใหม่
 * @param ref - Vue Ref ที่ต้องการอัปเดต
 * 
 * ตัวอย่าง: valueUpdater(newValue, myRef) หรือ valueUpdater((prev) => prev + 1, counterRef)
 */
export function valueUpdater<T extends Updater<any>>(updaterOrValue: T, ref: Ref) {
  ref.value = typeof updaterOrValue === 'function'
    ? updaterOrValue(ref.value)
    : updaterOrValue
}

// เพิ่มฟังก์ชัน getYears
/**
 * สร้างอาร์เรย์ของปีตั้งแต่ startYear ถึง endYear
 * ใช้สำหรับสร้างตัวเลือกปีในดรอปดาวน์สำหรับฟอร์มกรอกข้อมูล
 * 
 * @param startYear ปีเริ่มต้น
 * @param endYear ปีสิ้นสุด
 * @returns อาร์เรย์ของปี
 * 
 * ตัวอย่าง: getYears(2020, 2025) จะคืนค่า [2020, 2021, 2022, 2023, 2024, 2025]
 */
export function getYears(startYear: number, endYear: number): number[] {
  const years: number[] = [];
  for (let year = startYear; year <= endYear; year++) {
    years.push(year);
  }
  return years;
}