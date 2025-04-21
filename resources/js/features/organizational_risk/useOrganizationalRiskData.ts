import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import type { OrganizationalRisk } from './organizational_risk.ts';

export function useOrganizationalRiskData(initialRisks: OrganizationalRisk[]) {
    // สร้าง reactive reference สำหรับเก็บข้อมูลความเสี่ยงองค์กร
    const data = ref<OrganizationalRisk[]>([]);

    // เรียกใช้ฟังก์ชัน getData ใน onMounted
    onMounted(async () => {
        // ใช้ข้อมูลจาก props หรือเรียก API ถ้าจำเป็น
        data.value = initialRisks && initialRisks.length > 0 
        ? [...initialRisks]
        : await getData();
    });

    // ฟังก์ชันสำหรับโหลดข้อมูลจาก API (หากจำเป็น)
    async function getData(): Promise<OrganizationalRisk[]> {
        try {
            const response = await fetch(route('organizational-risks.index'));
            return await response.json();
        } catch (error) {
            console.error('เกิดข้อผิดพลาดในการดึงข้อมูลความเสี่ยงองค์กร:', error);
            return [];
        }
    }

    // ฟังก์ชันอัปเดตสถานะความเสี่ยงองค์กร
    const updateRiskStatus = async (id: number, active: boolean) => {
        // หาข้อมูลความเสี่ยง
        const riskIndex = data.value.findIndex(risk => risk.id === id);
        if (riskIndex === -1) {
            console.error('ไม่พบความเสี่ยงองค์กรที่มี ID:', id);
            return;
        }
        
        // อัปเดตข้อมูลในแอปพลิเคชัน
        data.value[riskIndex].active = active;
        data.value = [...data.value];
        
        // ข้อมูลสำหรับ log
        const riskName = data.value[riskIndex].risk_name;
        
        // ส่งข้อมูลผ่าน Inertia
        router.put(route('organizational-risks.update', id), {
            ...data.value[riskIndex],
            active: active
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // แสดง log เมื่อสำเร็จ
                console.log('✅ อัปเดตสถานะความเสี่ยงองค์กรสำเร็จ', {
                    risk: riskName,
                    status: active ? 'เปิดใช้งาน' : 'ปิดใช้งาน',
                    timestamp: new Date().toLocaleString('th-TH')
                });
            },
            onError: (errors) => {
                // แสดง log เมื่อไม่สำเร็จ
                console.error('❌ ไม่สามารถอัปเดตสถานะความเสี่ยงองค์กรได้', {
                    risk: riskName,
                    attemptedStatus: active ? 'เปิดใช้งาน' : 'ปิดใช้งาน',
                    errors: errors,
                    timestamp: new Date().toLocaleString('th-TH')
                });
                
                // คืนค่าเดิมเมื่อเกิดข้อผิดพลาด
                data.value[riskIndex].active = !active;
                data.value = [...data.value];
            }
        });
    };

    return {
        data,
        updateRiskStatus
    };
}
