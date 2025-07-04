<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

// กำหนด props ที่รับมาจาก controller
defineProps<{
    status?: string;
}>();

// สร้าง form instance สำหรับจัดการข้อมูล
const form = useForm({
    egatid: '',
    password: '',
    remember: false,
});

// ฟังก์ชันสำหรับส่งข้อมูล form
const submit = () => {
    form.post(route('loginEGAT'), {
        onFinish: () => form.reset('password'),
    });
};

// ฟังก์ชัน validation สำหรับ EGAT ID (แก้ไขให้รองรับทั้งตัวเลขและตัวอักษร)
const validateEGATID = () => {
    const egatid = form.egatid.trim();
    
    // ตรวจสอบความยาว
    if (egatid.length > 20) {
        form.errors.egatid = 'EGAT ID ต้องไม่เกิน 20 ตัวอักษร';
    } else if (egatid.length === 0) {
        form.errors.egatid = 'กรุณากรอก EGAT ID';
    } else {
        // ล้าง error เมื่อข้อมูลถูกต้อง
        delete form.errors.egatid;
    }
};

// ฟังก์ชันสำหรับจัดการการเปลี่ยนแปลงของ EGAT ID
const handleEgatIdInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.egatid = target.value;
    validateEGATID();
};
</script>

<template>
    <AuthBase title="Log in to your account" description="Enter your EGAT ID and password below to log in">
        <Head title="Log in" />

        <!-- แสดงข้อความสถานะ -->
        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <!-- ฟอร์มล็อกอิน -->
        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <!-- ฟิลด์ EGAT ID -->
                <div class="grid gap-2">
                    <Label for="egatid">EGAT ID</Label>
                    <Input
                        id="egatid"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="username"
                        :value="form.egatid"
                        @input="handleEgatIdInput"
                        placeholder="569887"
                        maxlength="20"
                        :class="{ 'border-red-500': form.errors.egatid }"
                    />
                    <InputError :message="form.errors.egatid" />
                </div>

                <!-- ฟิลด์รหัสผ่าน -->
                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Password"
                        :class="{ 'border-red-500': form.errors.password }"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <!-- Checkbox Remember me -->
                <div class="flex items-center justify-between" :tabindex="3">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" v-model="form.remember" :tabindex="4" />
                        <span>Remember me</span>
                    </Label>
                </div>

                <!-- ปุ่มล็อกอิน -->
                <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Log in
                </Button>
            </div>

            <!-- ลิงก์ไปหน้าล็อกอินแบบอีเมล -->
            <div class="text-center text-sm text-muted-foreground">
                Or with
                <TextLink :href="route('login')" :tabindex="5">Email</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
