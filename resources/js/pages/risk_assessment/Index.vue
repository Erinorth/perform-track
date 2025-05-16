<!-- resources\js\pages\risk_assessment\Index.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { PlusIcon, FileTextIcon, PencilIcon, TrashIcon, AlertTriangleIcon } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { toast } from 'vue-sonner'
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'

// รับค่า props จาก controller
interface Props {
  riskAssessments: {
    data: Array<{
      id: number
      assessment_date: string
      likelihood_level: number
      impact_level: number
      risk_score: number
      notes: string | null
      division_risk: {
        id: number
        risk_name: string
        organizational_risk: {
          id: number
          risk_name: string
        }
      }
    }>
    links: Array<any>
    meta: any
  }
}

const props = defineProps<Props>()

// กำหนดตัวแปรสำหรับค้นหาข้อมูล
const searchTerm = ref('')

// กรองข้อมูลตามคำค้นหา
const filteredAssessments = computed(() => {
  const term = searchTerm.value.toLowerCase().trim()
  if (!term) return props.riskAssessments.data
  
  return props.riskAssessments.data.filter(assessment => 
    assessment.division_risk.risk_name.toLowerCase().includes(term) ||
    assessment.division_risk.organizational_risk.risk_name.toLowerCase().includes(term)
  )
})

// ฟังก์ชันสำหรับการลบข้อมูล
const deleteRiskAssessment = (id: number) => {
  if (confirm('คุณต้องการลบข้อมูลการประเมินความเสี่ยงนี้ใช่หรือไม่?')) {
    // ส่งคำร้องขอลบข้อมูล
    window.axios.delete(route('risk-assessments.destroy', id))
      .then(() => {
        // แสดงข้อความแจ้งเตือนเมื่อลบสำเร็จ
        toast.success('ลบข้อมูลประเมินความเสี่ยงเรียบร้อยแล้ว')
        // รีเฟรชหน้าเพื่ออัพเดทข้อมูล
        window.location.reload()
      })
      .catch((error) => {
        // แสดงข้อความแจ้งเตือนเมื่อเกิดข้อผิดพลาด
        toast.error('เกิดข้อผิดพลาดในการลบข้อมูล: ' + error.message)
      })
  }
}

// ฟังก์ชันสำหรับระบุระดับความเสี่ยง
const getRiskLevel = (score: number): { text: string, color: string } => {
  if (score <= 3) {
    return { text: 'ต่ำ', color: 'bg-green-100 text-green-800' }
  } else if (score <= 8) {
    return { text: 'ปานกลาง', color: 'bg-yellow-100 text-yellow-800' }
  } else if (score <= 12) {
    return { text: 'สูง', color: 'bg-orange-100 text-orange-800' }
  } else {
    return { text: 'สูงมาก', color: 'bg-red-100 text-red-800' }
  }
}
</script>

<template>
  <AppLayout>
    <Head title="การประเมินความเสี่ยง" />

    <div class="py-6 px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">การประเมินความเสี่ยง</h1>
          <p class="mt-1 text-sm text-gray-500">รายการประเมินความเสี่ยงทั้งหมด</p>
        </div>
        
        <Link :href="route('risk-assessments.create')" class="mt-4 sm:mt-0">
          <Button class="flex items-center gap-2">
            <PlusIcon class="h-4 w-4" />
            <span>เพิ่มการประเมินใหม่</span>
          </Button>
        </Link>
      </div>
      
      <!-- ช่องค้นหา -->
      <div class="mb-4">
        <Input
          v-model="searchTerm"
          placeholder="ค้นหาตามชื่อความเสี่ยง..."
          class="max-w-md"
        />
      </div>

      <!-- ตารางข้อมูล -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <Table>
          <TableCaption>รายการประเมินความเสี่ยงทั้งหมด</TableCaption>
          <TableHeader>
            <TableRow>
              <TableHead>วันที่ประเมิน</TableHead>
              <TableHead>ความเสี่ยงองค์กร</TableHead>
              <TableHead>ความเสี่ยงระดับส่วนงาน</TableHead>
              <TableHead>โอกาสเกิด</TableHead>
              <TableHead>ผลกระทบ</TableHead>
              <TableHead>ระดับความเสี่ยง</TableHead>
              <TableHead>การจัดการ</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="assessment in filteredAssessments" :key="assessment.id">
              <TableCell>{{ new Date(assessment.assessment_date).toLocaleDateString('th-TH') }}</TableCell>
              <TableCell>{{ assessment.division_risk.organizational_risk.risk_name }}</TableCell>
              <TableCell>{{ assessment.division_risk.risk_name }}</TableCell>
              <TableCell>{{ assessment.likelihood_level }}</TableCell>
              <TableCell>{{ assessment.impact_level }}</TableCell>
              <TableCell>
                <span 
                  class="px-2 py-1 text-xs font-medium rounded-full" 
                  :class="getRiskLevel(assessment.risk_score).color"
                >
                  {{ getRiskLevel(assessment.risk_score).text }} ({{ assessment.risk_score }})
                </span>
              </TableCell>
              <TableCell>
                <div class="flex items-center space-x-2">
                  <Link :href="route('risk-assessments.show', assessment.id)" class="text-blue-600 hover:text-blue-800">
                    <FileTextIcon class="h-4 w-4" />
                  </Link>
                  <Link :href="route('risk-assessments.edit', assessment.id)" class="text-amber-600 hover:text-amber-800">
                    <PencilIcon class="h-4 w-4" />
                  </Link>
                  <button @click="deleteRiskAssessment(assessment.id)" class="text-red-600 hover:text-red-800">
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </TableCell>
            </TableRow>
            
            <!-- กรณีไม่มีข้อมูล -->
            <TableRow v-if="filteredAssessments.length === 0">
              <TableCell colspan="7" class="text-center py-8">
                <div class="flex flex-col items-center justify-center text-gray-500">
                  <AlertTriangleIcon class="h-12 w-12 mb-2" />
                  <span v-if="searchTerm">ไม่พบข้อมูลที่ตรงตามการค้นหา</span>
                  <span v-else>ไม่พบข้อมูลการประเมินความเสี่ยง</span>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- การแบ่งหน้า -->
      <div class="mt-4 flex justify-center" v-if="props.riskAssessments.meta && props.riskAssessments.meta.last_page > 1">
        <nav class="flex items-center">
          <template v-for="(link, i) in props.riskAssessments.links" :key="i">
            <div 
              v-if="link.url === null" 
              class="px-4 py-2 text-gray-400" 
              v-html="link.label"
            ></div>
            <Link 
              v-else 
              :href="link.url" 
              class="px-4 py-2 border mx-1 rounded" 
              :class="{'bg-blue-600 text-white': link.active, 'text-gray-700 hover:bg-gray-100': !link.active}" 
              v-html="link.label"
            ></Link>
          </template>
        </nav>
      </div>
    </div>
  </AppLayout>
</template>
