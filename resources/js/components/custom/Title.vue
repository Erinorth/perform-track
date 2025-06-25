<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'

interface Props {
  title: string
  subtitle?: string
  description?: string
  badge?: string
  badgeVariant?: 'default' | 'secondary' | 'destructive' | 'outline'
  size?: 'sm' | 'md' | 'lg' | 'xl'
  align?: 'left' | 'center' | 'right'
  class?: HTMLAttributes['class']
  showSeparator?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  align: 'left',
  badgeVariant: 'secondary',
  showSeparator: false
})

// คำนวณ class สำหรับขนาดต่างๆ
const titleClasses = computed(() => {
  const sizeClasses = {
    sm: 'text-lg font-semibold',
    md: 'text-xl font-semibold',
    lg: 'text-2xl font-bold',
    xl: 'text-3xl font-bold'
  }
  
  const alignClasses = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right'
  }
  
  return cn(
    sizeClasses[props.size],
    alignClasses[props.align],
    'leading-tight tracking-tight text-foreground'
  )
})

const subtitleClasses = computed(() => {
  const sizeClasses = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
    xl: 'text-xl'
  }
  
  const alignClasses = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right'
  }
  
  return cn(
    sizeClasses[props.size],
    alignClasses[props.align],
    'font-medium text-muted-foreground'
  )
})

const descriptionClasses = computed(() => {
  const alignClasses = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right'
  }
  
  return cn(
    'text-sm text-muted-foreground',
    alignClasses[props.align]
  )
})

const containerClasses = computed(() => {
  const alignClasses = {
    left: 'items-start',
    center: 'items-center',
    right: 'items-end'
  }
  
  return cn(
    'flex flex-col gap-1',
    alignClasses[props.align]
  )
})
</script>

<template>
  <div :class="cn(containerClasses, props.class)">
    <!-- Main Title Section -->
    <div :class="cn('flex items-center gap-2', props.align === 'center' ? 'justify-center' : props.align === 'right' ? 'justify-end' : 'justify-start')">
      <!-- Title -->
      <h1 :class="titleClasses">
        <slot name="title">
          {{ title }}
        </slot>
      </h1>
      
      <!-- Badge -->
      <Badge 
        v-if="badge" 
        :variant="badgeVariant"
        class="ml-2"
      >
        {{ badge }}
      </Badge>
      
      <!-- Custom slot for additional elements -->
      <slot name="actions" />
    </div>
    
    <!-- Subtitle -->
    <p 
      v-if="subtitle" 
      :class="subtitleClasses"
    >
      <slot name="subtitle">
        {{ subtitle }}
      </slot>
    </p>
    
    <!-- Description -->
    <p 
      v-if="description" 
      :class="descriptionClasses"
    >
      <slot name="description">
        {{ description }}
      </slot>
    </p>
    
    <!-- Custom content slot -->
    <slot />
    
    <!-- Separator -->
    <Separator 
      v-if="showSeparator" 
      class="mt-4 mb-2" 
    />
  </div>
</template>
