<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { 
  ArrowLeftIcon, 
  InfoIcon,
  BarChart4Icon,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import AppLayout from '@/layouts/AppLayout.vue'
import RiskMatrix from '@/components/RiskMatrix.vue'

// กำหนดโครงสร้างข้อมูล props
interface Props {
  riskAssessment: {
    id: number
    assessment_date: string
    likelihood_level: number
    impact_level: number
    division_risk_id: number
    notes: string | null
    division_risk: {
      id: number
      risk_name: string
      organizational_risk: {
        id: number
        risk_name: string
      }
    }
  }
  divisionRisks: Array<{
    id: number
    risk_name: string
    description: string
    organizational_risk: {
      id: number
      risk_name: string
    }
  }>
  likelihoodCriteria: Record<number, Array<{
    id: number
    level: number
    name: string
    description: string | null
  }>>
  impactCriteria: Record<number, Array<{
    id: number
    level: number
    name: string
    description: string | null
  }>>
}

const props = defineProps<Props>()

// สร้าง form สำหรับการบันทึกข้อมูล
const form = useForm({
  assessment_date: props.riskAssessment.assessment_date,
  division_risk_id: props.riskAssessment.division_risk_id.toString(),
  likelihood_level: props.riskAssessment.likelihood_level.toString(),
  impact_level: props.riskAssessment.impact_level.toString(),
  notes: props.riskAssessment.notes || ''
})

// กำหนดตัวแปรสำหรับแสดงข้อมูลเกณฑ์การประเมิน
const selectedDivisionRisk = ref<number>(props.riskAssessment.division_risk_id)
const showMatrix = ref(false)

// เกณฑ์การประเมินโอกาสเกิดสำหรับความเสี่ยงที่เลือก
const selectedLikelihoodCriteria = computed(() => {
  if (!selectedDivisionRisk.value) return []
  return props.likelihoodCriteria[selectedDivisionRisk.value] || []
})

// เกณฑ์การประเมินผลกระทบสำหรับความเสี่ยงที่เลือก
const selectedImpactCriteria = computed(() => {
  if (!selectedDivisionRisk.value) return []
  return props.impactCriteria[selectedDivisionRisk.value] || []
})

// คำนวณคะแนนความเสี่ยงและระดับความเสี่ยง
const riskScore = computed(() => {
  if (!form.likelihood_level || !form.impact_level) return 0
  return parseInt(form.likelihood_level) * parseInt(form.impact_level)
})

const riskLevel = computed(() => {
  const score = riskScore.value
  if (score <= 3) {
    return { text: 'ความเสี่ยงต่ำ', color: 'text-green-700' }
  } else if (score <= 8) {
    return { text: 'ความเสี่ยงปานกลาง', color: 'text-yellow-700' }
  } else if (score <= 12) {
    return { text: 'ความเสี่ยงสูง', color: 'text-orange-700' }
  } else {
    return { text: 'ความเสี่ยงสูงมาก', color: 'text-red-700' }
  }
})

// เมื่อเลือกความเสี่ยงระดับส่วนงาน
const onDivisionRiskChange = (value: string) => {
  form.division_risk_id = value
  selectedDivisionRisk.value = parseInt(value)
  
  // รีเซ็ตค่าการประเมินเมื่อเลือกความเสี่ยงอื่น
  form.likelihood_level = ''
  form.impact_level = ''
}

// ส่งข้อมูลการประเมิน
const submitForm = () => {
  form.put(route('risk-assessments.update', props.riskAssessment.id), {
    onSuccess: () => {
      toast.success('บันทึกการประเมินความเสี่ยงเรียบร้อยแล้ว')
    },
    onError: () => {
      toast.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง')
    }
  })
}

// สลับการแสดงแผนภูมิความเสี่ยง
const toggleRiskMatrix = () => {
  showMatrix.value = !showMatrix.value
}
</script>

<template>
  <AppLayout>
    <Head title="แก้ไขการประเมินความเสี่ยง" />

    <div class="py-6 px-4 sm:px-6 lg:px-8">
      <div class="flex items-center gap-2 mb-6">
        <Button variant="outline" size="sm" @click="$inertia.visit(route('risk-assessments.index'))">
          <ArrowLeftIcon class="h-4 w-4 mr-2" />
          กลับ
        </Button>
        <h1 class="text-2xl font-semibold text-gray-900">แก้ไขการประเมินความเสี่ยง</h1>
      </div>

      <Card class="max-w-4xl mx-auto">
        <CardHeader>
          <CardTitle>แบบฟอร์มแก้ไขการประเมินความเสี่ยง</CardTitle>
          <CardDescription>แก้ไขข้อมูลการประเมินความเสี่ยงตามเกณฑ์การประเมินที่กำหนด</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submitForm" class="space-y-6">
            <!-- วันที่ประเมิน -->
            <div>
              <Label for="assessment_date">วันที่ประเมิน <span class="text-red-500">*</span></Label>
              <Input
                id="assessment_date"
                v-model="form.assessment_date"
                type="date"
                class="mt-1"
                :class="{ 'border-red-500': form.errors.assessment_date }"
              />
              <div v-if="form.errors.assessment_date" class="text-red-500 text-sm mt-1">
                {{ form.errors.assessment_date }}
              </div>
            </div>

            <!-- ความเสี่ยงระดับส่วนงาน -->
            <div>
              <Label for="division_risk_id">ความเสี่ยงระดับส่วนงาน <span class="text-red-500">*</span></Label>
              <Select 
                v-model="form.division_risk_id"
                @update:model-value="onDivisionRiskChange"
              >
                <SelectTrigger 
                  class="w-full mt-1" 
                  :class="{ 'border-red-500': form.errors.division_risk_id }"
                >
                  <SelectValue placeholder="เลือกความเสี่ยงระดับส่วนงาน" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup v-for="(risks, orgRiskName) in groupBy(props.divisionRisks, risk => risk.organizational_risk.risk_name)" :key="orgRiskName">
                    <SelectLabel>{{ orgRiskName }}</SelectLabel>
                    <SelectItem 
                      v-for="risk in risks" 
                      :key="risk.id" 
                      :value="risk.id.toString()"
                    >
                      {{ risk.risk_name }}
                    </SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
              <div v-if="form.errors.division_risk_id" class="text-red-500 text-sm mt-1">
                {{ form.errors.division_risk_id }}
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- ระดับโอกาสเกิด -->
              <div>
                <div class="flex items-center justify-between">
                  <Label for="likelihood_level">ระดับโอกาสเกิด <span class="text-red-500">*</span></Label>
                  <TooltipProvider v-if="selectedLikelihoodCriteria.length > 0">
                    <Tooltip>
                      <TooltipTrigger>
                        <InfoIcon class="h-4 w-4 text-gray-500" />
                      </TooltipTrigger>
                      <TooltipContent>
                        <div class="max-w-xs">
                          <p class="font-medium mb-1">เกณฑ์การประเมินโอกาสเกิด:</p>
                          <ul class="list-disc pl-4 text-xs">
                            <li v-for="criteria in selectedLikelihoodCriteria" :key="criteria.id">
                              <span class="font-medium">ระดับ {{ criteria.level }}: {{ criteria.name }}</span>
                              <p v-if="criteria.description" class="text-xs text-gray-200">{{ criteria.description }}</p>
                            </li>
                          </ul>
                        </div>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                </div>
                <Select v-model="form.likelihood_level">
                  <SelectTrigger 
                    class="w-full mt-1" 
                    :class="{ 'border-red-500': form.errors.likelihood_level }"
                  >
                    <SelectValue placeholder="เลือกระดับโอกาสเกิด" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="1">1 - น้อยมาก</SelectItem>
                    <SelectItem value="2">2 - น้อย</SelectItem>
                    <SelectItem value="3">3 - ปานกลาง</SelectItem>
                    <SelectItem value="4">4 - สูง</SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.likelihood_level" class="text-red-500 text-sm mt-1">
                  {{ form.errors.likelihood_level }}
                </div>
              </div>

              <!-- ระดับผลกระทบ -->
              <div>
                <div class="flex items-center justify-between">
                  <Label for="impact_level">ระดับผลกระทบ <span class="text-red-500">*</span></Label>
                  <TooltipProvider v-if="selectedImpactCriteria.length > 0">
                    <Tooltip>
                      <TooltipTrigger>
                        <InfoIcon class="h-4 w-4 text-gray-500" />
                      </TooltipTrigger>
                      <TooltipContent>
                        <div class="max-w-xs">
                          <p class="font-medium mb-1">เกณฑ์การประเมินผลกระทบ:</p>
                          <ul class="list-disc pl-4 text-xs">
                            <li v-for="criteria in selectedImpactCriteria" :key="criteria.id">
                              <span class="font-medium">ระดับ {{ criteria.level }}: {{ criteria.name }}</span>
                              <p v-if="criteria.description" class="text-xs text-gray-200">{{ criteria.description }}</p>
                            </li>
                          </ul>
                        </div>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                </div>
                <Select v-model="form.impact_level">
                  <SelectTrigger 
                    class="w-full mt-1" 
                    :class="{ 'border-red-500': form.errors.impact_level }"
                  >
                    <SelectValue placeholder="เลือกระดับผลกระทบ" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="1">1 - น้อยมาก</SelectItem>
                    <SelectItem value="2">2 - น้อย</SelectItem>
                    <SelectItem value="3">3 - ปานกลาง</SelectItem>
                    <SelectItem value="4">4 - สูง</SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.impact_level" class="text-red-500 text-sm mt-1">
                  {{ form.errors.impact_level }}
                </div>
              </div>
            </div>

            <!-- แสดงระดับความเสี่ยง -->
            <div v-if="form.likelihood_level && form.impact_level" class="p-4 bg-gray-50 rounded-lg">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-700">ระดับความเสี่ยง:</p>
                  <h3 class="text-2xl font-bold" :class="riskLevel.color">
                    {{ riskLevel.text }} ({{ riskScore }})
                  </h3>
                </div>
                <Button variant="outline" size="sm" @click="toggleRiskMatrix" type="button">
                  <BarChart4Icon class="h-4 w-4 mr-2" />
                  {{ showMatrix ? 'ซ่อน' : 'แสดง' }}แผนภูมิความเสี่ยง
                </Button>
              </div>
              
              <!-- Risk Matrix -->
              <div v-if="showMatrix" class="mt-4">
                <RiskMatrix 
                  :likelihood="parseInt(form.likelihood_level)" 
                  :impact="parseInt(form.impact_level)" 
                />
              </div>
            </div>

            <!-- บันทึกเพิ่มเติม -->
            <div>
              <Label for="notes">บันทึกเพิ่มเติม</Label>
              <Textarea
                id="notes"
                v-model="form.notes"
                class="mt-1"
                rows="4"
                placeholder="บันทึกเพิ่มเติมเกี่ยวกับการประเมินความเสี่ยงนี้"
              />
              <div v-if="form.errors.notes" class="text-red-500 text-sm mt-1">
                {{ form.errors.notes }}
              </div>
            </div>
          </form>
        </CardContent>
        <CardFooter class="flex justify-end gap-4">
          <Button variant="outline" @click="$inertia.visit(route('risk-assessments.index'))">
            ยกเลิก
          </Button>
          <Button @click="submitForm" :disabled="form.processing">
            บันทึกการเปลี่ยนแปลง
          </Button>
        </CardFooter>
      </Card>
    </div>
  </AppLayout>
</template>

<script lang="ts">
// ฟังก์ชันสำหรับจัดกลุ่มข้อมูล
function groupBy<T, K>(array: T[], getKey: (item: T) => K): Record<string, T[]> {
  return array.reduce((result, item) => {
    const key = getKey(item) as unknown as string
    if (!result[key]) {
      result[key] = []
    }
    result[key].push(item)
    return result
  }, {} as Record<string, T[]>)
}
</script>
