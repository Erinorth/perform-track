/* 
  ไฟล์: resources/js/features/organizational_risk/organizational_risk.ts
  ไฟล์นี้กำหนด TypeScript interfaces สำหรับข้อมูลความเสี่ยงระดับองค์กรและระดับสายงาน
  ใช้ร่วมกับระบบ Risk Assessment ที่พัฒนาด้วย Laravel 12 + Vue 3
*/

// อินเตอร์เฟซสำหรับข้อมูลความเสี่ยงระดับสายงาน
export interface DepartmentRisk {
  id: number                       // รหัสความเสี่ยงระดับสายงาน (Primary Key)
  risk_name: string                // ชื่อความเสี่ยงระดับสายงาน
  description?: string             // รายละเอียดความเสี่ยง (อาจเป็นค่าว่างได้)
  year: number                     // ปีของความเสี่ยง (ปีงบประมาณหรือปีการประเมิน)
  organizational_risk_id?: number  // รหัสอ้างอิงไปยังความเสี่ยงระดับองค์กร (Foreign Key, อาจเป็นค่าว่างได้)
  created_at: string               // วันเวลาที่สร้าง (timestamp)
  updated_at: string               // วันเวลาที่แก้ไขล่าสุด (timestamp)
}

// อินเตอร์เฟซสำหรับข้อมูลความเสี่ยงระดับองค์กร
export interface OrganizationalRisk {
  id: number                       // รหัสความเสี่ยงระดับองค์กร (Primary Key)
  risk_name: string                // ชื่อความเสี่ยงระดับองค์กร
  description: string              // รายละเอียดความเสี่ยง
  year: number                     // ปีของความเสี่ยง (ปีงบประมาณหรือปีการประเมิน)
  created_at: string               // วันเวลาที่สร้าง (timestamp)
  updated_at: string               // วันเวลาที่แก้ไขล่าสุด (timestamp)
  department_risks?: DepartmentRisk[] // ความสัมพันธ์แบบ one-to-many กับความเสี่ยงระดับสายงาน (อาจมีหรือไม่มีก็ได้)
  active?: boolean                 // สถานะการใช้งาน (active/inactive)
}
