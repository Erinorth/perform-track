/*
  ไฟล์: resources/js/types/types.ts
  รวม TypeScript interfaces และ utility สำหรับระบบบริหารความเสี่ยง (Risk Management)
  รองรับทั้ง OrganizationalRisk, DivisionRisk, RiskControl, RiskAssessment, Criteria, Attachment ฯลฯ
  ใช้ร่วมกับ Laravel 12 + Vue 3 + TypeScript
*/

// ================= ORGANIZATIONAL RISK =================
export interface OrganizationalRisk {
  id: number
  risk_name: string
  description: string
  created_at: string
  updated_at: string
  deleted_at?: string | null
  attachments?: OrganizationalRiskAttachment[]
  division_risks?: DivisionRisk[]
}

// ================= DIVISION RISK =================
export interface DivisionRisk {
  id: number
  risk_name: string
  description: string
  organizational_risk_id?: number | null
  created_at: string
  updated_at: string
  deleted_at?: string | null
  organizational_risk?: OrganizationalRisk
  attachments?: DivisionRiskAttachment[]
  risk_controls?: RiskControl[]
  risk_assessments?: RiskAssessment[]
  likelihood_criteria?: LikelihoodCriteria[]
  impact_criteria?: ImpactCriteria[]
}

// ================= RISK CONTROL =================
export type ControlType = 'preventive' | 'detective' | 'corrective' | 'compensating'
export type ControlStatus = 'active' | 'inactive'

export const CONTROL_TYPE_LABELS: Record<ControlType, string> = {
  preventive: 'การป้องกัน',
  detective: 'การตรวจจับ',
  corrective: 'การแก้ไข',
  compensating: 'การชดเชย'
} as const

export const CONTROL_STATUS_LABELS: Record<ControlStatus, string> = {
  active: 'ใช้งาน',
  inactive: 'ไม่ใช้งาน'
} as const

export const CONTROL_TYPE_COLORS: Record<ControlType, string> = {
  preventive: 'blue',
  detective: 'green',
  corrective: 'orange',
  compensating: 'purple'
} as const

export interface RiskControl {
  id: number
  division_risk_id: number
  control_name: string
  description?: string
  owner?: string
  status: ControlStatus
  control_type?: ControlType
  implementation_details?: string
  created_at: string
  updated_at: string
  deleted_at?: string | null
  division_risk?: DivisionRisk
  attachments?: RiskControlAttachment[]
}

export interface RiskControlAttachment {
  id: number
  risk_control_id: number
  filename: string
  filepath: string
  filetype?: string
  filesize?: number
  created_at: string
  updated_at: string
  deleted_at?: string | null
}

// ================= RISK ASSESSMENT =================
export type RiskLevel = 1 | 2 | 3 | 4

export interface RiskAssessment {
  id: number
  assessment_year: number
  assessment_period: number
  likelihood_level: RiskLevel
  impact_level: RiskLevel
  risk_score: number
  division_risk_id: number
  notes?: string
  created_at: string
  updated_at: string
  deleted_at?: string | null
  division_risk?: DivisionRisk
  attachments?: RiskAssessmentAttachment[]
}

export interface RiskAssessmentAttachment {
  id: number
  risk_assessment_id: number
  file_name: string
  file_path: string
  file_type?: string
  file_size?: number
  created_at: string
  updated_at: string
  deleted_at?: string | null
}

// ================= CRITERIA =================
export interface CriteriaItem {
  id?: number
  level: number
  name: string
  description: string | null
  division_risk_id?: number
  created_at?: string
  updated_at?: string
}

export interface LikelihoodCriteria {
  id: number
  level: RiskLevel
  name: string
  description?: string | null
  division_risk_id: number
  created_at: string
  updated_at: string
  division_risk?: DivisionRisk
}

export interface ImpactCriteria {
  id: number
  level: RiskLevel
  name: string
  description?: string | null
  division_risk_id: number
  created_at: string
  updated_at: string
  division_risk?: DivisionRisk
}

// ================= ATTACHMENTS =================
export interface OrganizationalRiskAttachment {
  id: number
  organizational_risk_id: number
  filename: string
  filepath: string
  filetype?: string
  filesize?: number
  created_at: string
  updated_at: string
  deleted_at?: string | null
}

export interface DivisionRiskAttachment {
  id: number
  division_risk_id: number
  filename: string
  filepath: string
  filetype?: string
  filesize?: number
  created_at: string
  updated_at: string
  deleted_at?: string | null
}

// ================= FORM DATA =================
export interface RiskControlFormData {
  division_risk_id: number
  control_name: string
  description?: string
  owner?: string
  status: ControlStatus
  control_type?: ControlType
  implementation_details?: string
  attachments?: File[] | null
}

export interface DivisionRiskFormData {
  risk_name: string
  description: string
  organizational_risk_id?: number | null
  attachments?: File[] | null
  likelihood_criteria?: Array<{ level: number, name: string, description: string }>
  impact_criteria?: Array<{ level: number, name: string, description: string }>
}

export interface RiskAssessmentFormData {
  assessment_year: number
  assessment_period: number
  likelihood_level: RiskLevel
  impact_level: RiskLevel
  division_risk_id: number
  notes?: string
  attachments?: File[] | null
}

// ================= STATISTICS =================
export interface RiskControlStatistics {
  total_controls: number
  active_controls: number
  inactive_controls: number
  by_type: Record<ControlType, number>
  by_division: Record<string, number>
  effectiveness_rate: number
  controls_per_risk: number
  recent_additions: number
}

export interface DivisionRiskStatistics {
  total_risks: number
  risks_with_controls: number
  recent_assessments: number
  high_risk_count: number
  average_risk_score: number
}

export interface RiskAssessmentStatistics {
  total_assessments: number
  current_year_assessments: number
  average_likelihood: number
  average_impact: number
  risk_distribution: Record<string, number>
}

// ================= FILTERS =================
export interface RiskControlFilters {
  search?: string
  status?: ControlStatus | ''
  control_type?: ControlType | ''
  division_risk_id?: number | ''
  owner?: string
  date_from?: string
  date_to?: string
}

export interface DivisionRiskFilters {
  search?: string
  organizational_risk_id?: number | ''
  has_controls?: boolean
  date_from?: string
  date_to?: string
}

export interface RiskAssessmentFilters {
  search?: string
  assessment_year?: number | ''
  assessment_period?: number | ''
  division_risk_id?: number | ''
  risk_level?: 'low' | 'medium' | 'high' | 'critical' | ''
  date_from?: string
  date_to?: string
}

// ================= SORT =================
export interface RiskControlSort {
  field: 'control_name' | 'status' | 'control_type' | 'owner' | 'created_at' | 'updated_at'
  direction: 'asc' | 'desc'
}

export interface DivisionRiskSort {
  field: 'risk_name' | 'created_at' | 'updated_at'
  direction: 'asc' | 'desc'
}

export interface RiskAssessmentSort {
  field: 'assessment_year' | 'assessment_period' | 'risk_score' | 'likelihood_level' | 'impact_level' | 'created_at'
  direction: 'asc' | 'desc'
}

// ================= API RESPONSE =================
export interface RiskControlApiResponse {
  data: RiskControl[]
  statistics: RiskControlStatistics
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface DivisionRiskApiResponse {
  data: DivisionRisk[]
  statistics: DivisionRiskStatistics
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

// ================= UTILITY TYPES & FUNCTIONS =================
export interface FileValidationResult {
  valid: boolean
  errors: string[]
  validFiles?: File[]
}

export interface UploadedFile {
  name: string
  size: number
  type: string
  url?: string
}

export interface SystemSettings {
  max_file_size: number
  allowed_file_types: string[]
  risk_matrix_size: number
  assessment_periods_per_year: number
}

// Dashboard
export interface RiskDashboardData {
  overview: {
    total_organizational_risks: number
    total_division_risks: number
    total_controls: number
    total_assessments: number
  }
  risk_matrix: RiskMatrixData
  top_risks: DivisionRisk[]
  recent_controls: RiskControl[]
  statistics: {
    controls: RiskControlStatistics
    assessments: RiskAssessmentStatistics
  }
}

export interface RiskMatrixData {
  matrix: Array<Array<{
    likelihood: RiskLevel
    impact: RiskLevel
    count: number
    risks: DivisionRisk[]
  }>>
  legend: {
    likelihood_labels: Record<RiskLevel, string>
    impact_labels: Record<RiskLevel, string>
    risk_levels: Record<string, { min: number; max: number; color: string; label: string }>
  }
}

// Union types
export type AnyRisk = OrganizationalRisk | DivisionRisk
export type AnyAttachment = RiskControlAttachment | DivisionRiskAttachment | RiskAssessmentAttachment | OrganizationalRiskAttachment
export type AnyFormData = RiskControlFormData | DivisionRiskFormData | RiskAssessmentFormData
export type AnyFilters = RiskControlFilters | DivisionRiskFilters | RiskAssessmentFilters
export type AnySort = RiskControlSort | DivisionRiskSort | RiskAssessmentSort

// Type guards
export const isRiskControl = (obj: any): obj is RiskControl => {
  return obj && typeof obj === 'object' && 'control_name' in obj && 'division_risk_id' in obj
}
export const isDivisionRisk = (obj: any): obj is DivisionRisk => {
  return obj && typeof obj === 'object' && 'risk_name' in obj && !('control_name' in obj)
}
export const isControlType = (value: string): value is ControlType => {
  return ['preventive', 'detective', 'corrective', 'compensating'].includes(value)
}
export const isControlStatus = (value: string): value is ControlStatus => {
  return ['active', 'inactive'].includes(value)
}
export const isRiskLevel = (value: number): value is RiskLevel => {
  return [1, 2, 3, 4].includes(value)
}

// Helper functions
export const getRiskLevelColor = (score: number): string => {
  if (score <= 4) return 'green'
  if (score <= 8) return 'yellow'
  if (score <= 12) return 'orange'
  return 'red'
}
export const getRiskLevelLabel = (score: number): string => {
  if (score <= 4) return 'ต่ำ'
  if (score <= 8) return 'ปานกลาง'
  if (score <= 12) return 'สูง'
  return 'วิกฤติ'
}
export const calculateRiskScore = (likelihood: RiskLevel, impact: RiskLevel): number => {
  return likelihood * impact
}
export const formatFileSize = (bytes: number): string => {
  if (!bytes || bytes === 0) return '0 B'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}
export const getFileExtension = (filename: string): string => {
  return filename.split('.').pop()?.toLowerCase() || ''
}
export const isValidFileType = (filename: string, allowedTypes: string[]): boolean => {
  const extension = '.' + getFileExtension(filename)
  return allowedTypes.includes(extension)
}
