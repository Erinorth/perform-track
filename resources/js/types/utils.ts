// ======================== UTILITY TYPES ========================

import type { OrganizationalRisk } from './organizational-risk'
import type { DivisionRisk } from './division-risk'
import type { RiskControl } from './risk-control'
import type { RiskAssessment } from './risk-assessment'
import type { 
  OrganizationalRiskAttachment, 
  DivisionRiskAttachment, 
  RiskControlAttachment, 
  RiskAssessmentAttachment 
} from './organizational-risk'
import type { 
  OrganizationalRiskFormData, 
  DivisionRiskFormData, 
  RiskControlFormData, 
  RiskAssessmentFormData 
} from './division-risk'
import type { 
  OrganizationalRiskFilters, 
  DivisionRiskFilters, 
  RiskControlFilters, 
  RiskAssessmentFilters 
} from './risk-control'
import type { 
  OrganizationalRiskSort, 
  DivisionRiskSort, 
  RiskControlSort, 
  RiskAssessmentSort 
} from './risk-assessment'

// Union types สำหรับการใช้งานแบบ generic
export type AnyRisk = OrganizationalRisk | DivisionRisk
export type AnyAttachment = RiskControlAttachment | DivisionRiskAttachment | RiskAssessmentAttachment | OrganizationalRiskAttachment
export type AnyFormData = RiskControlFormData | DivisionRiskFormData | RiskAssessmentFormData | OrganizationalRiskFormData
export type AnyFilters = RiskControlFilters | DivisionRiskFilters | RiskAssessmentFilters | OrganizationalRiskFilters
export type AnySort = RiskControlSort | DivisionRiskSort | RiskAssessmentSort | OrganizationalRiskSort

// Type guard functions
export const isRiskControl = (obj: any): obj is RiskControl => {
  return obj && typeof obj === 'object' && 'control_name' in obj && 'division_risk_id' in obj
}

export const isDivisionRisk = (obj: any): obj is DivisionRisk => {
  return obj && typeof obj === 'object' && 'risk_name' in obj && !('control_name' in obj) && 'organizational_risk_id' in obj
}

export const isOrganizationalRisk = (obj: any): obj is OrganizationalRisk => {
  return obj && typeof obj === 'object' && 'risk_name' in obj && !('control_name' in obj) && !('organizational_risk_id' in obj)
}

export const isRiskAssessment = (obj: any): obj is RiskAssessment => {
  return obj && typeof obj === 'object' && 'assessment_year' in obj && 'likelihood_level' in obj && 'impact_level' in obj
}

// Generic pagination type
export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from?: number
  to?: number
}

// Generic API Response type
export interface ApiResponse<T> {
  data: T[]
  meta?: PaginationMeta
  message?: string
  success?: boolean
}

// Generic action result type
export interface ActionResult {
  success: boolean
  message: string
  data?: any
  errors?: Record<string, string[]>
}

// Date range type
export interface DateRange {
  from?: string
  to?: string
}

// Search and filter base interface
export interface BaseFilters {
  search?: string
  date_from?: string
  date_to?: string
  per_page?: number
  page?: number
}

// Sort base interface
export interface BaseSort {
  field: string
  direction: 'asc' | 'desc'
}
