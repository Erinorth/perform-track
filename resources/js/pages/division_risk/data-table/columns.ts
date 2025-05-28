/* 
  ไฟล์: resources\js\features\division_risk\columns.ts
  หน้านี้กำหนดคอลัมน์สำหรับ DataTable ของ Division Risk
  เพิ่ม event สำหรับลบ (delete) ข้อมูลใน action
  เพิ่ม column สำหรับแสดง Organizational Risk
*/

import { h } from 'vue'
import { router } from '@inertiajs/vue3' // เพิ่มการนำเข้า router จาก inertiajs
import { ColumnDef, TableMeta, RowData } from '@tanstack/vue-table'
import { DataTableColumnHeader, DataTableDropDown } from '@/components/ui/data-table'
import type { DivisionRisk, OrganizationalRisk } from '@/types/types'
import { Checkbox } from '@/components/ui/checkbox'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge' // เพิ่ม import Badge
import { ChevronDown, Building2 } from 'lucide-vue-next' // เพิ่ม Building2 ใน import

// ขยาย interface TableMeta เพื่อเพิ่ม event onEdit สำหรับการแก้ไขข้อมูล
declare module '@tanstack/vue-table' {
  interface TableMeta<TData extends RowData> {
    onEdit?: (division_risk: TData) => void
    onDelete?: (division_risk: TData) => void
  }
}

// Helper function สำหรับกำหนดสี badge ตามระดับความเสี่ยง
function getRiskLevelVariant(riskLevel: string): 'default' | 'secondary' | 'destructive' | 'outline' {
  switch (riskLevel?.toLowerCase()) {
    case 'high':
    case 'สูง':
      return 'destructive'
    case 'medium':
    case 'กลาง':
      return 'default'
    case 'low':
    case 'ต่ำ':
      return 'secondary'
    default:
      return 'outline'
  }
}

// กำหนด columns สำหรับ DataTable
export const columns: ColumnDef<DivisionRisk>[] = [
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
    meta: {
      className: 'w-12' // กำหนดความกว้างให้เหมาะสม
    }
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
    meta: {
      className: 'w-16 text-center' // กำหนดความกว้างให้เหมาะสม
    }
  },
  {
    // เพิ่ม column สำหรับแสดง Organizational Risk
    accessorKey: "organizational_risk", // ชื่อ field ที่เชื่อมโยงกับ organizational risk
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Organizational Risk'
      })
    ),
    cell: ({ row }) => {
      const organizational_risk = row.getValue("organizational_risk") as OrganizationalRisk | null
      
      // ตรวจสอบว่ามีข้อมูล organizational risk หรือไม่
      if (!organizational_risk) {
        return h('div', { class: 'flex items-center gap-2 text-muted-foreground' }, [
          h('span', { class: 'text-sm' }, 'ไม่ระบุ')
        ])
      }

      // แสดงชื่อ organizational risk พร้อม badge สำหรับระดับความเสี่ยง (ถ้ามี)
      return h('div', { class: 'flex items-center gap-2' }, [
        h('div', { class: 'flex flex-col gap-1 min-w-0' }, [
          // ชื่อ organizational risk
          h('span', { 
            class: 'text-sm font-medium truncate',
            title: organizational_risk.risk_name // แสดง tooltip เมื่อ hover
          }, organizational_risk.risk_name),
          // แสดง badge สำหรับระดับความเสี่ยง (ถ้ามี property risk_level)
          (organizational_risk as any).risk_level ? h(Badge, {
            variant: getRiskLevelVariant((organizational_risk as any).risk_level),
            class: 'text-xs w-fit'
          }, () => (organizational_risk as any).risk_level) : null
        ])
      ])
    },
    // เปิดใช้งานการเรียงลำดับโดยใช้ชื่อ organizational risk
    sortingFn: (rowA, rowB) => {
      const orgRiskA = rowA.getValue("organizational_risk") as OrganizationalRisk | null
      const orgRiskB = rowB.getValue("organizational_risk") as OrganizationalRisk | null
      const nameA = orgRiskA?.risk_name || ""
      const nameB = orgRiskB?.risk_name || ""
      return nameA.localeCompare(nameB, 'th')
    },
    meta: {
      className: 'min-w-56 max-w-72' // กำหนดความกว้าง responsive
    }
  },
  {
    accessorKey: "risk_name", // ชื่อความเสี่ยง
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Risk Name'
      })
    ),
    // เพิ่มปุ่มสามเหลี่ยมย่อ/ขยายพร้อมกับชื่อความเสี่ยง
    cell: ({ row }) => {
      const risk_name = row.getValue("risk_name") as string
      
      return h('div', { class: 'flex items-center gap-2' }, [
        // ปุ่มสามเหลี่ยมสำหรับย่อ/ขยาย
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          class: 'p-0 h-8 w-8 shrink-0',
          onClick: (e: Event) => {
            e.stopPropagation() // ป้องกันการ bubble ของ event
            // บันทึกการทำงานของปุ่ม (ถ้าต้องการ)
            console.log('Toggle expand for division risk:', row.original.id)
            row.toggleExpanded() // สลับสถานะย่อ/ขยาย
          }
        }, () => h(ChevronDown, {
          class: `h-4 w-4 transition-transform ${row.getIsExpanded() ? 'rotate-180' : ''}`,
        })),
        // ชื่อความเสี่ยง
        h('span', { class: 'truncate font-medium' }, risk_name)
      ])
    },
    meta: {
      className: 'min-w-48' // กำหนดความกว้างขั้นต่ำ
    }
  },
  {
    accessorKey: "description", // รายละเอียดความเสี่ยง
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Description'
      })
    ),
    // แสดงรายละเอียดไม่เกิน 50 ตัวอักษร ถ้ายาวให้เติม ...
    cell: ({ row }) => {
      const description = row.getValue("description") as string
      const truncatedText = description?.length > 50 ? `${description.substring(0, 50)}...` : description
      
      return h('span', { 
        class: 'text-sm text-muted-foreground',
        title: description // แสดง tooltip เต็มเมื่อ hover
      }, truncatedText || '-')
    },
    meta: {
      className: 'min-w-40 max-w-60' // จำกัดความกว้างสำหรับ description
    }
  },
  {
    accessorKey: "created_at", // วันที่สร้างข้อมูล
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Created Date'
      })
    ),
    // แปลงวันที่ให้อยู่ในรูปแบบไทย พร้อมแสดงเวลา
    cell: ({ row }) => {
      const dateValue = row.getValue("created_at")
      if (!dateValue) return h('span', { class: 'text-muted-foreground' }, '-')
      
      const date = new Date(dateValue as string)
      return h('div', { class: 'text-sm' }, [
        h('div', {}, date.toLocaleDateString('th-TH', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        })),
        h('div', { class: 'text-xs text-muted-foreground' }, date.toLocaleTimeString('th-TH', {
          hour: '2-digit',
          minute: '2-digit'
        }))
      ])
    },
    enableHiding: true, // สามารถซ่อนคอลัมน์นี้ได้
    meta: {
      className: 'w-32 text-center' // กำหนดความกว้างคงที่
    }
  },
  {
    accessorKey: "updated_at", // วันที่แก้ไขล่าสุด
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Last Updated'
      })
    ),
    // แปลงวันที่ให้อยู่ในรูปแบบไทย พร้อมแสดงเวลา
    cell: ({ row }) => {
      const dateValue = row.getValue("updated_at")
      if (!dateValue) return h('span', { class: 'text-muted-foreground' }, '-')
      
      const date = new Date(dateValue as string)
      return h('div', { class: 'text-sm' }, [
        h('div', {}, date.toLocaleDateString('th-TH', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        })),
        h('div', { class: 'text-xs text-muted-foreground' }, date.toLocaleTimeString('th-TH', {
          hour: '2-digit',
          minute: '2-digit'
        }))
      ])
    },
    enableHiding: true, // สามารถซ่อนคอลัมน์นี้ได้
    meta: {
      className: 'w-32 text-center' // กำหนดความกว้างคงที่
    }
  },
  {
    id: "actions",
    enableHiding: false,
    cell: ({ row, table }) => {
      const division_risk = row.original;
      const meta = table.options.meta as TableMeta<DivisionRisk>
      
      // ใช้ Generic DataTableDropDown component โดยส่งข้อมูลผ่าน data prop
      return h('div', { class: 'relative' }, [
        h(DataTableDropDown, {
          data: division_risk, // ส่งข้อมูล division_risk ผ่าน data prop
          menuLabel: 'ตัวเลือกความเสี่ยงฝ่าย', // custom label สำหรับเมนู
          onExpand: () => {
            console.log('กำลังเปิดรายละเอียดความเสี่ยง ID:', division_risk.id);
            
            // นำทางไปยังหน้า show โดยใช้ ID ของความเสี่ยง
            router.visit(`/division-risks/${division_risk.id}`, {
              data: { 
                previousPage: window.location.href,
                source: 'data-table'
              },
              preserveState: true
            });
          },
          onEdit: () => {
            console.log('กำลังแก้ไขความเสี่ยง ID:', division_risk.id)
            meta?.onEdit?.(division_risk)
          },
          onDelete: () => {
            console.log('กำลังลบความเสี่ยง ID:', division_risk.id)
            meta?.onDelete?.(division_risk)
          },
        }),
      ]);
    },
    meta: {
      className: 'w-16' // กำหนดความกว้างให้เหมาะสมกับปุ่ม actions
    }
  },
]
