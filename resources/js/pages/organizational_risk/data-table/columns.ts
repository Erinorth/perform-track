/* 
  ไฟล์: resources\js\features\organizational_risk\columns.ts
  หน้านี้กำหนดคอลัมน์สำหรับ DataTable ของ Organizational Risk
  เพิ่มปุ่มสามเหลี่ยมย่อ/ขยายในคอลัมน์ risk_name
*/

import { h } from 'vue'
import { router } from '@inertiajs/vue3' // เพิ่มการนำเข้า router จาก inertiajs
import { ColumnDef, TableMeta, RowData } from '@tanstack/vue-table'
import { DataTableColumnHeader, DataTableDropDown } from '@/components/custom/data-table'
import type { OrganizationalRisk } from '@/types/types'
import { Checkbox } from '@/components/ui/checkbox'
import { Button } from '@/components/ui/button'
import { ChevronDown } from 'lucide-vue-next'

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
    // เพิ่มปุ่มสามเหลี่ยมย่อ/ขยายพร้อมกับชื่อความเสี่ยง
    cell: ({ row }) => {
      const risk_name = row.getValue("risk_name") as string
      
      return h('div', { class: 'flex items-center gap-2' }, [
        // ปุ่มสามเหลี่ยมสำหรับย่อ/ขยาย
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          class: 'p-0 h-8 w-8',
          onClick: (e: Event) => {
            e.stopPropagation() // ป้องกันการ bubble ของ event
            // บันทึกการทำงานของปุ่ม (ถ้าต้องการ)
            // logTableAction('expand', 'organizational_risk', row.original.id)
            row.toggleExpanded() // สลับสถานะย่อ/ขยาย
          }
        }, () => h(ChevronDown, {
          class: `h-4 w-4 transition-transform ${row.getIsExpanded() ? 'rotate-180' : ''}`,
        })),
        // ชื่อความเสี่ยง
        h('span', {}, risk_name)
      ])
    },
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
      const organization_risk = row.original;
      const meta = table.options.meta as TableMeta<OrganizationalRisk>
      
      // ใช้ Generic DataTableDropDown component โดยส่งข้อมูลผ่าน data prop
      // ลบ onExpand เนื่องจากย้ายไปไว้ที่คอลัมน์ risk_name แล้ว
      return h('div', { class: 'relative' }, [
        h(DataTableDropDown, {
          data: organization_risk, // ส่งข้อมูล organization_risk ผ่าน data prop
          menuLabel: 'ตัวเลือกความเสี่ยงองค์กร', // custom label สำหรับเมนู
          onExpand: () => {
            // เปลี่ยนจาก row.toggleExpanded() เป็น router.visit()
            // บันทึกการทำงาน (ถ้าต้องการ)
            console.log('กำลังเปิดรายละเอียดความเสี่ยง ID:', organization_risk.id);
            
            // นำทางไปยังหน้า show โดยใช้ ID ของความเสี่ยง
            router.visit(`/organizational-risks/${organization_risk.id}`, {
              data: { 
                previousPage: window.location.href,
                source: 'data-table'
              },
              preserveState: true
            });
          },
          onEdit: () => {
            //logTableAction('edit', 'organizational_risk', organization_risk.id)
            meta?.onEdit?.(organization_risk)
          },
          onDelete: () => {
            //logTableAction('delete', 'organizational_risk', organization_risk.id)
            meta?.onDelete?.(organization_risk)
          },
        }),
      ]);
    },
  },
]
