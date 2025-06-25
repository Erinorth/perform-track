// ======================== DIVISION RISK TYPES ========================

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

export interface DivisionRiskFormData {
  risk_name: string
  description: string
  organizational_risk_id?: number | null
  attachments?: File[] | null
  likelihood_criteria?: Array<{ level: number, name: string, description: string }>
  impact_criteria?: Array<{ level: number, name: string, description: string }>
}

export interface DivisionRiskFilters {
  search?: string
  organizational_risk_id?: number | ''
  has_controls?: boolean
  date_from?: string
  date_to?: string
}

export interface DivisionRiskSort {
  field: 'risk_name' | 'created_at' | 'updated_at'
  direction: 'asc' | 'desc'
}

export interface DivisionRiskStatistics {
  total_risks: number
  risks_with_controls: number
  recent_assessments: number
  high_risk_count: number
  average_risk_score: number
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

// Import types สำหรับ relation
import type { OrganizationalRisk } from './organizational-risk'
import type { RiskControl } from './risk-control'
import type { RiskAssessment } from './risk-assessment'
import type { LikelihoodCriteria, ImpactCriteria } from './criteria'
