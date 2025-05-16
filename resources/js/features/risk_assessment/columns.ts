// ไฟล์: resources\js\features\risk_assessment\columns.ts
// หน้านี้กำหนดคอลัมน์สำหรับ DataTable ของการประเมินความเสี่ยง
// เพิ่ม event สำหรับลบ (delete) ข้อมูลใน action และแสดงชื่อความเสี่ยงระดับฝ่าย

import { h } from 'vue'
import { ColumnDef, TableMeta, RowData } from '@tanstack/vue-table'
import { DataTableColumnHeader, DataTableDropDown } from '@/components/ui/data-table'
import type { RiskAssessment } from '@/types/types'
import { Checkbox } from '@/components/ui/checkbox'
// นำเข้า icon จาก lucide-vue-next (ถ้าต้องการใช้)
import { FileText } from 'lucide-vue-next'

// ขยาย interface TableMeta เพื่อเพิ่ม event onEdit สำหรับการแก้ไขข้อมูล
declare module '@tanstack/vue-table' {
  interface TableMeta<TData extends RowData> {
    onEdit?: (assessment: TData) => void
    onDelete?: (assessment: TData) => void
  }
}

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
  // เพิ่มคอลัมน์ใหม่: ชื่อความเสี่ยงระดับฝ่าย
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
      return h('div', { class: 'max-w-[200px] truncate font-medium' }, 
        divisionRisk?.risk_name || '-'
      )
    },
    enableSorting: true,
  },
  {
    accessorKey: "assessment_date", // วันที่ประเมิน
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'วันที่ประเมิน'
      })
    ),
    // แปลงวันที่ให้อยู่ในรูปแบบไทย
    cell: ({ row }) => {
      const date = new Date(row.getValue("assessment_date"))
      return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
  },
  {
    accessorKey: "likelihood_level", // ระดับโอกาสเกิด
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'ระดับโอกาสเกิด'
      })
    ),
  },
  {
    accessorKey: "impact_level", // ระดับผลกระทบ
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'ระดับผลกระทบ'
      })
    ),
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
    }
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
    cell: ({ row, table }) => {
      const assessment = row.original;
      const meta = table.options.meta as TableMeta<RiskAssessment>
      
      // ใช้ Generic DataTableDropDown component โดยส่งข้อมูลผ่าน data prop
      return h('div', { class: 'relative' }, [
        h(DataTableDropDown, {
          data: assessment, // ส่งข้อมูล assessment ผ่าน data prop
          menuLabel: 'ตัวเลือกการประเมินความเสี่ยง', // custom label สำหรับเมนู
          onExpand: () => {
            console.log('ขยายแถวข้อมูล', assessment.id)
            row.toggleExpanded()
          },
          onEdit: () => {
            console.log('แก้ไขข้อมูลความเสี่ยง:', assessment.id)
            meta?.onEdit?.(assessment)
          },
          onDelete: () => {
            console.log('ลบข้อมูลความเสี่ยง:', assessment.id)
            meta?.onDelete?.(assessment)
          },
        }),
      ]);
    },
  },
]
