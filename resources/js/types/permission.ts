export interface UserPermission {
    id: number | string;
    egat_id: string;
    name: string;
    view: boolean;
    create: boolean;
    update: boolean;
    delete: boolean;
    [key: string]: any;
  }
  