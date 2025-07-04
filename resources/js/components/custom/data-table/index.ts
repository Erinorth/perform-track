// ไฟล์: resources\js\components\ui\data-table\index.ts
// รวบรวม export ของทุก component ที่เกี่ยวข้องกับ DataTable

// นำเข้าและส่งออก DataTableColumnHeader component
export { default as DataTableColumnHeader } from './DataTableColumnHeader.vue'

// นำเข้าและส่งออก DataTableViewOptions component จาก features/organizational_risk
export { default as DataTableViewOptions } from './DataTableViewOptions.vue'

// เพิ่ม log เพื่อตรวจสอบการโหลด
console.log('Data Table components ถูกโหลด')

// สามารถเพิ่ม export สำหรับ component อื่นๆ ของ DataTable ในอนาคต เช่น
export { default as DataTablePagination } from './DataTablePagination.vue'
export { default as DataTableDropDown } from './DataTableDropDown.vue'
export { default as BulkActionMenu } from './BulkActionMenu.vue'
