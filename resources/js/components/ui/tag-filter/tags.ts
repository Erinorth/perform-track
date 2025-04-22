// ไฟล์: resources/js/types/tags.ts

export interface Tag {
    id: string;
    label: string;
    count?: number;
  }
  
  export interface TagFilterProps {
    title: string;
    tags: Tag[];
    selectedTags?: string[];
  }
  