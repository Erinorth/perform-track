<template>
    <Drawer :open="true" @update:open="$emit('close')">
      <DrawerContent>
        <DrawerHeader>
          <DrawerTitle>ประวัติการเปลี่ยนแปลงความเสี่ยง</DrawerTitle>
          <DrawerDescription>
            แสดงประวัติการเปลี่ยนแปลงของความเสี่ยงตามปีงบประมาณ
          </DrawerDescription>
        </DrawerHeader>
        
        <div class="p-4">
          <Tabs defaultValue="history" class="w-full">
            <TabsList class="grid w-full grid-cols-2">
              <TabsTrigger value="history">ประวัติการเปลี่ยนแปลง</TabsTrigger>
              <TabsTrigger value="yearly">เปรียบเทียบรายปี</TabsTrigger>
            </TabsList>
            
            <TabsContent value="history">
              <Card>
                <CardContent class="pt-6">
                  <ul class="space-y-6">
                    <li v-for="(history, index) in riskHistory" :key="index" class="relative pl-6 pb-6 border-l border-gray-200">
                      <div class="absolute w-3 h-3 bg-blue-500 rounded-full -left-1.5 mt-1.5"></div>
                      <div class="flex justify-between mb-1">
                        <span class="font-medium text-gray-900">{{ getActionText(history.action_type) }}</span>
                        <span class="text-sm text-gray-500">{{ formatDate(history.created_at) }}</span>
                      </div>
                      <div class="text-sm text-gray-600">
                        <p>โดย: {{ history.user_name }}</p>
                        <div v-if="history.changes" class="mt-2 p-3 bg-gray-50 rounded-md">
                          <div v-for="(value, key) in history.changes" :key="key" class="flex flex-wrap text-xs">
                            <div class="w-1/4 text-gray-500">{{ getFieldName(key) }}</div>
                            <div class="w-3/4">
                              <span class="text-red-500 line-through">{{ value.from || '-' }}</span>
                              <span class="mx-2">→</span>
                              <span class="text-green-500">{{ value.to || '-' }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </li>
                    
                    <li v-if="riskHistory.length === 0" class="text-center py-6 text-gray-500">
                      ไม่พบประวัติการเปลี่ยนแปลงของความเสี่ยงนี้
                    </li>
                  </ul>
                </CardContent>
              </Card>
            </TabsContent>
            
            <TabsContent value="yearly">
              <Card>
                <CardContent class="pt-6">
                  <div v-if="yearlyData.length > 0">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>ปีงบประมาณ</TableHead>
                          <TableHead>ชื่อความเสี่ยง</TableHead>
                          <TableHead>คำอธิบาย</TableHead>
                          <TableHead>สถานะ</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        <TableRow v-for="item in yearlyData" :key="item.year">
                          <TableCell>{{ item.year }}</TableCell>
                          <TableCell>{{ item.risk_name }}</TableCell>
                          <TableCell>
                            <div class="max-h-20 overflow-y-auto">{{ item.description }}</div>
                          </TableCell>
                          <TableCell>
                            <Badge :variant="item.active ? 'success' : 'secondary'">
                              {{ item.active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                            </Badge>
                          </TableCell>
                        </TableRow>
                      </TableBody>
                    </Table>
                  </div>
                  <div v-else class="text-center py-6 text-gray-500">
                    ไม่พบข้อมูลเปรียบเทียบรายปี
                  </div>
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
        
        <DrawerFooter>
          <Button variant="outline" @click="$emit('close')">ปิด</Button>
        </DrawerFooter>
      </DrawerContent>
    </Drawer>
  </template>
  
  <script setup lang="ts">
  import { ref, onMounted } from 'vue';
  
  // Props
  interface Props {
    riskId: number | undefined;
  }
  
  const props = defineProps<Props>();
  const emit = defineEmits(['close']);
  
  // State
  interface RiskHistory {
    id: number;
    action_type: string;
    user_name: string;
    changes: Record<string, {from: string, to: string}>;
    created_at: string;
  }
  
  interface YearlyData {
    year: number;
    risk_name: string;
    description: string;
    active: boolean;
  }
  
  const riskHistory = ref<RiskHistory[]>([]);
  const yearlyData = ref<YearlyData[]>([]);
  const isLoading = ref(false);
  
  onMounted(async () => {
    if (!props.riskId) return;
    
    isLoading.value = true;
    try {
      // โหลดข้อมูลประวัติการเปลี่ยนแปลง
      const historyResponse = await fetch(`/api/organizational-risks/${props.riskId}/history`);
      riskHistory.value = await historyResponse.json();
      
      // โหลดข้อมูลเปรียบเทียบรายปี
      const yearlyResponse = await fetch(`/api/organizational-risks/${props.riskId}/yearly-comparison`);
      yearlyData.value = await yearlyResponse.json();
    } catch (error) {
      console.error(error);
    } finally {
      isLoading.value = false;
    }
  });
  
  // Helper functions
  const getActionText = (action: string): string => {
    const actionMap: Record<string, string> = {
      'create': 'สร้างความเสี่ยงใหม่',
      'update': 'แก้ไขข้อมูลความเสี่ยง',
      'delete': 'ลบความเสี่ยง',
      'restore': 'กู้คืนความเสี่ยง'
    };
    
    return actionMap[action] || action;
  };
  
  const getFieldName = (field: string): string => {
    const fieldMap: Record<string, string> = {
      'risk_name': 'ชื่อความเสี่ยง',
      'description': 'คำอธิบาย',
      'year': 'ปีงบประมาณ',
      'active': 'สถานะ'
    };
    
    return fieldMap[field] || field;
  };
  
  const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('th-TH', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  };
  </script>
  