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

// ไฟล์: resources/js/lib/utils.ts

/**
 * ฟอร์แมตวันที่เป็นรูปแบบ dd/mm/yyyy
 * @param date วันที่ที่ต้องการฟอร์แมต
 * @returns วันที่ในรูปแบบ dd/mm/yyyy
 */
export function formatDate(date: Date | string): string {
  if (!date) return '';
  
  const d = typeof date === 'string' ? new Date(date) : date;
  
  // ตรวจสอบว่าวันที่ถูกต้องหรือไม่
  if (isNaN(d.getTime())) return '';
  
  return d.toLocaleDateString('th-TH', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}

/**
 * รับวันที่และส่งคืนงวดครึ่งปี (เช่น "2025-1" สำหรับครึ่งปีแรกของ 2025)
 * @param date วันที่ที่ต้องการหางวด
 * @returns ออบเจ็กต์ที่มี value และ label ของงวด
 */
export function getHalfYearPeriod(date: Date) {
  const year = date.getFullYear();
  const month = date.getMonth() + 1;
  const half = month <= 6 ? 1 : 2;
  
  return {
    value: `${year}-${half}`,
    label: `${year} - ${half === 1 ? 'ครึ่งปีแรก' : 'ครึ่งปีหลัง'}`
  };
}

/**
 * แปลงปีและงวดการประเมินเป็นข้อความ
 * @param year ปีที่ประเมิน
 * @param period งวด (1=ครึ่งปีแรก, 2=ครึ่งปีหลัง)
 * @returns string
 */
export function formatAssessmentPeriod(year: number, period: number): string {
  return `${year} - ${period === 1 ? 'ครึ่งปีแรก' : 'ครึ่งปีหลัง'}`;
}

/**
 * ฟังก์ชันกรองตามงวดการประเมิน
 */
export function filterByPeriod(row: any, columnId: string, filterValues: string[]) {
  if (!filterValues?.length) return true
  
  const date = new Date(row.getValue(columnId))
  const period = getHalfYearPeriod(date).value
  
  return filterValues.includes(period)
}

/**
 * ฟังก์ชันกรองตามระดับความเสี่ยง
 */
export function filterByRiskScore(row: any, columnId: string, filterValues: string[]) {
  if (!filterValues?.length) return true
  
  const score = row.getValue(columnId)
  let level = ''
  
  if (score >= 9 && score <= 16) {
    level = 'high'
  } else if (score >= 4 && score <= 8) {
    level = 'medium'
  } else if (score >= 1 && score <= 3) {
    level = 'low'
  }
  
  return filterValues.includes(level)
}