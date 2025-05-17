/*
  ไฟล์: resources/js/types/types.ts
  ไฟล์นี้กำหนด TypeScript interfaces สำหรับระบบประเมินความเสี่ยงทั้งหมด
  รวมข้อมูลความเสี่ยงระดับองค์กร ระดับฝ่าย และการประเมินความเสี่ยง
  ใช้ร่วมกับระบบ Risk Assessment ที่พัฒนาด้วย Laravel 12 + Vue 3
*/

// อินเตอร์เฟซสำหรับข้อมูลความเสี่ยงระดับองค์กร
export interface OrganizationalRisk {
    id: number                       // รหัสความเสี่ยงระดับองค์กร (Primary Key)
    risk_name: string                // ชื่อความเสี่ยงระดับองค์กร
    description: string              // รายละเอียดความเสี่ยง
    created_at: string               // วันเวลาที่สร้าง (timestamp)
    updated_at: string               // วันเวลาที่แก้ไขล่าสุด (timestamp)
    attachments?: OrganizationalRiskAttachment[];
    division_risks?: DivisionRisk[] // ความสัมพันธ์แบบ one-to-many กับความเสี่ยงระดับฝ่าย (อาจมีหรือไม่มีก็ได้)
  }
  
  // อินเตอร์เฟซสำหรับข้อมูลความเสี่ยงระดับฝ่าย
  export interface DivisionRisk {
    id: number                        // รหัสความเสี่ยงระดับฝ่าย (Primary Key)
    risk_name: string                 // ชื่อความเสี่ยงระดับฝ่าย
    description: string               // รายละเอียดความเสี่ยง
    organizational_risk_id: number | null // รหัสความเสี่ยงระดับองค์กรที่เกี่ยวข้อง (Foreign Key, อาจเป็น null)
    created_at: string                // วันเวลาที่สร้าง (timestamp)
    updated_at: string                // วันเวลาที่แก้ไขล่าสุด (timestamp)
    deleted_at: string | null         // วันเวลาที่ลบ (soft delete)
    organizational_risk?: OrganizationalRisk // ความสัมพันธ์แบบ many-to-one กับความเสี่ยงระดับองค์กร (อาจมีหรือไม่มีก็ได้)
    attachments?: DivisionRiskAttachment[];
    risk_assessments?: RiskAssessment[] // ความสัมพันธ์แบบ one-to-many กับการประเมินความเสี่ยง
    
    // รองรับทั้งสองรูปแบบ (snake_case จาก Laravel และ camelCase ใน Vue)
    impact_criteria?: ImpactCriteria[] // snake_case จาก Laravel
    impactCriteria?: ImpactCriteria[] // camelCase ใน Vue
    
    // รองรับทั้งสองรูปแบบ (snake_case จาก Laravel และ camelCase ใน Vue)
    likelihood_criteria?: LikelihoodCriteria[] // snake_case จาก Laravel
    likelihoodCriteria?: LikelihoodCriteria[] // camelCase ใน Vue
  }
  
  // อินเตอร์เฟซสำหรับการประเมินความเสี่ยง
  export interface RiskAssessment {
    id: number;
    assessment_date: string;
    likelihood_level: number;
    impact_level: number;
    risk_score: number;
    division_risk_id: number;
    notes?: string;
    created_at: string;
    updated_at: string;
    attachments?: RiskAssessmentAttachment[];
    // ความสัมพันธ์อื่นๆ
    divisionRisk?: DivisionRisk;
    division_risk?: DivisionRisk;
  }

  // อินเตอร์เฟซทั่วไปสำหรับเกณฑ์ประเมินความเสี่ยง (ใช้ร่วมกันระหว่างเกณฑ์โอกาสเกิดและเกณฑ์ผลกระทบ)
  export interface CriteriaItem {
    id?: number;                       // รหัสเกณฑ์ (Primary Key)
    level: number;                    // ระดับเกณฑ์ (1-4)
    name: string;                     // ชื่อระดับ
    description: string;              // รายละเอียดเกณฑ์
    division_risk_id?: number;         // รหัสความเสี่ยงระดับฝ่ายที่เกี่ยวข้อง (Foreign Key)
    created_at?: string;               // วันเวลาที่สร้าง (timestamp)
    updated_at?: string;               // วันเวลาที่แก้ไขล่าสุด (timestamp)
  }
  
  // อินเตอร์เฟซสำหรับเกณฑ์ผลกระทบ
  export interface ImpactCriteria {
    id: number                       // รหัสเกณฑ์ผลกระทบ (Primary Key)
    level: number                    // ระดับผลกระทบ (1-4)
    name: string                     // ชื่อระดับผลกระทบ
    description: string | null       // รายละเอียดเกณฑ์ผลกระทบ (อาจเป็น null)
    division_risk_id: number       // รหัสความเสี่ยงระดับฝ่ายที่เกี่ยวข้อง (Foreign Key)
    created_at: string               // วันเวลาที่สร้าง (timestamp)
    updated_at: string               // วันเวลาที่แก้ไขล่าสุด (timestamp)
    division_risk?: DivisionRisk // ความสัมพันธ์แบบ many-to-one กับความเสี่ยงระดับฝ่าย
  }
  
  // อินเตอร์เฟซสำหรับเกณฑ์โอกาสเกิด
  export interface LikelihoodCriteria {
    id: number                       // รหัสเกณฑ์โอกาสเกิด (Primary Key)
    level: number                    // ระดับโอกาสเกิด (1-4)
    name: string                     // ชื่อระดับโอกาสเกิด
    description: string | null       // รายละเอียดเกณฑ์โอกาสเกิด (อาจเป็น null)
    division_risk_id: number       // รหัสความเสี่ยงระดับฝ่ายที่เกี่ยวข้อง (Foreign Key)
    created_at: string               // วันเวลาที่สร้าง (timestamp)
    updated_at: string               // วันเวลาที่แก้ไขล่าสุด (timestamp)
    division_risk?: DivisionRisk // ความสัมพันธ์แบบ many-to-one กับความเสี่ยงระดับฝ่าย
  }

  export interface OrganizationalRiskAttachment {
    id: number;
    organizational_risk_id: number;
    file_name: string;
    file_path: string;
    file_type: string;
    file_size: number;
    url: string;
    created_at: string;
    updated_at: string;
  }

  export interface DivisionRiskAttachment {
    id: number;
    division_risk_id: number;
    file_name: string;
    file_path: string;
    file_type: string;
    file_size: number;
    url: string;
    created_at: string;
    updated_at: string;
  }

  export interface RiskAssessmentAttachment {
    id: number;
    risk_assessment_id: number;
    file_name: string;
    file_path: string;
    file_type: string;
    file_size: number;
    url: string;
    created_at: string;
    updated_at: string;
  }
  