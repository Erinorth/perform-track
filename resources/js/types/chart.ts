// กำหนด interface สำหรับข้อมูลกราฟ
export interface ChartDataset {
  label: string
  data: number[]
  backgroundColor?: string | string[]
  borderColor?: string | string[]
  borderWidth?: number
  tension?: number
}

export interface ChartData {
  labels: string[]
  datasets: ChartDataset[]
}

export interface ChartOptions {
  responsive: boolean
  maintainAspectRatio: boolean
  plugins?: {
    legend?: {
      position: 'top' | 'bottom' | 'left' | 'right'
      display: boolean
    }
    title?: {
      display: boolean
      text: string
    }
  }
  scales?: {
    x?: {
      display: boolean
      title?: {
        display: boolean
        text: string
      }
    }
    y?: {
      display: boolean
      title?: {
        display: boolean
        text: string
      }
      beginAtZero: boolean
    }
  }
}

export interface TrendData {
  date: string
  critical: number // เพิ่ม critical
  high: number
  medium: number
  low: number
}

export interface FilterParams {
  timeRange: string
  riskType: string | number
}

// เพิ่ม interface สำหรับ Risk Assessment ตาม database schema
export interface RiskAssessmentData {
  likelihood_level: number // 1-4 ตาม likelihood_criteria table
  impact_level: number     // 1-4 ตาม impact_criteria table  
  risk_score: number       // likelihood_level * impact_level
  risks: Array<{
    id: number
    risk_name: string
    description?: string
    division_risk_id?: number
    organizational_risk_id?: number
    org_risk_name?: string
    assessment_date?: string
    notes?: string
  }>
}

// เพิ่ม interface สำหรับ Bubble Chart
export interface BubbleDataPoint {
  x: number
  y: number
  r: number
  riskCount: number
  riskScore: number
  risks: Array<{
    id: number
    risk_name: string
    description?: string
    division_risk_id?: number
    organizational_risk_id?: number
    org_risk_name?: string
    assessment_date?: string
    notes?: string
  }>
}

export interface BubbleDataset {
  label: string
  data: BubbleDataPoint[]
  backgroundColor: string
  borderColor: string
  borderWidth: number
  hoverBackgroundColor: string
  hoverBorderWidth: number
}

export interface BubbleChartData {
  datasets: BubbleDataset[]
}

// เพิ่ม interface สำหรับสถิติความเสี่ยง
export interface RiskSummary {
  low: number      // 1-3
  medium: number   // 4-8
  high: number     // 9-12
  critical: number // 13-16
}
