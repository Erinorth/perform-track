import { h } from 'vue'
import { router } from '@inertiajs/vue3' 
import { ColumnDef, TableMeta, RowData } from '@tanstack/vue-table'
import { DataTableColumnHeader, DataTableDropDown } from '@/components/custom/data-table'
import type { OrganizationalRisk } from '@/types/types'
import { Button } from '@/components/ui/button'
import { ChevronDown } from 'lucide-vue-next'

// กำหนด interface สำหรับ TableMeta เพื่อกำหนด callback functions
declare module '@tanstack/vue-table' {
  interface TableMeta<TData extends RowData> {
    onEdit?: (organization_risk: TData) => void
    onDelete?: (organization_risk: TData) => void
  }
}

// กำหนดโครงสร้างคอลัมน์ของตารางความเสี่ยงระดับองค์กร
export const columns: ColumnDef<OrganizationalRisk>[] = [
  // ลบคอลัมน์ select (checkbox) ออกแล้ว
  {
    accessorKey: "id", 
    header: ({ column }) => (
      // ใช้ DataTableColumnHeader เพื่อให้สามารถเรียงลำดับได้
      h(DataTableColumnHeader, {
        column: column,
        title: 'ID'
      })
    ),
  },
  {
    accessorKey: "risk_name", 
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'ชื่อความเสี่ยง'
      })
    ),
    
    cell: ({ row }) => {
      const risk_name = row.getValue("risk_name") as string
      
      return h('div', { class: 'flex items-center gap-2' }, [
        // ปุ่มขยายแถวเพื่อดูรายละเอียดเพิ่มเติม
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          class: 'p-0 h-8 w-8',
          onClick: (e: Event) => {
            e.stopPropagation() 
            // สลับสถานะการขยายแถว
            row.toggleExpanded() 
          }
        }, () => h(ChevronDown, {
          class: `h-4 w-4 transition-transform ${row.getIsExpanded() ? 'rotate-180' : ''}`,
        })),
        
        h('span', {}, risk_name)
      ])
    },
  },
  {
    accessorKey: "description", 
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'รายละเอียด'
      })
    ),
    
    cell: ({ row }) => {
      const description = row.getValue("description") as string
      return description.length > 50 ? `${description.substring(0, 50)}...` : description
    }
  },
  {
    accessorKey: "created_at", 
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'วันที่สร้าง'
      })
    ),
    
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
    enableHiding: true 
  },
  {
    accessorKey: "updated_at", 
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'อัปเดตล่าสุด'
      })
    ),
    
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
    enableHiding: true, 
  },
  {
    id: "actions",
    enableHiding: false,
    cell: ({ row, table }) => {
      const organization_risk = row.original;
      const meta = table.options.meta as TableMeta<OrganizationalRisk>
      
      return h('div', { class: 'relative' }, [
        h(DataTableDropDown, {
          data: organization_risk, 
          menuLabel: 'ตัวเลือกความเสี่ยงองค์กร', 
          onExpand: () => {
            console.log('กำลังเปิดรายละเอียดความเสี่ยง ID:', organization_risk.id);
            
            // นำทางไปยังหน้ารายละเอียดความเสี่ยง
            router.visit(`/organizational-risks/${organization_risk.id}`, {
              data: { 
                previousPage: window.location.href,
                source: 'data-table'
              },
              preserveState: true
            });
          },
          onEdit: () => {
            // เรียกใช้ฟังก์ชันแก้ไขจาก meta
            meta?.onEdit?.(organization_risk)
          },
          onDelete: () => {
            // เรียกใช้ฟังก์ชันลบจาก meta
            meta?.onDelete?.(organization_risk)
          },
        }),
      ]);
    },
  },
]
