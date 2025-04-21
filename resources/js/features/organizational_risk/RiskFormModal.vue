<template>
    <Dialog :open="true" @update:open="$emit('close')">
      <DialogContent class="sm:max-w-[500px] md:max-w-[700px]">
        <DialogHeader>
          <DialogTitle>
            {{ isEdit ? 'แก้ไขความเสี่ยงระดับสายงาน' : 'เพิ่มความเสี่ยงระดับสายงานใหม่' }}
          </DialogTitle>
          <DialogDescription>
            กรอกข้อมูลความเสี่ยงให้ครบถ้วน เมื่อเสร็จแล้วคลิกที่ปุ่มบันทึก
          </DialogDescription>
        </DialogHeader>
        
        <form @submit.prevent="handleSubmit">
          <div class="grid gap-4 py-4">
            <div class="grid grid-cols-4 items-center gap-4">
              <Label for="year" class="text-right">ปีงบประมาณ <span class="text-red-500">*</span></Label>
              <div class="col-span-3">
                <Select 
                  v-model="formData.year" 
                  :options="yearOptions" 
                  placeholder="เลือกปีงบประมาณ"
                  required
                />
                <div v-if="errors.year" class="text-sm text-red-500 mt-1">{{ errors.year }}</div>
              </div>
            </div>
            
            <div class="grid grid-cols-4 items-center gap-4">
              <Label for="risk_name" class="text-right">ชื่อความเสี่ยง <span class="text-red-500">*</span></Label>
              <div class="col-span-3">
                <Input 
                  id="risk_name"
                  v-model="formData.risk_name"
                  placeholder="ระบุชื่อความเสี่ยง"
                  required
                />
                <div v-if="errors.risk_name" class="text-sm text-red-500 mt-1">{{ errors.risk_name }}</div>
              </div>
            </div>
            
            <div class="grid grid-cols-4 items-start gap-4">
              <Label for="description" class="text-right pt-2">คำอธิบาย <span class="text-red-500">*</span></Label>
              <div class="col-span-3">
                <Textarea
                  id="description"
                  v-model="formData.description"
                  placeholder="อธิบายรายละเอียดของความเสี่ยง"
                  rows="4"
                  required
                />
                <div v-if="errors.description" class="text-sm text-red-500 mt-1">{{ errors.description }}</div>
              </div>
            </div>
            
            <div class="grid grid-cols-4 items-center gap-4">
              <Label for="active" class="text-right">สถานะ</Label>
              <div class="col-span-3">
                <div class="flex items-center space-x-2">
                  <Switch 
                    id="active"
                    v-model="formData.active"
                  />
                  <Label for="active">{{ formData.active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}</Label>
                </div>
              </div>
            </div>
          </div>
          
          <DialogFooter>
            <Button type="button" variant="outline" @click="$emit('close')">ยกเลิก</Button>
            <Button type="submit" :disabled="isSubmitting">
              <Loader2Icon v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
              บันทึก
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </template>
  
  <script setup lang="ts">
  import { ref, computed, onMounted } from 'vue';
  import { Loader2Icon } from 'lucide-vue-next';
  
  // Props
  interface Props {
    risk: any | null;
    isEdit: boolean;
  }
  
  const props = defineProps<Props>();
  
  const emit = defineEmits(['close', 'save']);
  
  // State
  const formData = ref({
    risk_name: '',
    description: '',
    year: new Date().getFullYear() + 543, // ปีไทย
    active: true
  });
  
  const errors = ref({
    risk_name: '',
    description: '',
    year: ''
  });
  
  const isSubmitting = ref(false);
  
  // คำนวณปีย้อนหลัง 5 ปี และปีปัจจุบัน
  const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear() + 543; // ปีไทย
    return Array.from({ length: 6 }, (_, i) => ({
      label: `${currentYear - i}`,
      value: currentYear - i
    }));
  });
  
  // โหลดข้อมูลเดิมกรณีเป็นการแก้ไข
  onMounted(() => {
    if (props.isEdit && props.risk) {
      formData.value = { ...props.risk };
    }
  });
  
  // ฟังก์ชันบันทึกข้อมูล
  const handleSubmit = async () => {
    // รีเซ็ต errors
    errors.value = {
      risk_name: '',
      description: '',
      year: ''
    };
    
    // ตรวจสอบข้อมูล
    let hasError = false;
    
    if (!formData.value.risk_name) {
      errors.value.risk_name = 'กรุณาระบุชื่อความเสี่ยง';
      hasError = true;
    }
    
    if (!formData.value.description) {
      errors.value.description = 'กรุณาระบุคำอธิบายความเสี่ยง';
      hasError = true;
    }
    
    if (!formData.value.year) {
      errors.value.year = 'กรุณาเลือกปีงบประมาณ';
      hasError = true;
    }
    
    if (hasError) return;
    
    isSubmitting.value = true;
    
    try {
      // ส่งข้อมูลไปยัง Parent Component
      emit('save', formData.value);
    } catch (error) {
      console.error(error);
    } finally {
      isSubmitting.value = false;
    }
  };
  </script>
  