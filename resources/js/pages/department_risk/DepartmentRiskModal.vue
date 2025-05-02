<!-- 
  ไฟล์: resources\js\pages\department_risk\DepartmentRiskModal.vue
  Modal สำหรับการเพิ่มและแก้ไขข้อมูลความเสี่ยงระดับสายงาน
  รองรับการใช้งานบนอุปกรณ์ทุกขนาดหน้าจอ
-->

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { getYears } from '@/lib/utils'; // ฟังก์ชันสำหรับสร้างช่วงปี
import type { DepartmentRisk } from '@/features/department_risk/department_risk';
import type { OrganizationalRisk } from '@/features/organizational_risk/organizational_risk';

// กำหนด Props ที่รับเข้ามา
const props = defineProps<{
  show: boolean; // สถานะการแสดง Modal
  risk?: DepartmentRisk; // ข้อมูลความเสี่ยงที่ต้องการแก้ไข (ถ้ามี)
  organizationalRisks: OrganizationalRisk[]; // รายการความเสี่ยงองค์กรเพื่อให้เลือก
}>();

// กำหนด Events ที่จะส่งออก
const emit = defineEmits<{
  'update:show': [value: boolean]; // Event สำหรับอัปเดตสถานะการแสดง Modal
  'saved': []; // Event แจ้งเมื่อบันทึกสำเร็จ
}>();

// สร้างช่วงปีสำหรับ dropdown เลือกปี
const yearOptions = getYears(2020, new Date().getFullYear() + 5);

// สร้างแบบฟอร์มเปล่าสำหรับกรณีสร้างใหม่
const emptyForm = {
  risk_name: '',
  description: '',
  year: new Date().getFullYear().toString(),
  organizational_risk_id: ''
};

// สร้าง Ref เพื่อเก็บข้อมูลฟอร์ม
const form = ref({ ...emptyForm });

// สร้าง Ref เพื่อเก็บสถานะการโหลดข้อมูล
const loading = ref(false);

// ตรวจสอบว่ากำลังสร้างข้อมูลใหม่หรือแก้ไขข้อมูลเดิม
const isEditing = computed(() => !!props.risk?.id);

// ชื่อหัวข้อ Modal ขึ้นอยู่กับโหมดการทำงาน
const modalTitle = computed(() => 
  isEditing.value ? 'แก้ไขความเสี่ยงสายงาน' : 'เพิ่มความเสี่ยงสายงานใหม่'
);

// ติดตามการเปลี่ยนแปลงข้อมูลความเสี่ยงที่ส่งมา
watch(
  () => props.risk,
  (newRisk) => {
    if (newRisk) {
      // กรณีแก้ไข: นำข้อมูลที่มีอยู่มากำหนดในฟอร์ม
      form.value = {
        risk_name: newRisk.risk_name,
        description: newRisk.description,
        year: newRisk.year.toString(),
        organizational_risk_id: newRisk.organizational_risk_id?.toString() || ''
      };
    } else {
      // กรณีสร้างใหม่: รีเซ็ตฟอร์มให้เป็นค่าเริ่มต้น
      form.value = { ...emptyForm };
    }
  },
  { immediate: true } // ทำงานทันทีเมื่อ component ถูกสร้าง
);

// ฟังก์ชันเมื่อกดยกเลิกหรือปิด Modal
const handleCancel = () => {
  emit('update:show', false);
};

// ฟังก์ชันเมื่อกดบันทึกข้อมูล
const handleSubmit = () => {
  loading.value = true;
  
  // เตรียมข้อมูลที่จะส่งไปยัง server
  const formData = {
    ...form.value,
    year: parseInt(form.value.year),
    organizational_risk_id: form.value.organizational_risk_id === 'null' ? null : form.value.organizational_risk_id
  };

  const route = isEditing.value 
    ? `department-risks.update` 
    : `department-risks.store`;
  
  const method = isEditing.value ? 'put' : 'post';
  
  // กำหนด URL และพารามิเตอร์ตามโหมดการทำงาน
  const routeParams = isEditing.value ? { departmentRisk: props.risk?.id } : {};
  
  // บันทึก log ข้อมูลสำหรับตรวจสอบ
  console.log(`กำลังบันทึกข้อมูลความเสี่ยงสายงาน: ${isEditing.value ? 'แก้ไข' : 'เพิ่มใหม่'}`, formData);
  
  // ส่งข้อมูลไปยัง server ด้วย Inertia
  router[method](route(routeParams), formData, {
    onSuccess: () => {
      // แสดงข้อความแจ้งเตือนเมื่อบันทึกสำเร็จ
      toast.success(isEditing.value 
        ? 'แก้ไขข้อมูลความเสี่ยงสายงานเรียบร้อยแล้ว' 
        : 'เพิ่มข้อมูลความเสี่ยงสายงานเรียบร้อยแล้ว'
      );
      loading.value = false;
      emit('update:show', false); // ปิด Modal
      emit('saved'); // แจ้งว่าบันทึกสำเร็จ
    },
    onError: (errors) => {
      // แสดงข้อความแจ้งเตือนเมื่อเกิดข้อผิดพลาด
      const errorMessages = Object.values(errors).flat().join('\n');
      toast.error(`เกิดข้อผิดพลาด: ${errorMessages}`);
      loading.value = false;
    }
  });
};
</script>

<template>
  <Dialog :open="show" @update:open="emit('update:show', $event)">
    <DialogContent class="sm:max-w-md md:max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ modalTitle }}</DialogTitle>
        <DialogDescription>กรอกข้อมูลความเสี่ยงระดับสายงาน</DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- ชื่อความเสี่ยง -->
        <div class="space-y-2">
          <Label for="risk_name">ชื่อความเสี่ยง</Label>
          <Input 
            id="risk_name" 
            v-model="form.risk_name" 
            placeholder="ระบุชื่อความเสี่ยง"
            required
          />
        </div>

        <!-- ความเสี่ยงองค์กรที่เกี่ยวข้อง -->
        <div class="space-y-2">
          <Label for="organizational_risk">ความเสี่ยงองค์กรที่เกี่ยวข้อง</Label>
          <Select v-model="form.organizational_risk_id">
            <SelectTrigger>
              <SelectValue placeholder="เลือกความเสี่ยงองค์กรที่เกี่ยวข้อง (ถ้ามี)" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="null">-- ไม่เชื่อมโยงกับความเสี่ยงองค์กร --</SelectItem>
              <SelectItem 
                v-for="orgRisk in props.organizationalRisks" 
                :key="orgRisk.id" 
                :value="orgRisk.id.toString()"
              >
                {{ orgRisk.year }} - {{ orgRisk.risk_name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- ปี -->
        <div class="space-y-2">
          <Label for="year">ปี</Label>
          <Select v-model="form.year" required>
            <SelectTrigger>
              <SelectValue placeholder="เลือกปี" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem 
                v-for="year in yearOptions" 
                :key="year" 
                :value="year.toString()"
              >
                {{ year }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- รายละเอียด -->
        <div class="space-y-2">
          <Label for="description">รายละเอียด</Label>
          <Textarea 
            id="description" 
            v-model="form.description" 
            placeholder="ระบุรายละเอียดความเสี่ยง"
            required
            rows="5"
          />
        </div>

        <DialogFooter class="flex justify-between sm:justify-end gap-2 pt-2">
          <Button type="button" variant="outline" @click="handleCancel" :disabled="loading">
            ยกเลิก
          </Button>
          <Button type="submit" :loading="loading">
            {{ isEditing ? 'บันทึกการแก้ไข' : 'บันทึก' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
