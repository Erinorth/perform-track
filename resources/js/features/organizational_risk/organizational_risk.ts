/* resources/js/features/organizational_risk/organizational_risk.ts */
export interface DepartmentRisk {
  id: number
  risk_name: string
  description?: string
  year: number
  organizational_risk_id?: number
  created_at: string
  updated_at: string
}

export interface OrganizationalRisk {
  id: number
  risk_name: string
  description: string
  year: number
  created_at: string
  updated_at: string
  department_risks?: DepartmentRisk[] // เพิ่มความสัมพันธ์
  active?: boolean
}