/**
 * ไฟล์: resources\js\lib\logger.ts
 * โมดูลสำหรับบันทึกเหตุการณ์ (logging) ในระบบ
 * ใช้สำหรับติดตามการทำงานของผู้ใช้และการดีบั๊กระบบ
 */

import { toast } from 'vue-sonner'

/**
 * บันทึกการกระทำทั่วไปในระบบ
 * @param message ข้อความที่ต้องการบันทึก
 * @param level ระดับความสำคัญของเหตุการณ์ (info, warning, error)
 */
export function logAction(message: string, level: 'info' | 'warning' | 'error' = 'info'): void {
  const timestamp = new Date().toISOString()
  console.log(`[${timestamp}] [${level.toUpperCase()}] ${message}`)
  
  // แสดง toast เฉพาะเมื่อเป็น warning หรือ error
  if (level === 'warning' || level === 'error') {
    toast[level === 'error' ? 'error' : 'warning'](message)
  }
}

/**
 * บันทึกการกระทำในตาราง (DataTable)
 * @param action ประเภทการกระทำ (view, edit, delete, expand)
 * @param entityType ประเภทของข้อมูล (organizational_risk, department_risk, etc.)
 * @param entityId ID ของข้อมูล
 */
export function logTableAction(
  action: 'view' | 'edit' | 'delete' | 'expand', 
  entityType: string, 
  entityId: number
): void {
  logAction(`${action.toUpperCase()} ${entityType} with ID: ${entityId}`)
}
