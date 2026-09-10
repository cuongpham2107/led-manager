export type AssetStatusValue = 'ready' | 'in_event' | 'in_transit' | 'repairing' | 'disposed';

export interface User {
  id: number;
  name: string;
  email: string;
  phone?: string;
  warehouse?: {
    id: number;
    code: string;
    name: string;
    city?: string;
  } | null;
  roles: string[];
}

export interface Warehouse {
  id: number;
  code: string;
  name: string;
  city?: string;
  address?: string;
  is_active: boolean;
  assets_count?: number;
  ready_assets_count?: number;
}

export interface Asset {
  id: number;
  serial_no: string;
  qr_code?: string;
  size?: string;
  manufactured_date?: string;
  purchase_cost?: number;
  current_status: {
    value: AssetStatusValue;
    label: string;
    color: string;
  };
  product_line?: {
    id: number;
    name: string;
    pitch?: number;
    environment?: string;
  } | null;
  device_type?: {
    id: number;
    name: string;
    category?: string;
  } | null;
  current_warehouse?: {
    id: number;
    code: string;
    name: string;
  } | null;
}

export interface CheckoutBatchItem {
  id: number;
  asset_id: number;
  is_dispatched: boolean;
  dispatched_at?: string;
  dispatched_by?: {
    id: number;
    name: string;
  };
  asset?: Asset;
}

export interface CheckoutBatch {
  id: number;
  code: string;
  status: {
    value: 'pending' | 'in_progress' | 'dispatched' | 'completed' | 'cancelled';
    label: string;
    color: string;
  };
  required_area_m2: number;
  target_cabinets_count: number;
  scanned_count: number;
  progress_percent: number;
  expected_return_date?: string;
  dispatched_at?: string;
  order?: {
    id: number;
    order_no: string;
    event?: string;
    request_date?: string;
    expected_return_date?: string;
    status?: string;
  };
  customer?: {
    id: number;
    name: string;
    phone?: string;
    company_name?: string;
  };
  warehouse?: {
    id: number;
    code: string;
    name: string;
  };
  device_type?: {
    id: number;
    name: string;
  };
  items?: CheckoutBatchItem[];
  created_at?: string;
}

export type ReturnGradeValue = 'normal' | 'damaged';

export interface ReturnBatchItem {
  id: number;
  asset_id: number;
  is_received: boolean;
  grade: {
    value: ReturnGradeValue;
    label: string;
    color: string;
  } | null;
  grade_note?: string;
  received_at?: string;
  received_by?: {
    id: number;
    name: string;
  };
  asset?: Asset;
}

export interface ReturnBatch {
  id: number;
  code: string;
  status: {
    value: 'pending' | 'in_progress' | 'completed';
    label: string;
    color: string;
  };
  return_date?: string;
  note?: string;
  completed_at?: string;
  total_items_count: number;
  normal_count: number;
  damaged_count: number;
  checkout_batch?: {
    id: number;
    code: string;
    warehouse?: {
      id: number;
      name: string;
    };
    order?: {
      id: number;
      order_no: string;
      event?: string;
    };
    customer?: {
      id: number;
      name: string;
    };
  };
  items?: ReturnBatchItem[];
  created_at?: string;
}

export type CheckinBatchTypeValue = 'production' | 'purchase' | 'transfer';

export interface CheckinBatchItem {
  id: number;
  asset_id: number;
  condition?: 'ok' | 'fault';
  condition_note?: string;
  is_received: boolean;
  received_at?: string;
  received_by?: {
    id: number;
    name: string;
  };
  asset?: Asset;
}

export interface CheckinBatch {
  id: number;
  code: string;
  status: {
    value: 'pending' | 'in_progress' | 'completed' | 'cancelled';
    label: string;
    color: string;
  };
  batch_type: {
    value: CheckinBatchTypeValue;
    label: string;
    color: string;
  };
  quantity: number;
  target_items_count: number;
  scanned_count: number;
  progress_percent: number;
  expected_date?: string;
  completed_at?: string;
  production_note?: string;
  note?: string;
  warehouse?: {
    id: number;
    code: string;
    name: string;
  };
  product_line?: {
    id: number;
    code: string;
    name: string;
  };
  device_type?: {
    id: number;
    name: string;
  };
  items?: CheckinBatchItem[];
  created_at?: string;
}
