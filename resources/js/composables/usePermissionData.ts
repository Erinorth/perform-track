import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import type { UserPermission } from '@/types/permission';

export function usePermissionData(initialUsers: UserPermission[]) {
    // สร้าง reactive reference สำหรับเก็บข้อมูลผู้ใช้
    const data = ref<UserPermission[]>([]);

    // เรียกใช้ฟังก์ชัน getData ใน onMounted
    onMounted(async () => {
        // ใช้ข้อมูลจาก props หรือเรียก API ถ้าจำเป็น
        data.value = initialUsers && initialUsers.length > 0 
        ? [...initialUsers]
        : await getData();
    });

        // ฟังก์ชันสำหรับโหลดข้อมูลจาก API (หากจำเป็น)
        async function getData(): Promise<UserPermission[]> {
        try {
            const response = await fetch(route('permission'));
            return await response.json();
        } catch (error) {
            console.error('เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้:', error);
            return [];
        }
    }

        // ฟังก์ชันอัปเดตสิทธิ์ด้วย Inertia
        const updatePermission = async (id: number | string, field: string, value: boolean) => {
        // หาข้อมูลผู้ใช้
        const userIndex = data.value.findIndex(user => user.id === id);
        if (userIndex === -1) {
            console.error('ไม่พบผู้ใช้ที่มี ID:', id);
            return;
        }
        
        // อัปเดตข้อมูลในแอปพลิเคชัน
        data.value[userIndex][field] = value;
        data.value = [...data.value];
        
        // ข้อมูลสำหรับ log
        const userName = data.value[userIndex].name;
        const egatId = data.value[userIndex].egat_id;
        
        // ส่งข้อมูลผ่าน Inertia
        router.patch(route('permission.update'), {
            id: id,
            [field]: value
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // แสดง log เมื่อสำเร็จ
                console.log('✅ อัปเดตสิทธิ์สำเร็จ', {
                    user: userName,
                    egatId: egatId,
                    permission: field,
                    value: value ? 'เปิดใช้งาน' : 'ปิดใช้งาน',
                    timestamp: new Date().toLocaleString('th-TH')
                });
            },
            onError: (errors) => {
                // แสดง log เมื่อไม่สำเร็จ
                console.error('❌ ไม่สามารถอัปเดตสิทธิ์ได้', {
                    user: userName,
                    egatId: egatId,
                    permission: field,
                    attemptedValue: value ? 'เปิดใช้งาน' : 'ปิดใช้งาน',
                    errors: errors,
                    timestamp: new Date().toLocaleString('th-TH')
                });
                
                // คืนค่าเดิมเมื่อเกิดข้อผิดพลาด
                data.value[userIndex][field] = !value;
                data.value = [...data.value];
            }
        });
    };

    return {
        data,
        updatePermission
    };
}
