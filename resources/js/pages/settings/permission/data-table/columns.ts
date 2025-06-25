import { h } from 'vue'
import { ColumnDef } from '@tanstack/vue-table'
import { Checkbox } from '@/components/ui/checkbox'
import DataTableColumnHeader from '@/components/custom/data-table/DataTableColumnHeader.vue'
import type { UserPermission } from '@/types/permission'
import { toast } from 'vue-sonner'

// ประกาศ module สำหรับ table meta
declare module '@tanstack/table-core' {
  interface TableMeta<TData> {
    updateData: (id: string | number, key: string, value: boolean) => void
  }
}

// สร้าง type helper สำหรับ ColumnDef เพื่อหลีกเลี่ยง TypeScript error
// eslint-disable-next-line @typescript-eslint/no-explicit-any
type GenericColumnDef<T> = ColumnDef<T, any>


// สร้างคอลัมน์ข้อความ
const createTextColumn = (key: 'egat_id' | 'name', title: string): ColumnDef<UserPermission> => ({
  accessorKey: key,
  header: ({ column }) => h(DataTableColumnHeader, { column, title })
})

// สร้างคอลัมน์สิทธิ์
const createPermissionColumn = (permission: 'view' | 'create' | 'update' | 'delete'): GenericColumnDef<UserPermission> => ({
  accessorKey: permission,
  header: ({ column }) => h(DataTableColumnHeader, {
    column,
    title: permission.charAt(0).toUpperCase() + permission.slice(1)
  }),
  cell: ({ row, table }) => {
    const value = Boolean(row.getValue(permission))
    return h(Checkbox, {
      'modelValue': value,
      'onUpdate:modelValue': function(value: boolean | 'indeterminate') {
        if (typeof value === 'boolean') {
          try {
            row.original[permission] = value
            table.options.meta?.updateData(row.original.id, permission, value)
          } catch (error) {
            toast.error(`ไม่สามารถอัพเดทสิทธิ์ ${permission} ได้`, {
              description: `เกิดข้อผิดพลาด: ${error instanceof Error ? error.message : 'ไม่ทราบสาเหตุ'}`
            })
          }
        }
      },
      'aria-label': `Can ${permission} permission`,
    })
  }
})

// ส่งออกคอลัมน์ทั้งหมด - ใช้ GenericColumnDef แทน ColumnDef
export const columns: ColumnDef<UserPermission>[] = [
  // สร้างคอลัมน์ข้อมูลพื้นฐาน
  createTextColumn('egat_id', 'ID'),
  createTextColumn('name', 'Name'),
  createPermissionColumn('view'),
  createPermissionColumn('create'),
  createPermissionColumn('update'),
  createPermissionColumn('delete'),
]
