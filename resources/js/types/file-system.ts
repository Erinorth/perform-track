// ======================== FILE AND SYSTEM TYPES ========================

export interface FileValidationResult {
  valid: boolean
  errors: string[]
  validFiles?: File[]
}

export interface UploadedFile {
  name: string
  size: number
  type: string
  url?: string
}

export interface SystemSettings {
  max_file_size: number
  allowed_file_types: string[]
  risk_matrix_size: number
  assessment_periods_per_year: number
}

// File utility functions
export const formatFileSize = (bytes: number): string => {
  if (!bytes || bytes === 0) return '0 B'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

export const getFileExtension = (filename: string): string => {
  return filename.split('.').pop()?.toLowerCase() || ''
}

export const isValidFileType = (filename: string, allowedTypes: string[]): boolean => {
  const extension = '.' + getFileExtension(filename)
  return allowedTypes.includes(extension)
}

export const validateFiles = (files: File[], settings: SystemSettings): FileValidationResult => {
  const errors: string[] = []
  const validFiles: File[] = []

  files.forEach(file => {
    // ตรวจสอบขนาดไฟล์
    if (file.size > settings.max_file_size) {
      errors.push(`ไฟล์ ${file.name} มีขนาดใหญ่เกินกว่า ${formatFileSize(settings.max_file_size)}`)
      return
    }

    // ตรวจสอบประเภทไฟล์
    if (!isValidFileType(file.name, settings.allowed_file_types)) {
      errors.push(`ไฟล์ ${file.name} เป็นประเภทที่ไม่รองรับ`)
      return
    }

    validFiles.push(file)
  })

  return {
    valid: errors.length === 0,
    errors,
    validFiles: validFiles.length > 0 ? validFiles : undefined
  }
}
