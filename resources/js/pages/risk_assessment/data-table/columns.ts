// ไฟล์: resources\js\features\risk_assessment\columns.ts
// หน้านี้กำหนดคอลัมน์สำหรับ DataTable ของการประเมินความเสี่ยง
// เพิ่ม event สำหรับลบ (delete) ข้อมูลใน action และแสดงชื่อความเสี่ยงระดับฝ่าย
// ย้ายปุ่มย่อ/ขยายไปยังคอลัมน์งวดการประเมิน

import { h } from 'vue'
import { router } from '@inertiajs/vue3' // เพิ่มการนำเข้า router จาก inertiajs
import { ColumnDef, TableMeta, RowData } from '@tanstack/vue-table'
import { DataTableColumnHeader, DataTableDropDown } from '@/components/ui/data-table'
import type { RiskAssessment } from '@/types/types'
import { Checkbox } from '@/components/ui/checkbox'
import { Button } from '@/components/ui/button'
import { ChevronDown } from 'lucide-vue-next'
// นำเข้า icon จาก lucide-vue-next (ถ้าต้องการใช้)
import { FileText } from 'lucide-vue-next'
import { filterByPeriod, filterByRiskScore, formatAssessmentPeriod } from "@/lib/utils"

// ขยาย interface TableMeta เพื่อเพิ่ม event onEdit สำหรับการแก้ไขข้อมูล
declare module '@tanstack/vue-table' {
  interface TableMeta<TData extends RowData> {
    onEdit?: (assessment: TData) => void
    onDelete?: (assessment: TData) => void
  }
}

// ฟังก์ชันกำหนดระดับความเสี่ยงตามคะแนน
const getRiskLevel = (score: number): { text: string, color: string } => {
  if (score <= 3) {
    return { text: 'ต่ำ', color: 'bg-green-100 text-green-800' }
  } else if (score <= 8) {
    return { text: 'ปานกลาง', color: 'bg-yellow-100 text-yellow-800' }
  } else if (score <= 12) {
    return { text: 'สูง', color: 'bg-orange-100 text-orange-800' }
  } else {
    return { text: 'สูงมาก', color: 'bg-red-100 text-red-800' }
  }
}

// กำหนด columns สำหรับ DataTable
export const columns: ColumnDef<RiskAssessment>[] = [
  {
    id: "select",
    header: ({ table }) => h(Checkbox, {
      'modelValue': table.getIsAllPageRowsSelected(),
      'onUpdate:modelValue': (value: boolean | "indeterminate") => table.toggleAllPageRowsSelected(!!value),
      'aria-label': 'Select all',
    }),
    cell: ({ row }) => h(Checkbox, {
      'modelValue': row.getIsSelected(),
      'onUpdate:modelValue': (value: boolean | "indeterminate") => row.toggleSelected(!!value),
      'aria-label': 'Select row',
    }),
    enableSorting: false,
    enableHiding: false,
  },
  {
    accessorKey: "id", // คีย์หลักของข้อมูล
    header: ({ column }) => (
      // ใช้ component DataTableColumnHeader สำหรับหัวตาราง
      h(DataTableColumnHeader, {
        column: column,
        title: 'ID'
      })
    ),
  },
  // คอลัมน์ชื่อความเสี่ยงระดับฝ่าย - ลบปุ่มย่อ/ขยายออก
  {
    id: "division_risk_name",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'ชื่อความเสี่ยงระดับฝ่าย'
      })
    ),
    // เพิ่ม accessorFn เพื่อให้ระบบสามารถเข้าถึงข้อมูลสำหรับการเรียงลำดับได้
    accessorFn: (row) => row.division_risk?.risk_name || '',
    cell: ({ row }) => {
      const divisionRisk = row.original.division_risk
      console.log('แสดงชื่อความเสี่ยงระดับฝ่าย:', divisionRisk?.risk_name || 'ไม่มีข้อมูล')
      
      // แสดงเฉพาะชื่อความเสี่ยงระดับฝ่าย ไม่มีปุ่มย่อ/ขยาย
      return h('div', { class: 'flex items-center' }, [
        h('span', { class: 'truncate font-medium' }, divisionRisk?.risk_name || '-')
      ])
    },
    enableSorting: true,
  },
  // คอลัมน์งวดการประเมิน - ย้ายปุ่มย่อ/ขยายมาไว้ที่นี่
  {
    id: "assessment_period",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'งวดการประเมิน'
      })
    ),
    accessorFn: (row) => formatAssessmentPeriod(row.assessment_year, row.assessment_period),
    cell: ({ row }) => {
      const assessmentPeriod = formatAssessmentPeriod(row.original.assessment_year, row.original.assessment_period)
      console.log('แสดงงวดการประเมิน:', assessmentPeriod)
      
      return h('div', { class: 'flex items-center gap-2' }, [
        // ปุ่มสามเหลี่ยมสำหรับย่อ/ขยาย - ย้ายมาจากคอลัมน์ division_risk_name
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          class: 'p-0 h-8 w-8 flex-shrink-0',
          onClick: (e: Event) => {
            e.stopPropagation() // ป้องกันการ bubble ของ event
            console.log('สลับการขยาย/ย่อแถว:', row.original.id)
            row.toggleExpanded() // สลับสถานะย่อ/ขยาย
          }
        }, () => h(ChevronDown, {
          class: `h-4 w-4 transition-transform duration-200 ${row.getIsExpanded() ? 'rotate-180' : ''}`,
        })),
        // แสดงงวดการประเมิน
        h('span', { 
          class: 'truncate font-medium',
          title: assessmentPeriod // แสดง tooltip เมื่อ hover
        }, assessmentPeriod)
      ])
    },
    filterFn: filterByPeriod,
    enableSorting: true,
  },
  {
    accessorKey: "likelihood_level", // ระดับโอกาสเกิด
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'ระดับโอกาสเกิด'
      })
    ),
    // แก้ไขจุดที่มีการเข้าถึง divisionRisk.likelihoodCriteria
    cell: ({ row }) => {
      const level = row.getValue("likelihood_level");
      const divisionRisk = row.original.division_risk;
      let levelName = '';
      
      // เพิ่มการตรวจสอบที่สมบูรณ์มากขึ้นเพื่อหลีกเลี่ยง undefined errors
      if (divisionRisk && 
          (divisionRisk.likelihoodCriteria || divisionRisk.likelihood_criteria) && 
          ((divisionRisk.likelihoodCriteria || []).length > 0 || 
          (divisionRisk.likelihood_criteria || []).length > 0)) {
        
        // ใช้ตัวแปรที่แน่ใจว่ามีข้อมูล
        const criteriaList = divisionRisk.likelihoodCriteria || divisionRisk.likelihood_criteria || [];
        const criteria = criteriaList.find(c => c.level === level);
        if (criteria) {
          levelName = criteria.name;
        }
      }
     
      // ถ้าไม่พบข้อมูลในฐานข้อมูล ใช้ค่าเริ่มต้น
      if (!levelName) {
        switch (level) {
          case 1: levelName = 'น้อยมาก'; break;
          case 2: levelName = 'น้อย'; break;
          case 3: levelName = 'ปานกลาง'; break;
          case 4: levelName = 'สูง'; break;
          default: levelName = 'ไม่ระบุ';
        }
      }
      
      console.log(`แสดงระดับโอกาสเกิด: ${levelName} (${level})`);
      return `${levelName} (${level})`;
    },
  },
  {
    accessorKey: "impact_level", // ระดับผลกระทบ
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'ระดับผลกระทบ'
      })
    ),
    // แก้ไขจุดที่มีการเข้าถึง divisionRisk.impactCriteria
    cell: ({ row }) => {
      const level = row.getValue("impact_level");
      const divisionRisk = row.original.division_risk;
      let levelName = '';
      
      // เพิ่มการตรวจสอบที่สมบูรณ์มากขึ้นเพื่อหลีกเลี่ยง undefined errors
      if (divisionRisk && 
          (divisionRisk.impactCriteria || divisionRisk.impact_criteria) && 
          ((divisionRisk.impactCriteria || []).length > 0 || 
          (divisionRisk.impact_criteria || []).length > 0)) {
        
        // ใช้ตัวแปรที่แน่ใจว่ามีข้อมูล
        const criteriaList = divisionRisk.impactCriteria || divisionRisk.impact_criteria || [];
        const criteria = criteriaList.find(c => c.level === level);
        if (criteria) {
          levelName = criteria.name;
        }
      }
      
      // ถ้าไม่พบข้อมูลในฐานข้อมูล ใช้ค่าเริ่มต้น
      if (!levelName) {
        switch (level) {
          case 1: levelName = 'น้อยมาก'; break;
          case 2: levelName = 'น้อย'; break;
          case 3: levelName = 'ปานกลาง'; break;
          case 4: levelName = 'สูง'; break;
          default: levelName = 'ไม่ระบุ';
        }
      }
      
      console.log(`แสดงระดับผลกระทบ: ${levelName} (${level})`);
      return `${levelName} (${level})`;
    },
  },
  {
    accessorKey: "risk_score", // คะแนนความเสี่ยง
    header: ({ column }: any) => h(DataTableColumnHeader, {
      column: column,
      title: 'คะแนนความเสี่ยง'
    }),
    cell: ({ row }: any) => {
      // ดึงคะแนนความเสี่ยงจาก assessment
      const score = row.original.risk_score
      // ใช้ฟังก์ชัน getRiskLevel เพื่อแปลงคะแนนเป็นระดับ
      const risk = getRiskLevel(score)
      // คืนค่า span ที่มีสีและข้อความตามระดับความเสี่ยง
      return h('span', {
        class: [
          'px-2 py-1 text-xs font-medium rounded-full',
          risk.color
        ]
      }, `${risk.text} (${score})`)
    },
    filterFn: filterByRiskScore
  },
  {
    accessorKey: "notes", // บันทึกเพิ่มเติม
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'บันทึกเพิ่มเติม'
      })
    ),
    // แสดงบันทึกไม่เกิน 50 ตัวอักษร ถ้ายาวให้เติม ...
    cell: ({ row }) => {
      const notes = row.getValue("notes") as string
      if (!notes) return "-"
      return notes.length > 50 ? `${notes.substring(0, 50)}...` : notes
    }
  },
  {
    accessorKey: "created_at", // วันที่สร้างข้อมูล
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'วันที่สร้าง'
      })
    ),
    // แปลงวันที่ให้อยู่ในรูปแบบไทย พร้อมแสดงเวลา
    cell: ({ row }) => {
      const date = new Date(row.getValue("created_at"))
      return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    enableHiding: true // สามารถซ่อนคอลัมน์นี้ได้
  },
  {
    accessorKey: "updated_at", // วันที่แก้ไขล่าสุด
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'แก้ไขล่าสุด'
      })
    ),
    // แปลงวันที่ให้อยู่ในรูปแบบไทย พร้อมแสดงเวลา
    cell: ({ row }) => {
      const date = new Date(row.getValue("updated_at"))
      return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    enableHiding: true, // สามารถซ่อนคอลัมน์นี้ได้
  },
  {
    id: "actions",
    enableHiding: false,
    
    // แก้ไขการใช้ DataTableDropDown ที่ต้องการ property risk_name
    cell: ({ row, table }) => {
      const assessment = row.original;
      const meta = table.options.meta as TableMeta<RiskAssessment>
      
      // สร้าง object ที่มี risk_name เพื่อให้ตรงกับ prop ที่ DataTableDropDown ต้องการ
      const dropdownData = {
        id: assessment.id,
        risk_name: assessment.division_risk?.risk_name || 'ไม่มีชื่อความเสี่ยง'
        // เพิ่ม properties อื่นๆ จาก assessment ที่จำเป็น
      };
      
      // ใช้ dropdownData แทน assessment
      return h('div', { class: 'relative' }, [
        h(DataTableDropDown, {
          data: dropdownData, 
          menuLabel: 'ตัวเลือกการประเมินความเสี่ยง',
          onExpand: () => {
            console.log('ดูรายละเอียดการประเมินความเสี่ยง', assessment.id)
            router.visit(`/risk-assessments/${assessment.id}`, {
              data: { 
                previousPage: window.location.href,
                source: 'data-table'
              },
              preserveState: true
            });
          },
          onEdit: () => {
            console.log('แก้ไขข้อมูลการประเมินความเสี่ยง:', assessment.id)
            meta?.onEdit?.(assessment)
          },
          onDelete: () => {
            console.log('ลบข้อมูลการประเมินความเสี่ยง:', assessment.id)
            meta?.onDelete?.(assessment)
          },
        }),
      ]);
    }
  },
]
