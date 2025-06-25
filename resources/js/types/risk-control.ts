// ======================== RISK CONTROL TYPES ========================

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

export interface RiskControlFilters {
  search?: string
  status?: ControlStatus | ''
  control_type?: ControlType | ''
  division_risk_id?: number | ''
  owner?: string
  date_from?: string
  date_to?: string
}

export interface RiskControlSort {
  field: 'control_name' | 'status' | 'control_type' | 'owner' | 'created_at' | 'updated_at'
  direction: 'asc' | 'desc'
}

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

// Utility functions สำหรับ Risk Control
export const isControlType = (value: string): value is ControlType => {
  return ['preventive', 'detective', 'corrective', 'compensating'].includes(value)
}

export const isControlStatus = (value: string): value is ControlStatus => {
  return ['active', 'inactive'].includes(value)
}

// Import types สำหรับ relation
import type { DivisionRisk } from './division-risk'
