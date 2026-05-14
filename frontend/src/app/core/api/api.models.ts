export interface ApiResponse<T> {
  status: boolean;
  message: string;
  data: T;
  errors?: Record<string, string>;
}

export interface Pager {
  page?: number;
  pageSize?: number;
  current_page?: number;
  page_size?: number;
  total: number;
  last_page?: number;
}

export interface Product {
  id: number;
  product_code: string;
  name: string;
  category?: string | null;
  description?: string | null;
  image_url?: string | null;
  status: 'active' | 'inactive';
}

export interface ProductListData {
  items: Product[];
  pager: Pager;
}

export interface VariantOption {
  id: number;
  variant_group_id: number;
  name: string;
  value?: string | null;
  base_cost: number;
  combo_quantity?: number | null;
  sort_order: number;
  is_active: boolean;
  status: string;
}

export interface VariantGroup {
  id: number;
  product_id: number;
  name: string;
  type: 'text' | 'combo';
  is_stock_dimension: boolean;
  sort_order: number;
  status: string;
  options: VariantOption[];
}

export interface ProductSku {
  id: number;
  sku_code: string;
  display_name: string;
  size_option_id: number;
  combo_option_id: number;
  size_name: string;
  combo_name: string;
  combo_quantity: number;
  suggested_cost: number;
  cost_price: number;
  sale_price: number;
  is_sellable: boolean;
  is_active: boolean;
}

export interface SkuListData {
  items: ProductSku[];
  pager: Pager;
}

export interface StockBySize {
  id: number;
  product_id: number;
  product_name?: string | null;
  size_option_id: number;
  size_name?: string | null;
  quantity_on_hand: number;
  quantity_reserved: number;
  quantity_available: number;
  avg_cost: number;
  stock_value: number;
}

export interface StockMovement {
  id: number;
  product_id: number;
  product_name?: string | null;
  size_option_id: number;
  size_name?: string | null;
  movement_type: string;
  quantity: number;
  quantity_before: number;
  quantity_after: number;
  unit_cost: number;
  reference_type?: string | null;
  reference_id?: number | null;
  purchase_import_id?: number | null;
  note?: string | null;
  created_at: string;
}

export interface StockListData {
  items: StockBySize[];
}

export interface StockMovementListData {
  items: StockMovement[];
  pager: Pager;
}

export interface PurchaseImport {
  id: number;
  import_code: string;
  supplier_name?: string | null;
  import_date: string;
  total_quantity: number;
  total_amount: number;
  note?: string | null;
  created_at: string;
  updated_at: string;
  items?: PurchaseImportItem[];
}

export interface PurchaseImportItem {
  id: number;
  purchase_import_id: number;
  product_id: number;
  product_name?: string | null;
  size_option_id: number;
  size_name?: string | null;
  quantity: number;
  unit_cost: number;
  total_cost: number;
}

export interface PurchaseImportListData {
  items: PurchaseImport[];
  pager: Pager;
}

export interface TiktokProduct {
  id: number;
  product_id?: number | null;
  product_code?: string | null;
  warehouse_product_name?: string | null;
  tiktok_product_id: string;
  name: string;
  shop_name?: string | null;
  status: 'active' | 'inactive';
  skus?: TiktokSku[];
}

export interface TiktokSku {
  id: number;
  tiktok_product_id: number;
  product_sku_id?: number | null;
  warehouse_sku_code?: string | null;
  warehouse_sku_name?: string | null;
  tiktok_sku_id: string;
  seller_sku?: string | null;
  name?: string | null;
  tiktok_price?: number;
  tiktok_inventory_quantity?: number;
  tiktok_warehouse_id?: string | null;
  status: 'active' | 'inactive';
}

export interface TiktokProductListData {
  items: TiktokProduct[];
  pager: Pager;
}

export interface TiktokImportResult {
  products_created: number;
  products_updated: number;
  skus_created: number;
  skus_updated: number;
  skus_linked: number;
  skus_unmatched: number;
  items: unknown[];
}
