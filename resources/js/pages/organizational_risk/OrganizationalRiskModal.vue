<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { toast } from 'vue-sonner';
import { SaveIcon, XIcon } from 'lucide-vue-next';
import type { OrganizationalRisk } from '@/features/organizational_risk/organizational_risk';

const props = defineProps<{
  show: boolean;
  risk?: OrganizationalRisk;
}>();

const emit = defineEmits<{
  (e: 'update:show', value: boolean): void;
  (e: 'saved'): void;
}>();

const isEditing = computed(() => !!props.risk?.id);
const modalTitle = computed(() => isEditing.value ? 'แก้ไขความเสี่ยงองค์กร' : 'เพิ่มความเสี่ยงองค์กร');

const form = useForm({
  risk_name: props.risk?.risk_name ?? '',
  description: props.risk?.description ?? '',
  year: props.risk?.year ?? new Date().getFullYear(),
});

// Reset form when modal opens with new data
watch(() => props.show, (newVal) => {
  if (newVal && props.risk) {
    form.risk_name = props.risk.risk_name;
    form.description = props.risk.description;
    form.year = props.risk.year;
  } else if (newVal) {
    form.reset();
    form.year = new Date().getFullYear();
  }
});

const closeModal = () => {
  emit('update:show', false);
};

const submitForm = () => {
  if (isEditing.value) {
    form.put(`/organizational-risks/${props.risk?.id}`, {
      onSuccess: () => {
        toast.success('บันทึกข้อมูลสำเร็จ');
        closeModal();
        emit('saved');
      },
      onError: () => {
        toast.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
      }
    });
  } else {
    form.post('/organizational-risks', {
      onSuccess: () => {
        toast.success('เพิ่มข้อมูลสำเร็จ');
        closeModal();
        emit('saved');
      },
      onError: () => {
        toast.error('เกิดข้อผิดพลาดในการเพิ่มข้อมูล');
      }
    });
  }
};
</script>

<template>
  <Dialog :open="show" @update:open="(val) => emit('update:show', val)">
    <DialogContent class="sm:max-w-[550px]">
      <DialogHeader>
        <DialogTitle>{{ modalTitle }}</DialogTitle>
        <DialogDescription class="sr-only">รายละเอียดฟอร์มสำหรับการจัดการความเสี่ยงองค์กร</DialogDescription>
      </DialogHeader>
      
      <form @submit.prevent="submitForm" class="space-y-4 mt-4">
        <div class="grid gap-4 py-2">
          <div class="grid gap-2">
            <Label for="risk_name">ชื่อความเสี่ยง</Label>
            <Input 
              id="risk_name" 
              v-model="form.risk_name" 
              placeholder="ระบุชื่อความเสี่ยงองค์กร"
            />
            <p v-if="form.errors.risk_name" class="text-sm text-red-500">{{ form.errors.risk_name }}</p>
          </div>
          
          <div class="grid gap-2">
            <Label for="description">รายละเอียด</Label>
            <Textarea 
              id="description" 
              v-model="form.description" 
              placeholder="รายละเอียดความเสี่ยง"
              rows="4"
            />
            <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</p>
          </div>
          
          <div class="grid gap-2">
            <Label for="year">ปี</Label>
            <Input 
              id="year" 
              v-model="form.year" 
              type="number"
            />
            <p v-if="form.errors.year" class="text-sm text-red-500">{{ form.errors.year }}</p>
          </div>
        </div>
        
        <DialogFooter class="flex flex-col sm:flex-row items-center justify-end space-y-2 sm:space-y-0 sm:space-x-2">
          <Button type="button" variant="outline" @click="closeModal" class="w-full sm:w-auto flex items-center gap-2">
            <XIcon class="h-4 w-4" />
            <span>ยกเลิก</span>
          </Button>
          <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto flex items-center gap-2">
            <SaveIcon class="h-4 w-4" />
            <span>{{ isEditing ? 'บันทึก' : 'เพิ่ม' }}</span>
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
