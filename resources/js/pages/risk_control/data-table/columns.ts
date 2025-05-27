// ไฟล์: resources\js\pages\risk_control\data-table\columns.ts
// หน้านี้กำหนดคอลัมน์สำหรับ DataTable ของการควบคุมความเสี่ยง
// เพิ่ม event สำหรับลบ (delete), แก้ไข (edit), และเปลี่ยนสถานะ (toggle status) ข้อมูลใน action
// แสดงชื่อความเสี่ยงระดับฝ่าย, ประเภทการควบคุม, สถานะ, และผู้รับผิดชอบ
// ย้ายปุ่มย่อ/ขยายไปยังคอลัมน์ชื่อการควบคุม

import { h } from 'vue'
import { router } from '@inertiajs/vue3' // เพิ่มการนำเข้า router จาก inertiajs
import { ColumnDef, TableMeta, RowData } from '@tanstack/vue-table'
import { DataTableColumnHeader, DataTableDropDown } from '@/components/ui/data-table'
import type { RiskControl } from '@/types/risk-control'
import { Checkbox } from '@/components/ui/checkbox'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { ChevronDown, Shield, ShieldCheck, ShieldAlert, User } from 'lucide-vue-next'

// ขยาย interface TableMeta เพื่อเพิ่ม event สำหรับการจัดการข้อมูลการควบคุมความเสี่ยง
declare module '@tanstack/vue-table' {
  interface TableMeta<TData extends RowData> {
    onEdit?: (control: TData) => void
    onDelete?: (control: TData) => void
    onToggleStatus?: (control: TData) => void
    onViewDetails?: (control: TData) => void
  }
}

// ฟังก์ชันกำหนดป้ายกำกับประเภทการควบคุม
const getControlTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    'preventive': 'การป้องกัน',
    'detective': 'การตรวจจับ',
    'corrective': 'การแก้ไข',
    'compensating': 'การชดเชย'
  }
  return labels[type] || 'ไม่ระบุ'
}

// ฟังก์ชันกำหนดสีและสไตล์ตามประเภทการควบคุม
const getControlTypeStyle = (type: string): { variant: string, color: string } => {
  const styles: Record<string, { variant: string, color: string }> = {
    'preventive': { variant: 'default', color: 'bg-blue-100 text-blue-800' },
    'detective': { variant: 'secondary', color: 'bg-green-100 text-green-800' },
    'corrective': { variant: 'destructive', color: 'bg-orange-100 text-orange-800' },
    'compensating': { variant: 'outline', color: 'bg-purple-100 text-purple-800' }
  }
  return styles[type] || { variant: 'outline', color: 'bg-gray-100 text-gray-800' }
}

// ฟังก์ชันกำหนดสีและสไตล์ตามสถานะการควบคุม
const getStatusStyle = (status: string): { variant: string, color: string, icon: any } => {
  if (status === 'active') {
    return { 
      variant: 'default', 
      color: 'bg-green-100 text-green-800', 
      icon: ShieldCheck 
    }
  } else {
    return { 
      variant: 'secondary', 
      color: 'bg-gray-100 text-gray-800', 
      icon: ShieldAlert 
    }
  }
}

// ฟังก์ชันสำหรับกรองข้อมูลตามประเภทการควบคุม
const filterByControlType = (row: any, id: string, value: string) => {
  return value.includes(row.getValue(id))
}

// ฟังก์ชันสำหรับกรองข้อมูลตามสถานะ
const filterByStatus = (row: any, id: string, value: string) => {
  return value.includes(row.getValue(id))
}

// ฟังก์ชันจัดรูปแบบวันที่
const formatDateTime = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// กำหนด columns สำหรับ DataTable
export const columns: ColumnDef<RiskControl>[] = [
  {
    id: "select",
    header: ({ table }) => h(Checkbox, {
      'modelValue': table.getIsAllPageRowsSelected(),
      'onUpdate:modelValue': (value: boolean | "indeterminate") => table.toggleAllPageRowsSelected(!!value),
      'aria-label': 'เลือกทั้งหมด',
    }),
    cell: ({ row }) => h(Checkbox, {
      'modelValue': row.getIsSelected(),
      'onUpdate:modelValue': (value: boolean | "indeterminate") => row.toggleSelected(!!value),
      'aria-label': 'เลือกแถว',
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
  // คอลัมน์ชื่อการควบคุม - ย้ายปุ่มย่อ/ขยายมาไว้ที่นี่
  {
    id: "control_name",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'ชื่อการควบคุม'
      })
    ),
    // เพิ่ม accessorFn เพื่อให้ระบบสามารถเข้าถึงข้อมูลสำหรับการเรียงลำดับได้
    accessorFn: (row) => row.control_name || '',
    cell: ({ row }) => {
      const control = row.original
      console.log('แสดงชื่อการควบคุม:', control.control_name || 'ไม่มีข้อมูล')
      
      return h('div', { class: 'flex items-center gap-2' }, [
        // ปุ่มสามเหลี่ยมสำหรับย่อ/ขยาย
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          class: 'p-0 h-8 w-8 flex-shrink-0',
          onClick: (e: Event) => {
            e.stopPropagation() // ป้องกันการ bubble ของ event
            console.log('สลับการขยาย/ย่อแถว:', control.id)
            row.toggleExpanded() // สลับสถานะย่อ/ขยาย
          }
        }, () => h(ChevronDown, {
          class: `h-4 w-4 transition-transform duration-200 ${row.getIsExpanded() ? 'rotate-180' : ''}`,
        })),
        // แสดงชื่อการควบคุมและรายละเอียด
        h('div', { class: 'flex flex-col gap-1' }, [
          h('span', { 
            class: 'truncate font-medium',
            title: control.control_name // แสดง tooltip เมื่อ hover
          }, control.control_name || '-'),
          // แสดงรายละเอียดย่อ (ถ้ามี)
          control.description && h('span', {
            class: 'text-xs text-gray-500 truncate max-w-xs',
            title: control.description
          }, control.description.length > 50 ? 
            `${control.description.substring(0, 50)}...` : 
            control.description
          )
        ])
      ])
    },
    enableSorting: true,
  },
  // คอลัมน์ชื่อความเสี่ยงระดับฝ่าย
  {
    id: "division_risk_name",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'ความเสี่ยงระดับฝ่าย'
      })
    ),
    // เพิ่ม accessorFn เพื่อให้ระบบสามารถเข้าถึงข้อมูลสำหรับการเรียงลำดับได้
    accessorFn: (row) => row.division_risk?.risk_name || '',
    cell: ({ row }) => {
      const divisionRisk = row.original.division_risk
      console.log('แสดงชื่อความเสี่ยงระดับฝ่าย:', divisionRisk?.risk_name || 'ไม่มีข้อมูล')
      
      // แสดงชื่อความเสี่ยงระดับฝ่ายและองค์กร (ถ้ามี)
      return h('div', { class: 'flex flex-col gap-1' }, [
        h('span', { 
          class: 'truncate font-medium' 
        }, divisionRisk?.risk_name || '-'),
        // แสดงชื่อความเสี่ยงระดับองค์กร (ถ้ามี)
        divisionRisk?.organizational_risk && h('span', {
          class: 'text-xs text-gray-500 truncate',
          title: `องค์กร: ${divisionRisk.organizational_risk.risk_name}`
        }, `องค์กร: ${divisionRisk.organizational_risk.risk_name}`)
      ])
    },
    enableSorting: true,
  },
  // คอลัมน์ประเภทการควบคุม
  {
    accessorKey: "control_type", // ประเภทการควบคุม
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'ประเภทการควบคุม'
      })
    ),
    cell: ({ row }) => {
      const type = row.getValue("control_type") as string;
      
      if (!type) {
        return h('span', { class: 'text-gray-400' }, '-')
      }

      const typeLabel = getControlTypeLabel(type)
      const typeStyle = getControlTypeStyle(type)
      
      console.log(`แสดงประเภทการควบคุม: ${typeLabel} (${type})`);
      
      // สร้าง Badge แสดงประเภทการควบคุม
      return h(Badge, {
        variant: typeStyle.variant as any,
        class: `${typeStyle.color} gap-1`
      }, () => [
        h(Shield, { class: 'w-3 h-3' }),
        typeLabel
      ])
    },
    filterFn: filterByControlType,
    enableSorting: true,
  },
  // คอลัมน์สถานะการควบคุม
  {
    accessorKey: "status", // สถานะการควบคุม
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'สถานะ'
      })
    ),
    cell: ({ row }) => {
      const status = row.getValue("status") as string;
      const statusStyle = getStatusStyle(status)
      const statusLabel = status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน'
      
      console.log(`แสดงสถานะการควบคุม: ${statusLabel} (${status})`);
      
      // สร้าง Badge แสดงสถานะ
      return h(Badge, {
        variant: statusStyle.variant as any,
        class: `${statusStyle.color} gap-1`
      }, () => [
        h(statusStyle.icon, { class: 'w-3 h-3' }),
        statusLabel
      ])
    },
    filterFn: filterByStatus,
    enableSorting: true,
  },
  // คอลัมน์ผู้รับผิดชอบ
  {
    accessorKey: "owner", // ผู้รับผิดชอบ
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'ผู้รับผิดชอบ'
      })
    ),
    cell: ({ row }) => {
      const owner = row.getValue("owner") as string
      
      if (!owner) {
        return h('span', { class: 'text-gray-400' }, '-')
      }
      
      console.log(`แสดงผู้รับผิดชอบ: ${owner}`);
      
      // แสดงผู้รับผิดชอบพร้อมไอคอน
      return h('div', { class: 'flex items-center gap-2' }, [
        h(User, { class: 'w-4 h-4 text-gray-400' }),
        h('span', { 
          class: 'truncate',
          title: owner
        }, owner)
      ])
    },
    enableSorting: true,
  },
  // คอลัมน์รายละเอียดการดำเนินการ
  {
    accessorKey: "implementation_details", // รายละเอียดการดำเนินการ
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'รายละเอียดการดำเนินการ'
      })
    ),
    // แสดงรายละเอียดไม่เกิน 50 ตัวอักษร ถ้ายาวให้เติม ...
    cell: ({ row }) => {
      const details = row.getValue("implementation_details") as string
      if (!details) return h('span', { class: 'text-gray-400' }, '-')
      
      const truncatedDetails = details.length > 50 ? `${details.substring(0, 50)}...` : details
      
      return h('span', {
        class: 'text-sm',
        title: details // แสดง tooltip เต็มเมื่อ hover
      }, truncatedDetails)
    },
    enableHiding: true // สามารถซ่อนคอลัมน์นี้ได้
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
      const createdAt = row.getValue("created_at") as string
      return h('span', { 
        class: 'text-sm text-gray-600',
        title: formatDateTime(createdAt)
      }, formatDateTime(createdAt))
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
      const updatedAt = row.getValue("updated_at") as string
      return h('span', { 
        class: 'text-sm text-gray-600',
        title: formatDateTime(updatedAt)
      }, formatDateTime(updatedAt))
    },
    enableHiding: true, // สามารถซ่อนคอลัมน์นี้ได้
  },
  {
    id: "actions",
    enableHiding: false,
    
    // แก้ไขการใช้ DataTableDropDown ที่ต้องการ property control_name
    cell: ({ row, table }) => {
      const control = row.original;
      const meta = table.options.meta as TableMeta<RiskControl>
      
      // สร้าง object ที่มี control_name เพื่อให้ตรงกับ prop ที่ DataTableDropDown ต้องการ
      const dropdownData = {
        id: control.id,
        risk_name: control.control_name || 'ไม่มีชื่อการควบคุม', // ใช้ control_name แทน risk_name
        control_name: control.control_name || 'ไม่มีชื่อการควบคุม'
        // เพิ่ม properties อื่นๆ จาก control ที่จำเป็น
      };
      
      // ใช้ dropdownData แทน control
      return h('div', { class: 'relative' }, [
        h(DataTableDropDown, {
          data: dropdownData, 
          menuLabel: 'ตัวเลือกการควบคุมความเสี่ยง',
          onExpand: () => {
            console.log('ดูรายละเอียดการควบคุมความเสี่ยง', control.id)
            // เรียกใช้ meta function สำหรับดูรายละเอียด
            meta?.onViewDetails?.(control)
          },
          onEdit: () => {
            console.log('แก้ไขข้อมูลการควบคุมความเสี่ยง:', control.id)
            meta?.onEdit?.(control)
          },
          onDelete: () => {
            console.log('ลบข้อมูลการควบคุมความเสี่ยง:', control.id)
            meta?.onDelete?.(control)
          },
          // เพิ่มตัวเลือกสำหรับเปลี่ยนสถานะ
          onToggleStatus: () => {
            console.log('เปลี่ยนสถานะการควบคุมความเสี่ยง:', control.id)
            meta?.onToggleStatus?.(control)
          },
        }),
      ]);
    }
  },
]

// ส่งออกฟังก์ชันเสริมสำหรับใช้ใน component อื่น
export {
  getControlTypeLabel,
  getControlTypeStyle,
  getStatusStyle,
  filterByControlType,
  filterByStatus,
  formatDateTime
}
