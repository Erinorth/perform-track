/* resources\js\composables\useDivisionRiskActions.ts */
// นำเข้าตัวแปรและฟังก์ชันที่จำเป็น
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { DivisionRisk } from '@/types/types';
import { useDivisionRiskData } from '@/composables/useDivisionRiskData';
import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation';

/**
 * Composable สำหรับจัดการ action ต่างๆ ของความเสี่ยงฝ่าย
 * รวมฟังก์ชันการเพิ่ม แก้ไข และลบข้อมูล
 */
export function useDivisionRiskActions(initialRisks: DivisionRisk[]) {
  // ใช้ composable สำหรับจัดการข้อมูลความเสี่ยงฝ่าย
  const { data, deleteRisk, bulkDeleteRisks } = useDivisionRiskData(initialRisks);

  // ตัวแปรสำหรับจัดการสถานะ Modal และข้อมูลที่กำลังแก้ไข
  const showModal = ref(false);
  const currentRisk = ref<DivisionRisk | undefined>(undefined);

  // ฟังก์ชันเปิด Modal สำหรับเพิ่มข้อมูลใหม่
  const openCreateModal = () => {
    currentRisk.value = undefined;
    showModal.value = true;
    
    // บันทึก log เพื่อการตรวจสอบ
    console.log('เปิด Modal เพื่อเพิ่มความเสี่ยงใหม่');
  };

  // ฟังก์ชันเปิด Modal สำหรับแก้ไขข้อมูล
  const openEditModal = (risk: DivisionRisk) => {
    currentRisk.value = risk;
    showModal.value = true;
    
    // บันทึก log เพื่อการตรวจสอบ
    console.log('เปิด Modal เพื่อแก้ไขความเสี่ยง:', risk.risk_name);
  };

  // ฟังก์ชันจัดการเมื่อบันทึกข้อมูลสำเร็จ
  const handleSaved = () => {
    router.visit(route('division-risks.index'), {
      preserveScroll: true,
      only: ['risks']
    });
  };

  const { confirmDelete, confirmBulkDelete } = useDeleteConfirmation();

  // ฟังก์ชันสำหรับลบข้อมูลความเสี่ยง (ปรับปรุงใหม่)
  const handleDelete = (risk: DivisionRisk) => {
    confirmDelete(risk, risk.risk_name, deleteRisk);
  };

  // ฟังก์ชันสำหรับลบข้อมูลหลายรายการพร้อมกัน (ปรับปรุงใหม่)
  const handleBulkDelete = async (selectedIds: number[]) => {
    confirmBulkDelete(selectedIds, 'ความเสี่ยง', bulkDeleteRisks);
  };

  return {
    data,
    showModal,
    currentRisk,
    openCreateModal,
    openEditModal,
    handleSaved,
    handleDelete,
    handleBulkDelete
  };
}
