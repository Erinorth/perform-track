// ===============================
// Type definitions สำหรับ Combobox Component
// ===============================

// Interface พื้นฐานสำหรับ option ใน combobox
export interface ComboboxOption {
  id: number | string;
  label: string; // ข้อความที่แสดงใน dropdown
  value?: any; // ค่าที่เก็บไว้ (optional)
  disabled?: boolean; // สถานะปิดการใช้งาน
  [key: string]: any; // properties เพิ่มเติม
}

// Props สำหรับ Combobox component
export interface ComboboxProps<T extends ComboboxOption> {
  // ข้อมูลพื้นฐาน
  options: T[]; // รายการตัวเลือกทั้งหมด
  modelValue?: T | null; // ค่าที่เลือกปัจจุบัน
  placeholder?: string; // ข้อความตัวอย่าง
  
  // การค้นหาและกรอง
  searchable?: boolean; // เปิด/ปิดการค้นหา
  searchPlaceholder?: string; // ข้อความตัวอย่างสำหรับการค้นหา
  filterFunction?: (option: T, searchTerm: string) => boolean; // ฟังก์ชันกรองข้อมูล
  
  // การแสดงผล
  displayFunction?: (option: T) => string; // ฟังก์ชันแสดงข้อความ
  emptyMessage?: string; // ข้อความเมื่อไม่พบข้อมูล
  loading?: boolean; // สถานะกำลังโหลด
  
  // การควบคุม
  disabled?: boolean; // ปิดการใช้งาน
  clearable?: boolean; // แสดงปุ่มล้างค่า
  required?: boolean; // บังคับเลือก
  
  // สไตล์
  size?: 'sm' | 'md' | 'lg'; // ขนาด
  variant?: 'default' | 'outline' | 'ghost'; // รูปแบบ
}

// Events ที่ส่งออกจาก Combobox
export interface ComboboxEmits<T extends ComboboxOption> {
  (e: 'update:modelValue', value: T | null): void;
  (e: 'select', value: T): void;
  (e: 'clear'): void;
  (e: 'search', searchTerm: string): void;
  (e: 'open'): void;
  (e: 'close'): void;
}
