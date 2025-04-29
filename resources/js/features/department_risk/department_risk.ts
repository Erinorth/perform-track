// resources/js/features/department_risk/department_risk.ts
export interface DepartmentRisk {
  id: number
  risk_name: string
  description?: string
  year: number
  organizational_risk_id?: number
  created_at: string
  updated_at: string
}