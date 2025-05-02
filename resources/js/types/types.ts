/*
  ไฟล์: resources/js/types/types.ts
  ไฟล์นี้กำหนด TypeScript interfaces สำหรับระบบประเมินความเสี่ยงทั้งหมด
  รวมข้อมูลความเสี่ยงระดับองค์กร ระดับสายงาน และการประเมินความเสี่ยง
  ใช้ร่วมกับระบบ Risk Assessment ที่พัฒนาด้วย Laravel 12 + Vue 3
*/

// อินเตอร์เฟซสำหรับข้อมูลความเสี่ยงระดับองค์กร
export interface OrganizationalRisk {
    id: number                       // รหัสความเสี่ยงระดับองค์กร (Primary Key)
    risk_name: string                // ชื่อความเสี่ยงระดับองค์กร
    description: string              // รายละเอียดความเสี่ยง
    year: number                     // ปีของความเสี่ยง (ปีงบประมาณหรือปีการประเมิน)
    created_at: string               // วันเวลาที่สร้าง (timestamp)
    updated_at: string               // วันเวลาที่แก้ไขล่าสุด (timestamp)
    department_risks?: DepartmentRisk[] // ความสัมพันธ์แบบ one-to-many กับความเสี่ยงระดับสายงาน (อาจมีหรือไม่มีก็ได้)
  }
  
  // อินเตอร์เฟซสำหรับข้อมูลความเสี่ยงระดับสายงาน
  export interface DepartmentRisk {
    id: number                        // รหัสความเสี่ยงระดับสายงาน (Primary Key)
    risk_name: string                 // ชื่อความเสี่ยงระดับสายงาน
    description: string               // รายละเอียดความเสี่ยง
    year: number                      // ปีของความเสี่ยง (ปีงบประมาณหรือปีการประเมิน)
    organizational_risk_id: number | null // รหัสความเสี่ยงระดับองค์กรที่เกี่ยวข้อง (Foreign Key, อาจเป็น null)
    created_at: string                // วันเวลาที่สร้าง (timestamp)
    updated_at: string                // วันเวลาที่แก้ไขล่าสุด (timestamp)
    deleted_at: string | null         // วันเวลาที่ลบ (soft delete)
    organizational_risk?: OrganizationalRisk // ความสัมพันธ์แบบ many-to-one กับความเสี่ยงระดับองค์กร (อาจมีหรือไม่มีก็ได้)
    risk_assessments?: RiskAssessment[] // ความสัมพันธ์แบบ one-to-many กับการประเมินความเสี่ยง
    impact_criteria?: ImpactCriteria[] // ความสัมพันธ์แบบ one-to-many กับเกณฑ์ผลกระทบ
    likelihood_criteria?: LikelihoodCriteria[] // ความสัมพันธ์แบบ one-to-many กับเกณฑ์โอกาสเกิด
  }
  
  // อินเตอร์เฟซสำหรับการประเมินความเสี่ยง
  export interface RiskAssessment {
    id: number                       // รหัสการประเมินความเสี่ยง (Primary Key)
    assessment_date: string          // วันที่ประเมิน (date)
    likelihood_level: number         // ระดับโอกาสเกิด (1-4)
    impact_level: number             // ระดับผลกระทบ (1-4)
    risk_score: number               // คะแนนความเสี่ยง (likelihood_level * impact_level)
    department_risk_id: number       // รหัสความเสี่ยงระดับสายงานที่เกี่ยวข้อง (Foreign Key)
    notes: string | null             // บันทึกเพิ่มเติม (อาจเป็น null)
    created_at: string               // วันเวลาที่สร้าง (timestamp)
    updated_at: string               // วันเวลาที่แก้ไขล่าสุด (timestamp)
    department_risk?: DepartmentRisk // ความสัมพันธ์แบบ many-to-one กับความเสี่ยงระดับสายงาน
  }
  
  // อินเตอร์เฟซสำหรับเกณฑ์ผลกระทบ
  export interface ImpactCriteria {
    id: number                       // รหัสเกณฑ์ผลกระทบ (Primary Key)
    level: number                    // ระดับผลกระทบ (1-4)
    name: string                     // ชื่อระดับผลกระทบ
    description: string | null       // รายละเอียดเกณฑ์ผลกระทบ (อาจเป็น null)
    department_risk_id: number       // รหัสความเสี่ยงระดับสายงานที่เกี่ยวข้อง (Foreign Key)
    created_at: string               // วันเวลาที่สร้าง (timestamp)
    updated_at: string               // วันเวลาที่แก้ไขล่าสุด (timestamp)
    department_risk?: DepartmentRisk // ความสัมพันธ์แบบ many-to-one กับความเสี่ยงระดับสายงาน
  }
  
  // อินเตอร์เฟซสำหรับเกณฑ์โอกาสเกิด
  export interface LikelihoodCriteria {
    id: number                       // รหัสเกณฑ์โอกาสเกิด (Primary Key)
    level: number                    // ระดับโอกาสเกิด (1-4)
    name: string                     // ชื่อระดับโอกาสเกิด
    description: string | null       // รายละเอียดเกณฑ์โอกาสเกิด (อาจเป็น null)
    department_risk_id: number       // รหัสความเสี่ยงระดับสายงานที่เกี่ยวข้อง (Foreign Key)
    created_at: string               // วันเวลาที่สร้าง (timestamp)
    updated_at: string               // วันเวลาที่แก้ไขล่าสุด (timestamp)
    department_risk?: DepartmentRisk // ความสัมพันธ์แบบ many-to-one กับความเสี่ยงระดับสายงาน
  }
  