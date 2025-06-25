// ======================== CRITERIA TYPES ========================

import type { RiskLevel } from './risk-assessment'
import type { DivisionRisk } from './division-risk'

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

export interface CriteriaFormData {
  level: RiskLevel
  name: string
  description?: string | null
  division_risk_id: number
}

export interface CriteriaFilters {
  search?: string
  level?: RiskLevel | ''
  division_risk_id?: number | ''
}

export interface CriteriaSort {
  field: 'level' | 'name' | 'created_at' | 'updated_at'
  direction: 'asc' | 'desc'
}

export interface CriteriaStatistics {
  total_likelihood: number
  total_impact: number
  criteria_per_risk: number
  complete_criteria_sets: number
}
