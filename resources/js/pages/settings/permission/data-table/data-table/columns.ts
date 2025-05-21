import { h } from 'vue'
import { ColumnDef } from '@tanstack/vue-table'
import { Checkbox } from '@/components/ui/checkbox'
import DataTableColumnHeader from './DataTableColumnHeader.vue'
import type { UserPermission } from '@/pages/settings/permission/data-table/types/permission';

export const columns: ColumnDef<UserPermission>[] = [
  {
    accessorKey: "egat_id",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'ID'
      })
    ),
  },
  {
    accessorKey: "name",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Name'
      })
    ),
  },
  {
    accessorKey: "view",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'View'
      })
    ),
    cell: ({ row, table }) => h(Checkbox, {
      'modelValue': row.getValue("view"),
      'onUpdate:modelValue': (value) => {
        row.original.view = value;
        table.options.meta?.updateData(row.original.id, "view", value);
      },
      'aria-label': 'Can view permission',
    }),    
  },
  
  {
    accessorKey: "create",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Create'
      })
    ),
    cell: ({ row, table }) => h(Checkbox, {
      'modelValue': row.getValue("create"),
      'onUpdate:modelValue': (value) => {
        row.original.create = value;
        table.options.meta?.updateData(row.original.id, "create", value);
      },
      'aria-label': 'Can create permission',
    }),    
  },
  
  {
    accessorKey: "update",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Update'
      })
    ),
    cell: ({ row, table }) => h(Checkbox, {
      'modelValue': row.getValue("update"),
      'onUpdate:modelValue': (value) => {
        row.original.update = value;
        table.options.meta?.updateData(row.original.id, "update", value);
      },
      'aria-label': 'Can update permission',
    }),    
  },
  
  {
    accessorKey: "delete",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Delete'
      })
    ),
    cell: ({ row, table }) => h(Checkbox, {
      'modelValue': row.getValue("delete"),
      'onUpdate:modelValue': (value) => {
        row.original.delete = value;
        table.options.meta?.updateData(row.original.id, "delete", value);
      },
      'aria-label': 'Can delete permission',
    }),    
  },
]