import { h } from 'vue'
import { ColumnDef } from '@tanstack/vue-table'
import DataTableColumnHeader from '@/components/ui/data-table/DataTableColumnHeader.vue'
import type { OrganizationalRisk } from './organizational_risk.ts';

export const columns: ColumnDef<OrganizationalRisk>[] = [
  {
    accessorKey: "id",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'ID'
      })
    ),
  },
  {
    accessorKey: "year",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Year'
      })
    ),
  },
  {
    accessorKey: "risk_name",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Risk Name'
      })
    ),
  },
  {
    accessorKey: "description",
    header: ({ column }) => (
      h(DataTableColumnHeader, {
        column: column,
        title: 'Description'
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
        title: 'Created Date'
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
        title: 'Last Updated'
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
  }
]
