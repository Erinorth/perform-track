// ======================== MAIN EXPORT FILE ========================

import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

// Export ทุกอย่างจากไฟล์ย่อย
export * from './organizational-risk'
export * from './division-risk'
export * from './risk-control'
export * from './risk-assessment'
export * from './criteria'
export * from './file-system'
export * from './dashboard'
export * from './utils'

// Export types ที่มีอยู่เดิม
export * from './chart'
export * from './combobox'
export * from './permission'

// Export global types
export * from './globals.d'
export * from './index.d'
export * from './ziggy.d'

// Re-export commonly used types for convenience
export type {
  OrganizationalRisk,
  DivisionRisk,
  RiskControl,
  RiskAssessment
} from './organizational-risk'

export type {
  ControlType,
  ControlStatus,
  RiskLevel
} from './risk-control'

export type {
  LikelihoodCriteria,
  ImpactCriteria
} from './criteria'

export type {
  RiskDashboardData,
  RiskMatrixData
} from './dashboard'

export type {
  FileValidationResult,
  SystemSettings
} from './file-system'

export type {
  AnyRisk,
  AnyAttachment,
  ApiResponse,
  ActionResult
} from './utils'
