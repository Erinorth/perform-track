// ไฟล์: resources\js\components\ui\data-table\tags.ts

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
  