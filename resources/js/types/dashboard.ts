// ======================== DASHBOARD TYPES ========================

import type { DivisionRisk } from './division-risk'
import type { RiskControl } from './risk-control'
import type { RiskLevel } from './risk-assessment'
import type { RiskControlStatistics, RiskAssessmentStatistics } from './risk-assessment'

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

export interface DashboardFilters {
  timeRange: 'month' | 'quarter' | 'year' | 'all'
  riskType?: 'organizational' | 'division' | 'all'
  assessmentYear?: number
  organizationalRiskId?: number
}

export interface DashboardMetrics {
  total_risks: number
  risks_change: number
  total_controls: number
  controls_change: number
  total_assessments: number
  assessments_change: number
  average_risk_score: number
  risk_score_change: number
}

export interface TrendDataPoint {
  date: string
  organizational_risks: number
  division_risks: number
  controls: number
  assessments: number
}

export interface RiskDistribution {
  low: number
  medium: number
  high: number
  critical: number
}

export interface ControlEffectiveness {
  total_controls: number
  active_controls: number
  effectiveness_rate: number
  by_type: Record<string, number>
}
