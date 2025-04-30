<!-- 
  ไฟล์: resources\js\pages\organizational_risk\OrganizationalRiskModal.vue
  Modal สำหรับเพิ่มและแก้ไขข้อมูลความเสี่ยงระดับองค์กร
  ใช้ shadcn-vue components และ Inertia.js สำหรับการทำงานกับ Laravel backend
-->

<script setup lang="ts">
// นำเข้า APIs จาก Vue และ libraries ที่จำเป็น
import { ref, computed, watch } from 'vue';                 // APIs หลักจาก Vue 3
import { useForm } from '@inertiajs/vue3';                  // ใช้สำหรับจัดการฟอร์มและส่งข้อมูลไปยัง Laravel
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';  // Components สำหรับ Modal
import { Button } from '@/components/ui/button';            // Component ปุ่ม
import { Input } from '@/components/ui/input';              // Component input field
import { Textarea } from '@/components/ui/textarea';        // Component สำหรับข้อความหลายบรรทัด
import { Label } from '@/components/ui/label';              // Component ป้ายกำกับ
import { toast } from 'vue-sonner';                         // ใช้สำหรับแสดงข้อความแจ้งเตือน
import { SaveIcon, XIcon, CheckCircle2Icon, AlertCircleIcon, InfoIcon } from 'lucide-vue-next';          // ไอคอนสำหรับปุ่มต่างๆ
import type { OrganizationalRisk } from '@/features/organizational_risk/organizational_risk';  // Type ของข้อมูลความเสี่ยงองค์กร

// เพิ่มการนำเข้า composable
import { useOrganizationalRiskData } from '@/features/organizational_risk/useOrganizationalRiskData';

// กำหนด props ที่รับจาก parent component
const props = defineProps<{
  show: boolean;                   // สถานะการแสดง/ซ่อน Modal
  risk?: OrganizationalRisk;       // ข้อมูลความเสี่ยงที่ต้องการแก้ไข (ถ้ามี)
  initialRisks: OrganizationalRisk[]; // เพิ่ม prop สำหรับส่งข้อมูลเริ่มต้นให้ composable
}>();

// กำหนด events ที่จะส่งไปยัง parent component
const emit = defineEmits<{
  (e: 'update:show', value: boolean): void;  // สำหรับอัปเดตสถานะการแสดง Modal (v-model)
  (e: 'saved'): void;                        // แจ้งเมื่อบันทึกข้อมูลสำเร็จ
}>();

// เรียกใช้ composable เพื่อจัดการข้อมูล
const { submitForm } = useOrganizationalRiskData(props.initialRisks || []);

// สร้าง computed properties สำหรับตรวจสอบโหมดการทำงานและตั้งชื่อ Modal
const isEditing = computed(() => !!props.risk?.id);  // ตรวจสอบว่าเป็นการแก้ไขหรือไม่ (มี id = แก้ไข, ไม่มี = เพิ่มใหม่)
const modalTitle = computed(() => isEditing.value ? 'แก้ไขความเสี่ยงองค์กร' : 'เพิ่มความเสี่ยงองค์กร');  // ตั้งชื่อ Modal ตามโหมด

// สร้าง form สำหรับจัดการข้อมูลและการส่งไปยัง backend
const form = useForm({
  risk_name: props.risk?.risk_name ?? '',     // ชื่อความเสี่ยง (ใช้ค่าจาก props หรือค่าว่าง)
  description: props.risk?.description ?? '',  // รายละเอียดความเสี่ยง
  year: props.risk?.year ?? new Date().getFullYear(),  // ปี (ใช้ค่าจาก props หรือปีปัจจุบัน)
});

// ใช้ watch เพื่อรีเซ็ตฟอร์มเมื่อ Modal เปิดขึ้น
// เมื่อสถานะ show เปลี่ยน จะตรวจสอบและดำเนินการตามเงื่อนไข
watch(() => props.show, (newVal) => {
  if (newVal && props.risk) {
    // กรณี Modal เปิดและมีข้อมูลความเสี่ยงส่งมา (โหมดแก้ไข)
    form.risk_name = props.risk.risk_name;
    form.description = props.risk.description;
    form.year = props.risk.year;
  } else if (newVal) {
    // กรณี Modal เปิดแต่ไม่มีข้อมูลความเสี่ยงส่งมา (โหมดเพิ่มใหม่)
    form.reset();  // รีเซ็ตฟอร์มเป็นค่าเริ่มต้น
    form.year = new Date().getFullYear();  // กำหนดปีเป็นปีปัจจุบัน
  }
});

// ฟังก์ชันสำหรับปิด Modal
const closeModal = () => {
  emit('update:show', false);  // ส่ง event เพื่อเปลี่ยนสถานะการแสดง Modal เป็น false
};

// ฟังก์ชันตรวจสอบข้อมูลก่อนส่ง
const validateForm = () => {
  let isValid = true;
  const errors = [];

  if (!form.risk_name.trim()) {
    errors.push('กรุณาระบุชื่อความเสี่ยง');
    isValid = false;
  }
  
  if (!form.description.trim()) {
    errors.push('กรุณาระบุรายละเอียดความเสี่ยง');
    isValid = false;
  }

  if (!form.year || form.year < 2000 || form.year > 2100) {
    errors.push('กรุณาระบุปีให้ถูกต้อง (2000-2100)');
    isValid = false;
  }

  if (!isValid) {
    toast.warning('กรุณากรอกข้อมูลให้ครบถ้วน', {
      icon: InfoIcon,
      description: errors.join(', '),
      duration: 4000,
      position: 'top-center',
      closeButton: true
    });
  }

  return isValid;
};

// ฟังก์ชันสำหรับส่งฟอร์มโดยใช้ composable
const handleSubmit = async () => {
  // ตรวจสอบความถูกต้องของข้อมูลก่อนส่ง
  if (!validateForm()) return;
  
  try {
    // เรียกใช้ฟังก์ชัน submitForm จาก composable
    await submitForm(
      {
        risk_name: form.risk_name,
        description: form.description,
        year: form.year
      },
      isEditing.value ? props.risk?.id : undefined,
      () => {
        // ฟังก์ชัน callback เมื่อบันทึกสำเร็จ
        closeModal();
        emit('saved');
      }
    );
  } catch (error) {
    console.error('เกิดข้อผิดพลาดระหว่างการบันทึกข้อมูล:', error);
  }
};
</script>

<template>
  <!-- Dialog component จาก shadcn-vue -->
  <Dialog :open="show" @update:open="(val) => emit('update:show', val)">
    <DialogContent class="sm:max-w-[550px]">
      <!-- ส่วนหัวของ Modal แสดงชื่อและคำอธิบาย -->
      <DialogHeader>
        <DialogTitle>{{ modalTitle }}</DialogTitle>
        <!-- คำอธิบายสำหรับ screen reader แต่ซ่อนจากผู้ใช้ทั่วไป (sr-only) -->
        <DialogDescription class="sr-only">รายละเอียดฟอร์มสำหรับการจัดการความเสี่ยงองค์กร</DialogDescription>
      </DialogHeader>
      
      <!-- ฟอร์มสำหรับกรอกข้อมูล -->
      <form @submit.prevent="handleSubmit" class="space-y-4 mt-4">
        <div class="grid gap-4 py-2">
          <!-- ฟิลด์สำหรับชื่อความเสี่ยง -->
          <div class="grid gap-2">
            <Label for="risk_name">ชื่อความเสี่ยง</Label>
            <Input 
              id="risk_name" 
              v-model="form.risk_name" 
              placeholder="ระบุชื่อความเสี่ยงองค์กร"
            />
            <!-- แสดงข้อความแจ้งเตือนเมื่อเกิดข้อผิดพลาด -->
            <p v-if="form.errors.risk_name" class="text-sm text-red-500">{{ form.errors.risk_name }}</p>
          </div>
          
          <!-- ฟิลด์สำหรับรายละเอียดความเสี่ยง -->
          <div class="grid gap-2">
            <Label for="description">รายละเอียด</Label>
            <Textarea 
              id="description" 
              v-model="form.description" 
              placeholder="รายละเอียดความเสี่ยง"
              rows="4"
            />
            <!-- แสดงข้อความแจ้งเตือนเมื่อเกิดข้อผิดพลาด -->
            <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</p>
          </div>
          
          <!-- ฟิลด์สำหรับปีของความเสี่ยง -->
          <div class="grid gap-2">
            <Label for="year">ปี</Label>
            <Input 
              id="year" 
              v-model="form.year" 
              type="number"
            />
            <!-- แสดงข้อความแจ้งเตือนเมื่อเกิดข้อผิดพลาด -->
            <p v-if="form.errors.year" class="text-sm text-red-500">{{ form.errors.year }}</p>
          </div>
        </div>
        
        <!-- ส่วนท้ายของ Modal แสดงปุ่มดำเนินการ -->
        <DialogFooter class="flex flex-col sm:flex-row items-center justify-end space-y-2 sm:space-y-0 sm:space-x-2">
          <!-- ปุ่มยกเลิก -->
          <Button type="button" variant="outline" @click="closeModal" class="w-full sm:w-auto flex items-center gap-2">
            <XIcon class="h-4 w-4" />
            <span>ยกเลิก</span>
          </Button>
          <!-- ปุ่มบันทึก/เพิ่ม (เปลี่ยนชื่อตามโหมดการทำงาน) -->
          <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto flex items-center gap-2">
            <SaveIcon class="h-4 w-4" />
            <span>{{ isEditing ? 'บันทึก' : 'เพิ่ม' }}</span>
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
