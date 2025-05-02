/* 
  ไฟล์: resources\js\features\organizational_risk\columns.ts
  หน้านี้กำหนดคอลัมน์สำหรับ DataTable ของ Organizational Risk
  เพิ่ม event สำหรับลบ (delete) ข้อมูลใน action
*/

import { h } from 'vue'
import { ColumnDef, TableMeta, RowData } from '@tanstack/vue-table'
import { DataTableColumnHeader } from '@/components/ui/data-table'
import type { OrganizationalRisk } from '@/types/types';
import DropdownAction from './DataTableDropDown.vue'

// ขยาย interface TableMeta เพื่อเพิ่ม event onEdit สำหรับการแก้ไขข้อมูล
declare module '@tanstack/vue-table' {
  interface TableMeta<TData extends RowData> {
    onEdit?: (organization_risk: TData) => void
    onDelete?: (organization_risk: TData) => void
  }
}

// กำหนด columns สำหรับ DataTable
export const columns: ColumnDef<OrganizationalRisk>[] = [
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
    accessorKey: "year", // ปีของความเสี่ยง
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Year'
      })
    ),
    // ฟังก์ชันกรองข้อมูลตามปี
    filterFn: (row, id, filterValues) => {
      if (!filterValues || filterValues.length === 0) return true;
      const rowValue = row.getValue(id);
      return filterValues.includes(rowValue?.toString());
    },
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
    id: "actions", // คอลัมน์สำหรับปุ่ม action ต่าง ๆ เช่น แก้ไข/ขยาย
    enableHiding: false, // ห้ามซ่อนคอลัมน์นี้
    // แสดง DropdownAction component สำหรับแต่ละแถว
    cell: ({ row, table }) => {
      const organization_risk = row.original;
      const meta = table.options.meta as TableMeta<OrganizationalRisk>
      
      return h('div', { class: 'relative' }, [
        h(DropdownAction, {
          organization_risk: organization_risk,
          onExpand: () => row.toggleExpanded(), // ฟังก์ชันขยายแถว
          onEdit: () => meta?.onEdit?.(organization_risk), // ฟังก์ชันแก้ไขข้อมูล
          onDelete: () => meta?.onDelete?.(organization_risk), // ฟังก์ชันลบข้อมูล 
        }),
      ]);
    },
  },
]
