// ======================== RISK ASSESSMENT TYPES ========================

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

export interface RiskAssessmentFormData {
  assessment_year: number
  assessment_period: number
  likelihood_level: RiskLevel
  impact_level: RiskLevel
  division_risk_id: number
  notes?: string
  attachments?: File[] | null
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

export interface RiskAssessmentSort {
  field: 'assessment_year' | 'assessment_period' | 'risk_score' | 'likelihood_level' | 'impact_level' | 'created_at'
  direction: 'asc' | 'desc'
}

export interface RiskAssessmentStatistics {
  total_assessments: number
  current_year_assessments: number
  average_likelihood: number
  average_impact: number
  risk_distribution: Record<string, number>
}

export interface RiskAssessmentApiResponse {
  data: RiskAssessment[]
  statistics: RiskAssessmentStatistics
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

// Utility functions สำหรับ Risk Assessment
export const isRiskLevel = (value: number): value is RiskLevel => {
  return [1, 2, 3, 4].includes(value)
}

export const calculateRiskScore = (likelihood: RiskLevel, impact: RiskLevel): number => {
  return likelihood * impact
}

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

// Import types สำหรับ relation
import type { DivisionRisk } from './division-risk'
