export interface OrganizationalRisk {
    id: number;
    risk_name: string;
    description: string;
    year: number;
    active: boolean;
    created_at: string;
    updated_at: string;
  }
  
export interface RiskHistory {
  id: number;
  risk_id: number;
  user_id: number;
  user_name: string;
  action_type: 'create' | 'update' | 'delete' | 'restore';
  changes: Record<string, {from: string | null, to: string | null}>;
  created_at: string;
}

export interface RiskFormData {
  risk_name: string;
  description: string;
  year: number;
  active: boolean;
}
  