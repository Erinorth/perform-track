/* 
  ไฟล์: resources\js\features\division_risk\columns.ts
  หน้านี้กำหนดคอลัมน์สำหรับ DataTable ของ Division Risk
  เพิ่ม event สำหรับลบ (delete) ข้อมูลใน action
*/

import { h } from 'vue'
import { ColumnDef, TableMeta, RowData } from '@tanstack/vue-table'
import { DataTableColumnHeader, DataTableDropDown } from '@/components/ui/data-table'
import type { DivisionRisk } from '@/types/types'
import { Checkbox } from '@/components/ui/checkbox'

// ขยาย interface TableMeta เพื่อเพิ่ม event onEdit สำหรับการแก้ไขข้อมูล
declare module '@tanstack/vue-table' {
  interface TableMeta<TData extends RowData> {
    onEdit?: (division_risk: TData) => void
    onDelete?: (division_risk: TData) => void
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
  {
    accessorKey: "risk_name", // ชื่อความเสี่ยง
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Risk Name'
      })
    ),
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
      return description.length > 50 ? `${description.substring(0, 50)}...` : description
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
        title: 'Last Updated'
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
      const division_risk = row.original;
      const meta = table.options.meta as TableMeta<DivisionRisk>
      
      // ใช้ Generic DataTableDropDown component โดยส่งข้อมูลผ่าน data prop
      return h('div', { class: 'relative' }, [
        h(DataTableDropDown, {
          data: division_risk, // ส่งข้อมูล division_risk ผ่าน data prop
          menuLabel: 'ตัวเลือกความเสี่ยงฝ่าย', // custom label สำหรับเมนู
          onExpand: () => {
            //logTableAction('expand', 'division_risk', division_risk.id)
            row.toggleExpanded()
          },
          onEdit: () => {
            //logTableAction('edit', 'division_risk', division_risk.id)
            meta?.onEdit?.(division_risk)
          },
          onDelete: () => {
            //logTableAction('delete', 'division_risk', division_risk.id)
            meta?.onDelete?.(division_risk)
          },
        }),
      ]);
    },
  },
]
