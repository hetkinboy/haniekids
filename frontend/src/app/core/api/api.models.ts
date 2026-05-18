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
  summary?: {
    total_products: number;
    active_products: number;
    inactive_products: number;
  };
}

export interface ProductCopyResult {
  product: Product;
  groups_copied: number;
  options_copied: number;
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
  summary?: {
    total_skus: number;
    sellable_skus: number;
    active_skus: number;
  };
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

export interface TiktokInventorySyncItem {
  product_sku_id: number;
  tiktok_product_id: string;
  tiktok_sku_id: string;
  combo_quantity: number;
  quantity: number;
  status: 'synced' | 'failed' | 'skipped';
  mode?: 'real' | 'dry_run';
  error?: string;
  response?: unknown;
}

export interface StockAdjustResult {
  stock: StockBySize;
  movement: StockMovement;
  tiktok_sync: TiktokInventorySyncItem[];
}

export interface PublicStockCombo {
  sku_id: number;
  sku_code: string;
  combo_name: string;
  combo_option_id: number;
  combo_quantity: number;
  quantity: number;
  editable: boolean;
}

export interface PublicStockRow {
  stock_id: number;
  size_option_id: number;
  size_name: string;
  quantity_on_hand: number;
  quantity_available: number;
  combos: PublicStockCombo[];
}

export interface PublicStockProduct {
  id: number;
  product_code: string;
  name: string;
  rows: PublicStockRow[];
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
  can_edit?: boolean;
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
  summary?: {
    total_imports: number;
    total_quantity: number;
    total_amount: number;
  };
}

export interface OperatingCost {
  id: number;
  cost_date: string;
  cost_type: string;
  amount: number;
  allocation_type: string;
  product_id?: number | null;
  product_name?: string | null;
  order_id?: number | null;
  order_code?: string | null;
  note?: string | null;
}

export interface OperatingCostListData {
  items: OperatingCost[];
  pager: Pager;
  summary?: {
    total_costs: number;
    total_amount: number;
  };
}

export interface OperatingFeeSetting {
  id: number;
  fee_key: string;
  label: string;
  value_type: 'percent' | 'fixed';
  rate: number;
  status: 'active' | 'inactive';
}

export interface OrderItem {
  id: number;
  order_id: number;
  product_id: number;
  sku_id: number;
  sku_code: string;
  sku_display_name: string;
  size_option_id: number;
  size_name?: string | null;
  combo_option_id: number;
  combo_name?: string | null;
  combo_quantity: number;
  quantity: number;
  stock_quantity_deducted: number;
  sale_price: number;
  cost_price: number;
  total_sale: number;
  total_cost: number;
  allocated_fee: number;
  profit: number;
}

export interface TiktokOrderLineItem {
  seller_sku?: string | null;
  tiktok_sku_id?: string | null;
  sku_name?: string | null;
  product_name?: string | null;
  quantity: number;
  sale_price: number;
  original_price: number;
  matched_sku_id?: number | null;
  matched_sku_code?: string | null;
}

export interface Order {
  id: number;
  order_code: string;
  platform: string;
  tiktok_order_id?: string | null;
  customer_name?: string | null;
  customer_phone?: string | null;
  customer_address?: string | null;
  buyer_email?: string | null;
  shipping_provider?: string | null;
  payment_method_name?: string | null;
  delivery_option_name?: string | null;
  tiktok_status?: string | null;
  tiktok_raw_json?: string | null;
  order_date: string;
  status: 'pending' | 'confirmed' | 'shipped' | 'completed' | 'cancelled' | 'returned';
  gross_amount: number;
  discount_amount: number;
  platform_fee: number;
  transaction_fee: number;
  shipping_fee: number;
  cod_amount: number;
  net_revenue: number;
  total_cost: number;
  total_profit: number;
  stock_deducted: number;
  stock_returned: number;
  return_fee: number;
  note?: string | null;
  profit_breakdown?: OrderProfitBreakdown;
  items?: OrderItem[];
  tiktok_line_items?: TiktokOrderLineItem[];
}

export interface OrderProfitBreakdown {
  gross_amount: number;
  customer_paid: number;
  discount_amount: number;
  platform_fee: number;
  transaction_fee: number;
  shipping_fee: number;
  other_fee: number;
  settlement_amount: number;
  total_cost: number;
  total_profit: number;
}

export interface OrderListData {
  items: Order[];
  pager: Pager;
  summary?: {
    total_orders: number;
    net_revenue: number;
    total_profit: number;
    total_cost: number;
    unmatched_orders: number;
  };
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
  summary?: {
    total_products: number;
    active_products: number;
    linked_products: number;
  };
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

export interface TiktokConnection {
  id: number;
  shop_name?: string | null;
  shop_id: string;
  shop_cipher: string;
  app_key: string;
  app_secret?: string | null;
  base_url: string;
  auth_base_url: string;
  access_token?: string | null;
  refresh_token?: string | null;
  access_token_expires_at?: string | null;
  refresh_token_expires_at?: string | null;
  status: 'active' | 'inactive';
  last_synced_at?: string | null;
  last_error?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface TiktokWebhookEvent {
  id: number;
  connection_id?: number | null;
  shop_id?: string | null;
  event_type?: string | null;
  order_id?: string | null;
  order_status?: string | null;
  payload_json: string;
  process_status: 'received' | 'processed' | 'rejected' | 'failed';
  error_message?: string | null;
  received_at: string;
  processed_at?: string | null;
}

export interface ReportOverview {
  date_from: string;
  date_to: string;
  orders: {
    count: number;
    gross_amount: number;
    net_revenue: number;
    total_cost: number;
    gross_profit: number;
  };
  operating_cost: number;
  net_profit_after_operating_cost: number;
  stock: {
    quantity_on_hand: number;
    quantity_available: number;
    stock_value: number;
  };
}

export interface ReportProductRow {
  product_id: number;
  product_code: string;
  product_name: string;
  quantity_sold: number;
  total_sale: number;
  total_cost: number;
  profit: number;
}

export interface ReportSkuRow {
  sku_id: number;
  sku_code: string;
  sku_display_name: string;
  size_name?: string | null;
  combo_name?: string | null;
  combo_quantity: number;
  quantity_sold: number;
  total_sale: number;
  total_cost: number;
  profit: number;
}

export interface ReportStockRow {
  product_id: number;
  product_code: string;
  product_name: string;
  size_option_id: number;
  size_name: string;
  quantity_on_hand: number;
  quantity_reserved: number;
  quantity_available: number;
  avg_cost: number;
  stock_value: number;
}
