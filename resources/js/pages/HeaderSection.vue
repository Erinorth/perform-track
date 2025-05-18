<!-- resources/js/components/HeaderSection.vue -->
<script setup lang="ts">
defineProps<{
  title: string;
  description?: string;
}>();
</script>

<template>
  <div class="flex flex-col space-y-2">
    <h1 class="text-2xl font-bold tracking-tight">{{ title }}</h1>
    <p v-if="description" class="text-muted-foreground">{{ description }}</p>
  </div>
</template>

<!--
  - HeaderSection.vue: Component สำหรับส่วนหัวของหน้า
  - แสดงหัวข้อและคำอธิบายประจำหน้า
  - สามารถปรับแต่งได้ผ่าน props
  - การใช้งาน: 
    <HeaderSection title="ชื่อหน้า" description="รายละเอียด" />
-->
