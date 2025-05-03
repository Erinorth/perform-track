/**
 * @file resources\js\composables\index.d.ts
 * ไฟล์ประกาศประเภทข้อมูล (declaration file) สำหรับ composable function useMediaQuery
 * เป็น composable ที่ใช้ตรวจสอบ media query เพื่อช่วยในการทำ Responsive Design
 * โดยเฉพาะสำหรับการปรับการแสดงผลให้เหมาะสมกับอุปกรณ์มือถือ
 * 
 * @module @/composables/useMediaQuery
 */
declare module '@/composables/useMediaQuery' {
  /**
   * ฟังก์ชัน useMediaQuery สำหรับตรวจสอบสถานะของ CSS Media Query
   * 
   * @param query - CSS Media Query ที่ต้องการตรวจสอบ เช่น '(max-width: 640px)' สำหรับมือถือ
   * @returns Ref<boolean> - ค่า reactive ที่จะเป็น true เมื่อ query ตรงตามเงื่อนไข
   *                       - จะอัพเดทโดยอัตโนมัติเมื่อขนาดหน้าจอเปลี่ยนแปลง
   * 
   * @example
   * // นำไปใช้ในคอมโพเนนต์ Vue:
   * import { useMediaQuery } from '@/composables/useMediaQuery'
   * 
   * const isMobile = useMediaQuery('(max-width: 640px)')
   * const isTablet = useMediaQuery('(min-width: 641px) and (max-width: 1024px)')
   * 
   * // ใช้กับ v-if เพื่อแสดงคอมโพเนนต์แตกต่างกันตามขนาดหน้าจอ
   * <div v-if="isMobile">แสดงบนมือถือ</div>
   * <div v-else>แสดงบนหน้าจอขนาดใหญ่</div>
   */
  export function useMediaQuery(query: string): import('vue').Ref<boolean>;
}
