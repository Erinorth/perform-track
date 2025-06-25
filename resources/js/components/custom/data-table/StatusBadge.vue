<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'

// Define props
interface Props {
  status: string
}

defineProps<Props>()

// Define status variants และ labels
const variants: Record<string, 'default' | 'destructive' | 'secondary'> = {
  pending: 'secondary',
  processing: 'default',
  success: 'default',
  failed: 'destructive'
}

const colors = {
  pending: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-100',
  processing: 'bg-blue-100 text-blue-800 hover:bg-blue-100',
  success: 'bg-green-100 text-green-800 hover:bg-green-100',
  failed: 'bg-red-100 text-red-800 hover:bg-red-100'
}

const labels = {
  pending: 'รอดำเนินการ',
  processing: 'กำลังประมวลผล',
  success: 'สำเร็จ',
  failed: 'ล้มเหลว'
}
</script>

<template>
  <Badge
    :variant="variants[status] || 'secondary'"
    :class="cn('capitalize', colors[status as keyof typeof colors])"
  >
    {{ labels[status as keyof typeof labels] || status }}
  </Badge>
</template>
