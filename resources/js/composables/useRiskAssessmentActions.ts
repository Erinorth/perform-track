/* resources\js\composables\useRiskAssessmentActions.ts */
// นำเข้าตัวแปรและฟังก์ชันที่จำเป็น
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { RiskAssessment } from '@/types/types';
import { useRiskAssessmentData } from '@/composables/useRiskAssessmentData';
import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation';

/**
 * Composable สำหรับจัดการ action ต่างๆ ของการประเมินความเสี่ยง
 * รวมฟังก์ชันการเพิ่ม แก้ไข และลบข้อมูล
 */
export function useRiskAssessmentActions(initialAssessments: RiskAssessment[]) {
  // ใช้ composable สำหรับจัดการข้อมูลการประเมินความเสี่ยง
  const { data, deleteAssessment, bulkDeleteAssessments } = useRiskAssessmentData(initialAssessments);

  // ตัวแปรสำหรับจัดการสถานะ Modal และข้อมูลที่กำลังแก้ไข
  const showModal = ref(false);
  const currentAssessment = ref<RiskAssessment | undefined>(undefined);

  // ฟังก์ชันเปิด Modal สำหรับเพิ่มข้อมูลใหม่
  const openCreateModal = () => {
    currentAssessment.value = undefined;
    showModal.value = true;
    
    // บันทึก log เพื่อการตรวจสอบ
    console.log('เปิด Modal เพื่อเพิ่มการประเมินความเสี่ยงใหม่');
  };

  // ฟังก์ชันเปิด Modal สำหรับแก้ไขข้อมูล
  const openEditModal = (assessment: RiskAssessment) => {
    currentAssessment.value = assessment;
    showModal.value = true;
    
    // บันทึก log เพื่อการตรวจสอบ
    console.log('เปิด Modal เพื่อแก้ไขการประเมินความเสี่ยงวันที่:', 
      new Date(assessment.assessment_date).toLocaleDateString('th-TH'));
  };

  // ฟังก์ชันจัดการเมื่อบันทึกข้อมูลสำเร็จ
  const handleSaved = () => {
    router.visit(route('risk-assessments.index'), {
      preserveScroll: true,
      only: ['assessments']
    });
  };

  const { confirmDelete, confirmBulkDelete } = useDeleteConfirmation();

  // ฟังก์ชันสำหรับลบข้อมูลการประเมินความเสี่ยง
  const handleDelete = (assessment: RiskAssessment) => {
    const assessmentDate = new Date(assessment.assessment_date).toLocaleDateString('th-TH');
    confirmDelete(assessment, `การประเมินความเสี่ยงวันที่ ${assessmentDate}`, deleteAssessment);
  };

  // ฟังก์ชันสำหรับลบข้อมูลหลายรายการพร้อมกัน
  const handleBulkDelete = async (selectedIds: number[]) => {
    confirmBulkDelete(selectedIds, 'การประเมินความเสี่ยง', bulkDeleteAssessments);
  };

  return {
    data,
    showModal,
    currentAssessment,
    openCreateModal,
    openEditModal,
    handleSaved,
    handleDelete,
    handleBulkDelete
  };
}
