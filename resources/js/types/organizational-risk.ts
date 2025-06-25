// ======================== ORGANIZATIONAL RISK TYPES ========================

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

export interface OrganizationalRiskFormData {
  risk_name: string
  description: string
  attachments?: File[] | null
}

export interface OrganizationalRiskFilters {
  search?: string
  date_from?: string
  date_to?: string
}

export interface OrganizationalRiskSort {
  field: 'risk_name' | 'created_at' | 'updated_at'
  direction: 'asc' | 'desc'
}

export interface OrganizationalRiskStatistics {
  total_risks: number
  risks_with_divisions: number
  recent_additions: number
  average_divisions_per_risk: number
}

export interface OrganizationalRiskApiResponse {
  data: OrganizationalRisk[]
  statistics: OrganizationalRiskStatistics
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

// Import types สำหรับ relation
import type { DivisionRisk } from './division-risk'
