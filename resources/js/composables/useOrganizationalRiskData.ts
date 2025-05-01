/* 
  ไฟล์: resources\js\features\organizational_risk\useOrganizationalRiskData.ts
  Composable function สำหรับจัดการข้อมูลความเสี่ยงระดับองค์กร
  ใช้หลักการของ Vue Composition API เพื่อแยกโลจิกการจัดการข้อมูลออกจาก component
*/

import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';  // เพิ่ม useForm
import { toast } from 'vue-sonner';
import { CheckCircle2Icon } from 'lucide-vue-next';  // เพิ่ม icons ที่จำเป็น
import type { OrganizationalRisk } from '@/types/types';

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

    // เพิ่มฟังก์ชัน submitForm
    const submitForm = async (
        formData: { risk_name: string; description: string; year: number },
        riskId?: number,
        onSuccess?: () => void
    ) => {
        // บันทึก log เพื่อการตรวจสอบ
        console.log('📝 กำลังบันทึกข้อมูลความเสี่ยงองค์กร:', {
            data: formData,
            mode: riskId ? 'แก้ไข' : 'เพิ่มใหม่',
            timestamp: new Date().toLocaleString('th-TH')
        });

        return new Promise((resolve, reject) => {
            if (riskId) {
                // กรณีแก้ไข: ใช้ PUT request
                router.put(`/organizational-risks/${riskId}`, formData, {
                    onSuccess: (page) => {
                        // อัปเดตข้อมูลใน data array
                        const index = data.value.findIndex(item => item.id === riskId);
                        if (index !== -1) {
                            // แก้ไขเพื่อรักษาโครงสร้างข้อมูลเดิมพร้อมอัปเดตเฉพาะส่วนที่เปลี่ยน
                            data.value[index] = { 
                                ...data.value[index], 
                                risk_name: formData.risk_name,
                                description: formData.description,
                                year: formData.year
                            };
                            // สร้าง array ใหม่เพื่อทริกเกอร์การ re-render
                            data.value = [...data.value];
                        }
                        
                        // แสดงข้อความแจ้งเตือนสำเร็จ
                        toast.success('บันทึกข้อมูลความเสี่ยงสำเร็จ', {
                            icon: CheckCircle2Icon,
                            description: `ความเสี่ยง "${formData.risk_name}" ได้รับการแก้ไขเรียบร้อยแล้ว`,
                            duration: 4000,
                            closeButton: true
                        });
                        
                        // เรียกฟังก์ชัน callback ถ้ามี
                        if (onSuccess) onSuccess();
                        resolve(page);
                    },
                    onError: (errors) => {
                        // ... (โค้ดส่วน onError คงเดิม)
                    }
                });
            } else {
                // กรณีเพิ่มใหม่: ใช้ POST request
                router.post('/organizational-risks', formData, {
                    onSuccess: (page) => {
                        // แก้ไขส่วนนี้เพื่อตรวจสอบข้อมูลที่ได้รับจาก response
                        if (page.props.risk && typeof page.props.risk === 'object' && 'id' in page.props.risk) {
                            // ตรวจสอบให้แน่ใจว่าวัตถุมีโครงสร้างที่ถูกต้องตาม OrganizationalRisk
                            const newRisk = page.props.risk as OrganizationalRisk;
                            
                            // เพิ่มข้อมูลใหม่ใน array
                            data.value.push(newRisk);
                            // สร้าง array ใหม่เพื่อทริกเกอร์การ re-render
                            data.value = [...data.value];
                        } else {
                            console.warn('ได้รับข้อมูลความเสี่ยงที่ไม่สมบูรณ์จาก response:', page.props.risk);
                        }
                        
                        // แสดงข้อความแจ้งเตือนสำเร็จ
                        toast.success('เพิ่มความเสี่ยงสำเร็จ', {
                            icon: CheckCircle2Icon,
                            description: `ความเสี่ยง "${formData.risk_name}" ถูกเพิ่มเรียบร้อยแล้ว`,
                            duration: 4000,
                            closeButton: true
                        });
                        
                        // เรียกฟังก์ชัน callback ถ้ามี
                        if (onSuccess) onSuccess();
                        resolve(page);
                    },
                    onError: (errors) => {
                        // ... (โค้ดส่วน onError คงเดิม)
                    }
                });
            }
        });
    };
    
    // ส่งคืนข้อมูลและฟังก์ชันที่ต้องการให้ component อื่นใช้งาน
    return {
        data,
        deleteRisk,
        submitForm  // เพิ่มฟังก์ชัน submitForm
    };
}