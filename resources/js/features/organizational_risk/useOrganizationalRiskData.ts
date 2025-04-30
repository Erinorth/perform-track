/* 
  ไฟล์: resources\js\features\organizational_risk\useOrganizationalRiskData.ts
  Composable function สำหรับจัดการข้อมูลความเสี่ยงระดับองค์กร
  ใช้หลักการของ Vue Composition API เพื่อแยกโลจิกการจัดการข้อมูลออกจาก component
*/

import { ref, onMounted } from 'vue';  // นำเข้าฟังก์ชัน ref และ onMounted จาก Vue Composition API
import { router } from '@inertiajs/vue3';  // นำเข้า router จาก Inertia สำหรับการนำทางและส่งข้อมูล
import { toast } from 'vue-sonner';
import type { OrganizationalRisk } from './organizational_risk.ts';  // นำเข้า type ของข้อมูลความเสี่ยงระดับองค์กร

// ฟังก์ชัน composable สำหรับจัดการข้อมูลความเสี่ยงระดับองค์กร
// รับพารามิเตอร์เป็นข้อมูลเริ่มต้นจาก props
export function useOrganizationalRiskData(initialRisks: OrganizationalRisk[]) {
    // สร้าง reactive reference สำหรับเก็บข้อมูลความเสี่ยงระดับองค์กร
    // ใช้ ref เพื่อให้ข้อมูลเป็น reactive (เมื่อข้อมูลเปลี่ยน component จะ re-render โดยอัตโนมัติ)
    const data = ref<OrganizationalRisk[]>([]);

    // เรียกใช้ฟังก์ชัน getData เมื่อ component ถูก mount
    // ใช้ onMounted เพื่อให้แน่ใจว่าโค้ดจะทำงานหลังจาก component ถูกสร้างเสร็จแล้ว
    onMounted(async () => {
        // ตรวจสอบว่ามีข้อมูลเริ่มต้นหรือไม่
        // ถ้ามี ให้ใช้ข้อมูลจาก props
        // ถ้าไม่มี ให้เรียกข้อมูลจาก API ผ่านฟังก์ชัน getData
        data.value = initialRisks && initialRisks.length > 0 
        ? [...initialRisks]  // clone array เพื่อป้องกันการเปลี่ยนแปลงข้อมูลต้นฉบับ
        : await getData();   // เรียกข้อมูลจาก API
    });

    // ฟังก์ชันสำหรับดึงข้อมูลความเสี่ยงระดับองค์กรจาก API
    async function getData(): Promise<OrganizationalRisk[]> {
        try {
            // เรียกข้อมูลจาก API โดยใช้ route function ของ Laravel
            const response = await fetch(route('organizational-risks.index'));
            return await response.json();  // แปลง response เป็น JSON
        } catch (error) {
            // บันทึกข้อผิดพลาดลง console สำหรับการดีบัก
            console.error('เกิดข้อผิดพลาดในการดึงข้อมูลความเสี่ยงองค์กร:', error);
            // ส่งคืน array ว่างเมื่อเกิดข้อผิดพลาด
            return [];
        }
    }

    // ฟังก์ชันสำหรับอัปเดตสถานะความเสี่ยงระดับองค์กร (เปิดใช้งาน/ปิดใช้งาน)
    const updateRiskStatus = async (id: number, active: boolean) => {
        // ค้นหาตำแหน่งของความเสี่ยงที่ต้องการอัปเดตในอาร์เรย์
        const riskIndex = data.value.findIndex(risk => risk.id === id);
        // ตรวจสอบว่าพบความเสี่ยงหรือไม่
        if (riskIndex === -1) {
            console.error('ไม่พบความเสี่ยงองค์กรที่มี ID:', id);
            return;
        }
        
        // อัปเดตสถานะการใช้งานในข้อมูลแบบ reactive
        data.value[riskIndex].active = active;
        // สร้าง array ใหม่เพื่อทริกเกอร์การ re-render ของ component
        data.value = [...data.value];
        
        // เก็บชื่อความเสี่ยงสำหรับการแสดง log
        const riskName = data.value[riskIndex].risk_name;
        
        // ส่งข้อมูลไปยังเซิร์ฟเวอร์ผ่าน Inertia router
        router.put(route('organizational-risks.update', id), {
            ...data.value[riskIndex],  // ส่งข้อมูลเดิมทั้งหมด
            active: active  // อัปเดตสถานะการใช้งาน
        }, {
            preserveScroll: true,  // คงตำแหน่งการเลื่อนของหน้าไว้
            onSuccess: () => {
                // เมื่ออัปเดตสำเร็จ บันทึก log ลง console สำหรับการติดตาม
                console.log('✅ อัปเดตสถานะความเสี่ยงองค์กรสำเร็จ', {
                    risk: riskName,
                    status: active ? 'เปิดใช้งาน' : 'ปิดใช้งาน',
                    timestamp: new Date().toLocaleString('th-TH')  // บันทึกเวลาในรูปแบบไทย
                });
            },
            onError: (errors) => {
                // เมื่อเกิดข้อผิดพลาด บันทึก log ข้อผิดพลาดลง console
                console.error('❌ ไม่สามารถอัปเดตสถานะความเสี่ยงองค์กรได้', {
                    risk: riskName,
                    attemptedStatus: active ? 'เปิดใช้งาน' : 'ปิดใช้งาน',
                    errors: errors,
                    timestamp: new Date().toLocaleString('th-TH')  // บันทึกเวลาในรูปแบบไทย
                });
                
                // คืนค่าสถานะเดิมในกรณีที่การอัปเดตไม่สำเร็จ (optimistic UI rollback)
                data.value[riskIndex].active = !active;
                // สร้าง array ใหม่เพื่อทริกเกอร์การ re-render ของ component
                data.value = [...data.value];
            }
        });
    };

    // เพิ่มฟังก์ชันลบข้อมูลความเสี่ยง
    const deleteRisk = async (risk: OrganizationalRisk): Promise<void> => {
        if (!risk || !risk.id) {
            console.error('ไม่พบข้อมูล ID สำหรับความเสี่ยงที่ต้องการลบ');
            throw new Error('ไม่พบข้อมูลที่ต้องการลบ');
        }
        
        return new Promise((resolve, reject) => {
            // ส่งคำขอลบข้อมูลไปยัง Laravel Backend
            router.delete(route('organizational-risks.destroy', risk.id), {
                preserveScroll: true,
                onSuccess: () => {
                    // ลบข้อมูลออกจาก data เมื่อลบสำเร็จ
                    data.value = data.value.filter(item => item.id !== risk.id);
                    // แสดงข้อความแจ้งเตือนสำเร็จ
                    toast.success('ลบความเสี่ยงระดับองค์กรเรียบร้อยแล้ว');
                    
                    // บันทึก log สำหรับติดตาม
                    console.log('✅ ลบความเสี่ยงองค์กรสำเร็จ', {
                        risk: risk.risk_name,
                        id: risk.id,
                        timestamp: new Date().toLocaleString('th-TH')
                    });
                    
                    resolve();
                },
                onError: (errors) => {
                    // แสดงข้อความแจ้งเตือนเมื่อเกิดข้อผิดพลาด
                    if (errors.error) {
                        toast.error(errors.error);
                    } else {
                        toast.error('เกิดข้อผิดพลาดในการลบข้อมูล');
                    }
                    
                    // บันทึก log ข้อผิดพลาด
                    console.error('❌ ไม่สามารถลบความเสี่ยงองค์กรได้', {
                        risk: risk.risk_name,
                        id: risk.id,
                        errors: errors,
                        timestamp: new Date().toLocaleString('th-TH')
                    });
                    
                    reject(errors);
                }
            });
        });
    };
    
    // ส่งคืนข้อมูลและฟังก์ชันที่ต้องการให้ component อื่นใช้งาน
    return {
        data,
        updateRiskStatus,
        deleteRisk // ส่งคืนฟังก์ชันลบ
    };
}
