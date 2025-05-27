/**
 * ไฟล์: resources/js/types/risk-control.ts
 * คำอธิบาย: Type definitions สำหรับระบบการควบคุมความเสี่ยง
 * ทำหน้าที่: กำหนดโครงสร้างข้อมูลและ interface ทั้งหมดที่ใช้ในระบบ
 * ใช้งาน: ร่วมกับ Vue 3, TypeScript, Laravel 12
 */

// ============ BASE TYPES ============

// ประเภทของการควบคุมความเสี่ยง
export type ControlType = 'preventive' | 'detective' | 'corrective' | 'compensating';

// สถานะของการควบคุมความเสี่ยง
export type ControlStatus = 'active' | 'inactive';

// ระดับความเสี่ยง (1-4)
export type RiskLevel = 1 | 2 | 3 | 4;

// ป้ายกำกับสำหรับประเภทการควบคุม
export const CONTROL_TYPE_LABELS: Record<ControlType, string> = {
  preventive: 'การป้องกัน',
  detective: 'การตรวจจับ',
  corrective: 'การแก้ไข',
  compensating: 'การชดเชย'
} as const;

// ป้ายกำกับสำหรับสถานะการควบคุม
export const CONTROL_STATUS_LABELS: Record<ControlStatus, string> = {
  active: 'ใช้งาน',
  inactive: 'ไม่ใช้งาน'
} as const;

// สีสำหรับแต่ละประเภทการควบคุม
export const CONTROL_TYPE_COLORS: Record<ControlType, string> = {
  preventive: 'blue',
  detective: 'green', 
  corrective: 'orange',
  compensating: 'purple'
} as const;

// ============ ORGANIZATIONAL RISK INTERFACES ============

// Interface สำหรับความเสี่ยงระดับองค์กร
export interface OrganizationalRisk {
  id: number;
  risk_name: string;
  description: string;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
  
  // Relations - ความสัมพันธ์
  division_risks?: DivisionRisk[];
  attachments?: OrganizationalRiskAttachment[];
}

// Interface สำหรับเอกสารแนบของความเสี่ยงระดับองค์กร
export interface OrganizationalRiskAttachment {
  id: number;
  organizational_risk_id: number;
  filename: string;
  filepath: string;
  filetype?: string;
  filesize?: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
}

// ============ DIVISION RISK INTERFACES ============

// Interface สำหรับความเสี่ยงระดับฝ่าย/หน่วยงาน
export interface DivisionRisk {
  id: number;
  risk_name: string;
  description: string;
  organizational_risk_id?: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
  
  // Relations - ความสัมพันธ์
  organizational_risk?: OrganizationalRisk;
  risk_controls?: RiskControl[];
  risk_assessments?: RiskAssessment[];
  likelihood_criteria?: LikelihoodCriteria[];
  impact_criteria?: ImpactCriteria[];
  attachments?: DivisionRiskAttachment[];
}

// Interface สำหรับเอกสารแนบของความเสี่ยงระดับฝ่าย
export interface DivisionRiskAttachment {
  id: number;
  division_risk_id: number;
  filename: string;
  filepath: string;
  filetype?: string;
  filesize?: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
}

// ============ RISK CONTROL INTERFACES ============

// Interface หลักสำหรับการควบคุมความเสี่ยง
export interface RiskControl {
  id: number;
  division_risk_id: number;
  control_name: string;
  description?: string;
  owner?: string;
  status: ControlStatus;
  control_type?: ControlType;
  implementation_details?: string;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
  
  // Relations - ความสัมพันธ์
  division_risk?: DivisionRisk;
  attachments?: RiskControlAttachment[];
}

// Interface สำหรับเอกสารแนบของการควบคุมความเสี่ยง
export interface RiskControlAttachment {
  id: number;
  risk_control_id: number;
  filename: string;
  filepath: string;
  filetype?: string;
  filesize?: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
}

// Interface สำหรับการควบคุมความเสี่ยงที่มีข้อมูลเพิ่มเติม
export interface RiskControlExtended extends RiskControl {
  type_label: string;
  status_label: string;
  effectiveness_score?: number;
  last_review_date?: string;
  next_review_date?: string;
  division_risk_name?: string;
  organizational_risk_name?: string;
}

// ============ RISK ASSESSMENT INTERFACES ============

// Interface สำหรับการประเมินความเสี่ยง
export interface RiskAssessment {
  id: number;
  assessment_year: number;
  assessment_period: number;
  likelihood_level: RiskLevel;
  impact_level: RiskLevel;
  risk_score: number;
  division_risk_id: number;
  notes?: string;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
  
  // Relations - ความสัมพันธ์
  division_risk?: DivisionRisk;
  attachments?: RiskAssessmentAttachment[];
}

// Interface สำหรับเอกสารแนบของการประเมินความเสี่ยง
export interface RiskAssessmentAttachment {
  id: number;
  risk_assessment_id: number;
  file_name: string;
  file_path: string;
  file_type?: string;
  file_size?: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
}

// ============ CRITERIA INTERFACES ============

// Interface สำหรับเกณฑ์ความน่าจะเป็น
export interface LikelihoodCriteria {
  id: number;
  level: RiskLevel;
  name: string;
  description?: string;
  division_risk_id: number;
  created_at: string;
  updated_at: string;
  
  // Relations - ความสัมพันธ์
  division_risk?: DivisionRisk;
}

// Interface สำหรับเกณฑ์ผลกระทบ
export interface ImpactCriteria {
  id: number;
  level: RiskLevel;
  name: string;
  description?: string;
  division_risk_id: number;
  created_at: string;
  updated_at: string;
  
  // Relations - ความสัมพันธ์
  division_risk?: DivisionRisk;
}

// ============ FORM DATA INTERFACES ============

// Interface สำหรับข้อมูลฟอร์มการควบคุมความเสี่ยง
export interface RiskControlFormData {
  division_risk_id: number;
  control_name: string;
  description?: string;
  owner?: string;
  status: ControlStatus;
  control_type?: ControlType;
  implementation_details?: string;
  attachments?: File[] | null;
}

// Interface สำหรับข้อมูลฟอร์มความเสี่ยงระดับฝ่าย
export interface DivisionRiskFormData {
  risk_name: string;
  description: string;
  organizational_risk_id?: number;
  attachments?: File[] | null;
}

// Interface สำหรับข้อมูลฟอร์มการประเมินความเสี่ยง
export interface RiskAssessmentFormData {
  assessment_year: number;
  assessment_period: number;
  likelihood_level: RiskLevel;
  impact_level: RiskLevel;
  division_risk_id: number;
  notes?: string;
  attachments?: File[] | null;
}

// ============ STATISTICS INTERFACES ============

// Interface สำหรับสถิติการควบคุมความเสี่ยง
export interface RiskControlStatistics {
  total_controls: number;
  active_controls: number;
  inactive_controls: number;
  by_type: Record<ControlType, number>;
  by_division: Record<string, number>;
  effectiveness_rate: number;
  controls_per_risk: number;
  recent_additions: number;
}

// Interface สำหรับสถิติความเสี่ยงระดับฝ่าย
export interface DivisionRiskStatistics {
  total_risks: number;
  risks_with_controls: number;
  recent_assessments: number;
  high_risk_count: number;
  average_risk_score: number;
}

// Interface สำหรับสถิติการประเมินความเสี่ยง
export interface RiskAssessmentStatistics {
  total_assessments: number;
  current_year_assessments: number;
  average_likelihood: number;
  average_impact: number;
  risk_distribution: Record<string, number>;
}

// ============ FILTER AND SEARCH INTERFACES ============

// Interface สำหรับการกรองข้อมูลการควบคุมความเสี่ยง
export interface RiskControlFilters {
  search?: string;
  status?: ControlStatus | '';
  control_type?: ControlType | '';
  division_risk_id?: number | '';
  owner?: string;
  date_from?: string;
  date_to?: string;
}

// Interface สำหรับการกรองข้อมูลความเสี่ยงระดับฝ่าย
export interface DivisionRiskFilters {
  search?: string;
  organizational_risk_id?: number | '';
  has_controls?: boolean;
  date_from?: string;
  date_to?: string;
}

// Interface สำหรับการกรองการประเมินความเสี่ยง
export interface RiskAssessmentFilters {
  search?: string;
  assessment_year?: number | '';
  assessment_period?: number | '';
  division_risk_id?: number | '';
  risk_level?: 'low' | 'medium' | 'high' | 'critical' | '';
  date_from?: string;
  date_to?: string;
}

// ============ SORT INTERFACES ============

// Type สำหรับการเรียงลำดับข้อมูลการควบคุมความเสี่ยง
export interface RiskControlSort {
  field: 'control_name' | 'status' | 'control_type' | 'owner' | 'created_at' | 'updated_at';
  direction: 'asc' | 'desc';
}

// Type สำหรับการเรียงลำดับข้อมูลความเสี่ยงระดับฝ่าย
export interface DivisionRiskSort {
  field: 'risk_name' | 'created_at' | 'updated_at';
  direction: 'asc' | 'desc';
}

// Type สำหรับการเรียงลำดับการประเมินความเสี่ยง
export interface RiskAssessmentSort {
  field: 'assessment_year' | 'assessment_period' | 'risk_score' | 'likelihood_level' | 'impact_level' | 'created_at';
  direction: 'asc' | 'desc';
}

// ============ API RESPONSE INTERFACES ============

// Interface สำหรับการตอบกลับจาก API การควบคุมความเสี่ยง
export interface RiskControlApiResponse {
  data: RiskControl[];
  statistics: RiskControlStatistics;
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

// Interface สำหรับการตอบกลับจาก API ความเสี่ยงระดับฝ่าย
export interface DivisionRiskApiResponse {
  data: DivisionRisk[];
  statistics: DivisionRiskStatistics;
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

// ============ UTILITY INTERFACES ============

// Interface สำหรับการตรวจสอบไฟล์
export interface FileValidationResult {
  valid: boolean;
  errors: string[];
  validFiles: File[];
}

// Interface สำหรับข้อมูลไฟล์ที่อัปโหลด
export interface UploadedFile {
  name: string;
  size: number;
  type: string;
  url?: string;
}

// Interface สำหรับการตั้งค่าระบบ
export interface SystemSettings {
  max_file_size: number;
  allowed_file_types: string[];
  risk_matrix_size: number;
  assessment_periods_per_year: number;
}

// ============ DASHBOARD INTERFACES ============

// Interface สำหรับข้อมูล Dashboard
export interface RiskDashboardData {
  overview: {
    total_organizational_risks: number;
    total_division_risks: number;
    total_controls: number;
    total_assessments: number;
  };
  risk_matrix: RiskMatrixData;
  top_risks: DivisionRisk[];
  recent_controls: RiskControl[];
  statistics: {
    controls: RiskControlStatistics;
    assessments: RiskAssessmentStatistics;
  };
}

// Interface สำหรับข้อมูล Risk Matrix
export interface RiskMatrixData {
  matrix: Array<Array<{
    likelihood: RiskLevel;
    impact: RiskLevel;
    count: number;
    risks: DivisionRisk[];
  }>>;
  legend: {
    likelihood_labels: Record<RiskLevel, string>;
    impact_labels: Record<RiskLevel, string>;
    risk_levels: Record<string, { min: number; max: number; color: string; label: string }>;
  };
}

// ============ EXPORT UTILITY TYPES ============

// Union types สำหรับใช้งานทั่วไป
export type AnyRisk = OrganizationalRisk | DivisionRisk;
export type AnyAttachment = RiskControlAttachment | DivisionRiskAttachment | RiskAssessmentAttachment | OrganizationalRiskAttachment;
export type AnyFormData = RiskControlFormData | DivisionRiskFormData | RiskAssessmentFormData;
export type AnyFilters = RiskControlFilters | DivisionRiskFilters | RiskAssessmentFilters;
export type AnySort = RiskControlSort | DivisionRiskSort | RiskAssessmentSort;

// Helper type สำหรับ optional fields
export type PartialRiskControl = Partial<RiskControl>;
export type RequiredRiskControlFields = Required<Pick<RiskControl, 'division_risk_id' | 'control_name' | 'status'>>;

// Type guards สำหรับการตรวจสอบประเภท
export const isRiskControl = (obj: any): obj is RiskControl => {
  return obj && typeof obj === 'object' && 'control_name' in obj && 'division_risk_id' in obj;
};

export const isDivisionRisk = (obj: any): obj is DivisionRisk => {
  return obj && typeof obj === 'object' && 'risk_name' in obj && !('control_name' in obj);
};

export const isControlType = (value: string): value is ControlType => {
  return ['preventive', 'detective', 'corrective', 'compensating'].includes(value);
};

export const isControlStatus = (value: string): value is ControlStatus => {
  return ['active', 'inactive'].includes(value);
};

export const isRiskLevel = (value: number): value is RiskLevel => {
  return [1, 2, 3, 4].includes(value);
};

// Helper functions สำหรับการแปลงและการใช้งาน
export const getRiskLevelColor = (score: number): string => {
  if (score <= 4) return 'green';    // Low risk
  if (score <= 8) return 'yellow';   // Medium risk  
  if (score <= 12) return 'orange';  // High risk
  return 'red';                      // Critical risk
};

export const getRiskLevelLabel = (score: number): string => {
  if (score <= 4) return 'ต่ำ';
  if (score <= 8) return 'ปานกลาง';
  if (score <= 12) return 'สูง';
  return 'วิกฤติ';
};

export const calculateRiskScore = (likelihood: RiskLevel, impact: RiskLevel): number => {
  return likelihood * impact;
};

export const formatFileSize = (bytes: number): string => {
  if (!bytes || bytes === 0) return '0 B';
  
  if (bytes < 1024) {
    return bytes + ' B';
  } else if (bytes < 1024 * 1024) {
    return (bytes / 1024).toFixed(1) + ' KB';
  } else {
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }
};

export const getFileExtension = (filename: string): string => {
  return filename.split('.').pop()?.toLowerCase() || '';
};

export const isValidFileType = (filename: string, allowedTypes: string[]): boolean => {
  const extension = '.' + getFileExtension(filename);
  return allowedTypes.includes(extension);
};
