<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { 
  ArrowLeftIcon, 
  PencilIcon,
  TrashIcon,
  BarChart4Icon,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog'
import AppLayout from '@/layouts/AppLayout.vue'
import RiskMatrix from '@/components/RiskMatrix.vue'

// กำหนดโครงสร้างข้อมูล props
interface Props {
  riskAssessment: {
    id: number
    assessment_date: string
    likelihood_level: number
    impact_level: number
    risk_score: number
    notes: string | null
    division_risk: {
      id: number
      risk_name: string
      description: string
      organizational_risk: {
        id: number
        risk_name: string
      }
      likelihood_criteria: Array<{
        id: number
        level: number
        name: string
        description: string | null
      }>
      impact_criteria: Array<{
        id: number
        level: number
        name: string
        description: string | null
      }>
    }
  }
}

const props = defineProps<Props>()
const showMatrix = ref(false)

// คำนวณระดับความเสี่ยง
const riskLevel = computed(() => {
  const score = props.riskAssessment.risk_score
  if (score <= 3) {
    return { text: 'ความเสี่ยงต่ำ', color: 'bg-green-100 text-green-800' }
  } else if (score <= 8) {
    return { text: 'ความเสี่ยงปานกลาง', color: 'bg-yellow-100 text-yellow-800' }
  } else if (score <= 12) {
    return { text: 'ความเสี่ยงสูง', color: 'bg-orange-100 text-orange-800' }
  } else {
    return { text: 'ความเสี่ยงสูงมาก', color: 'bg-red-100 text-red-800' }
  }
})

// สลับการแสดงแผนภูมิความเสี่ยง
const toggleRiskMatrix = () => {
  showMatrix.value = !showMatrix.value
}

// ฟังก์ชันสำหรับลบข้อมูล
const deleteRiskAssessment = () => {
  window.axios.delete(route('risk-assessments.destroy', props.riskAssessment.id))
    .then(() => {
      toast.success('ลบข้อมูลการประเมินความเสี่ยงเรียบร้อยแล้ว')
      window.location.href = route('risk-assessments.index')
    })
    .catch((error) => {
      toast.error('เกิดข้อผิดพลาดในการลบข้อมูล: ' + error.message)
    })
}

// หาเกณฑ์การประเมินที่สอดคล้องกับระดับที่เลือก
const selectedLikelihoodCriteria = computed(() => {
  return props.riskAssessment.division_risk.likelihood_criteria.find(
    criteria => criteria.level === props.riskAssessment.likelihood_level
  )
})

const selectedImpactCriteria = computed(() => {
  return props.riskAssessment.division_risk.impact_criteria.find(
    criteria => criteria.level === props.riskAssessment.impact_level
  )
})
</script>

<template>
  <AppLayout>
    <Head title="รายละเอียดการประเมินความเสี่ยง" />

    <div class="py-6 px-4 sm:px-6 lg:px-8">
      <div class="flex items-center gap-2 mb-6">
        <Button variant="outline" size="sm" @click="$inertia.visit(route('risk-assessments.index'))">
          <ArrowLeftIcon class="h-4 w-4 mr-2" />
          กลับ
        </Button>
        <h1 class="text-2xl font-semibold text-gray-900">รายละเอียดการประเมินความเสี่ยง</h1>
      </div>

      <Card class="max-w-4xl mx-auto">
        <CardHeader class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <CardTitle>การประเมินความเสี่ยง #{{ props.riskAssessment.id }}</CardTitle>
            <p class="text-sm text-gray-500 mt-1">
              วันที่ประเมิน: {{ new Date(props.riskAssessment.assessment_date).toLocaleDateString('th-TH') }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <Button variant="outline" size="sm" @click="$inertia.visit(route('risk-assessments.edit', props.riskAssessment.id))">
              <PencilIcon class="h-4 w-4 mr-2" />
              แก้ไข
            </Button>
            <AlertDialog>
              <AlertDialogTrigger asChild>
                <Button variant="destructive" size="sm">
                  <TrashIcon class="h-4 w-4 mr-2" />
                  ลบ
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>คุณต้องการลบการประเมินความเสี่ยงนี้ใช่หรือไม่?</AlertDialogTitle>
                  <AlertDialogDescription>
                    การลบข้อมูลการประเมินความเสี่ยงนี้จะไม่สามารถกู้คืนได้ คุณแน่ใจหรือไม่ว่าต้องการดำเนินการต่อ?
                  </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>ยกเลิก</AlertDialogCancel>
                  <AlertDialogAction @click="deleteRiskAssessment" class="bg-red-600 hover:bg-red-700">
                    ลบข้อมูล
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </div>
        </CardHeader>
        <CardContent class="space-y-6">
          <!-- ข้อมูลความเสี่ยงองค์กรและส่วนงาน -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 class="text-sm font-medium text-gray-500">ความเสี่ยงองค์กร</h3>
              <p class="mt-1 text-lg font-medium">
                {{ props.riskAssessment.division_risk.organizational_risk.risk_name }}
              </p>
            </div>
            <div>
              <h3 class="text-sm font-medium text-gray-500">ความเสี่ยงระดับส่วนงาน</h3>
              <p class="mt-1 text-lg font-medium">
                {{ props.riskAssessment.division_risk.risk_name }}
              </p>
              <p v-if="props.riskAssessment.division_risk.description" class="mt-1 text-sm text-gray-600">
                {{ props.riskAssessment.division_risk.description }}
              </p>
            </div>
          </div>

          <!-- ข้อมูลการประเมินความเสี่ยง -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 bg-blue-50 rounded-lg">
              <h3 class="text-sm font-medium text-gray-500">ระดับโอกาสเกิด</h3>
              <p class="mt-1 text-2xl font-bold text-blue-700">
                {{ props.riskAssessment.likelihood_level }}
              </p>
              <p v-if="selectedLikelihoodCriteria" class="mt-1 text-sm text-blue-600">
                {{ selectedLikelihoodCriteria.name }}
              </p>
              <p v-if="selectedLikelihoodCriteria?.description" class="mt-1 text-xs text-gray-600">
                {{ selectedLikelihoodCriteria.description }}
              </p>
            </div>
            
            <div class="p-4 bg-indigo-50 rounded-lg">
              <h3 class="text-sm font-medium text-gray-500">ระดับผลกระทบ</h3>
              <p class="mt-1 text-2xl font-bold text-indigo-700">
                {{ props.riskAssessment.impact_level }}
              </p>
              <p v-if="selectedImpactCriteria" class="mt-1 text-sm text-indigo-600">
                {{ selectedImpactCriteria.name }}
              </p>
              <p v-if="selectedImpactCriteria?.description" class="mt-1 text-xs text-gray-600">
                {{ selectedImpactCriteria.description }}
              </p>
            </div>
            
            <div class="p-4 rounded-lg" :class="riskLevel.color">
              <h3 class="text-sm font-medium text-gray-700">ระดับความเสี่ยง</h3>
              <p class="mt-1 text-2xl font-bold">
                {{ riskLevel.text }}
              </p>
              <p class="mt-1 text-sm font-medium">
                คะแนน: {{ props.riskAssessment.risk_score }}
              </p>
            </div>
          </div>
          
          <!-- บันทึกเพิ่มเติม -->
          <div v-if="props.riskAssessment.notes" class="border-t pt-4">
            <h3 class="text-sm font-medium text-gray-500 mb-2">บันทึกเพิ่มเติม</h3>
            <div class="bg-gray-50 p-4 rounded-lg">
              <p class="whitespace-pre-wrap text-gray-700">{{ props.riskAssessment.notes }}</p>
            </div>
          </div>
          
          <!-- แผนภูมิความเสี่ยง -->
          <div>
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium text-gray-700">แผนภูมิความเสี่ยง</h3>
              <Button variant="outline" size="sm" @click="toggleRiskMatrix">
                <BarChart4Icon class="h-4 w-4 mr-2" />
                {{ showMatrix ? 'ซ่อน' : 'แสดง' }}แผนภูมิ
              </Button>
            </div>
            
            <div v-if="showMatrix" class="mt-4">
              <RiskMatrix 
                :likelihood="props.riskAssessment.likelihood_level" 
                :impact="props.riskAssessment.impact_level"
              />
            </div>
          </div>
        </CardContent>
        <CardFooter class="flex justify-between">
          <Button variant="outline" @click="$inertia.visit(route('risk-assessments.index'))">
            กลับสู่รายการ
          </Button>
          <Button @click="$inertia.visit(route('risk-assessments.edit', props.riskAssessment.id))">
            <PencilIcon class="h-4 w-4 mr-2" />
            แก้ไข
          </Button>
        </CardFooter>
      </Card>
    </div>
  </AppLayout>
</template>
