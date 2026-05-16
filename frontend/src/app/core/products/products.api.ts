import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

import { API_BASE_URL } from '../api/api.config';
import {
  ApiResponse,
  Pager,
  Product,
  ProductCopyResult,
  ProductListData,
  ProductSku,
  PublicStockProduct,
  OperatingCost,
  OperatingFeeSetting,
  OperatingCostListData,
  Order,
  OrderListData,
  PurchaseImport,
  PurchaseImportListData,
  ReportOverview,
  ReportProductRow,
  ReportSkuRow,
  ReportStockRow,
  SkuListData,
  StockAdjustResult,
  StockListData,
  StockMovementListData,
  TiktokProduct,
  TiktokProductListData,
  TiktokImportResult,
  TiktokConnection,
  TiktokSku,
  TiktokWebhookEvent,
  VariantGroup,
  VariantOption,
} from '../api/api.models';

@Injectable({ providedIn: 'root' })
export class ProductsApi {
  constructor(private readonly http: HttpClient) {}

  products(params: { keyword?: string; status?: string; page?: number; pageSize?: number }): Observable<ApiResponse<ProductListData>> {
    return this.http.get<ApiResponse<ProductListData>>(`${API_BASE_URL}/products`, { params: this.params(params) });
  }

  createProduct(payload: Partial<Product>): Observable<ApiResponse<Product>> {
    return this.http.post<ApiResponse<Product>>(`${API_BASE_URL}/products`, payload);
  }

  updateProduct(id: number, payload: Partial<Product>): Observable<ApiResponse<Product>> {
    return this.http.put<ApiResponse<Product>>(`${API_BASE_URL}/products/${id}`, payload);
  }

  copyProduct(id: number, payload: Partial<Product>): Observable<ApiResponse<ProductCopyResult>> {
    return this.http.post<ApiResponse<ProductCopyResult>>(`${API_BASE_URL}/products/${id}/copy`, payload);
  }

  deleteProduct(id: number): Observable<ApiResponse<unknown>> {
    return this.http.delete<ApiResponse<unknown>>(`${API_BASE_URL}/products/${id}`);
  }

  variantGroups(productId: number): Observable<ApiResponse<{ items: VariantGroup[] }>> {
    return this.http.get<ApiResponse<{ items: VariantGroup[] }>>(`${API_BASE_URL}/products/${productId}/variant-groups`);
  }

  createVariantGroup(productId: number, payload: Partial<VariantGroup>): Observable<ApiResponse<VariantGroup>> {
    return this.http.post<ApiResponse<VariantGroup>>(`${API_BASE_URL}/products/${productId}/variant-groups`, payload);
  }

  updateVariantGroup(id: number, payload: Partial<VariantGroup>): Observable<ApiResponse<VariantGroup>> {
    return this.http.put<ApiResponse<VariantGroup>>(`${API_BASE_URL}/variant-groups/${id}`, payload);
  }

  deleteVariantGroup(id: number): Observable<ApiResponse<unknown>> {
    return this.http.delete<ApiResponse<unknown>>(`${API_BASE_URL}/variant-groups/${id}`);
  }

  createVariantOption(groupId: number, payload: Partial<VariantOption>): Observable<ApiResponse<VariantOption>> {
    return this.http.post<ApiResponse<VariantOption>>(`${API_BASE_URL}/variant-groups/${groupId}/options`, payload);
  }

  updateVariantOption(id: number, payload: Partial<VariantOption>): Observable<ApiResponse<VariantOption>> {
    return this.http.put<ApiResponse<VariantOption>>(`${API_BASE_URL}/variant-options/${id}`, payload);
  }

  deleteVariantOption(id: number): Observable<ApiResponse<unknown>> {
    return this.http.delete<ApiResponse<unknown>>(`${API_BASE_URL}/variant-options/${id}`);
  }

  generateSkus(productId: number, payload: {
    overwrite: boolean;
    sku_prefix: string;
    force_update_cost?: boolean;
    size_prefix?: string;
    size_prefix_position?: string;
    combo_prefix?: string;
    combo_prefix_position?: string;
    variant_order?: string;
  }): Observable<ApiResponse<unknown>> {
    return this.http.post<ApiResponse<unknown>>(`${API_BASE_URL}/products/${productId}/generate-skus`, payload);
  }

  skus(productId: number, params: { keyword?: string; is_sellable?: string; is_active?: string; page?: number; pageSize?: number }): Observable<ApiResponse<SkuListData>> {
    return this.http.get<ApiResponse<SkuListData>>(`${API_BASE_URL}/products/${productId}/skus`, { params: this.params(params) });
  }

  updateSku(id: number, payload: Partial<ProductSku>): Observable<ApiResponse<ProductSku>> {
    return this.http.put<ApiResponse<ProductSku>>(`${API_BASE_URL}/skus/${id}`, payload);
  }

  stock(productId: number): Observable<ApiResponse<StockListData>> {
    return this.http.get<ApiResponse<StockListData>>(`${API_BASE_URL}/products/${productId}/stock`);
  }

  stockMovements(params: { product_id?: number; size_option_id?: number; movement_type?: string; page?: number; pageSize?: number }): Observable<ApiResponse<StockMovementListData>> {
    return this.http.get<ApiResponse<StockMovementListData>>(`${API_BASE_URL}/stock/movements`, { params: this.params(params) });
  }

  adjustStock(payload: { product_id: number; size_option_id: number; mode: string; quantity: number; unit_cost?: number; note?: string }): Observable<ApiResponse<StockAdjustResult>> {
    return this.http.post<ApiResponse<StockAdjustResult>>(`${API_BASE_URL}/stock/adjust`, payload);
  }

  publicStock(): Observable<ApiResponse<{ items: PublicStockProduct[] }>> {
    return this.http.get<ApiResponse<{ items: PublicStockProduct[] }>>(`${API_BASE_URL}/public-stock`);
  }

  updatePublicComboOne(payload: { product_id: number; size_option_id: number; quantity: number }): Observable<ApiResponse<StockAdjustResult>> {
    return this.http.post<ApiResponse<StockAdjustResult>>(`${API_BASE_URL}/public-stock/combo-1`, payload);
  }

  purchaseImports(params: { keyword?: string; date_from?: string; date_to?: string; page?: number; pageSize?: number }): Observable<ApiResponse<PurchaseImportListData>> {
    return this.http.get<ApiResponse<PurchaseImportListData>>(`${API_BASE_URL}/purchase-imports`, { params: this.params(params) });
  }

  createPurchaseImport(payload: unknown): Observable<ApiResponse<PurchaseImport>> {
    return this.http.post<ApiResponse<PurchaseImport>>(`${API_BASE_URL}/purchase-imports`, payload);
  }

  purchaseImport(id: number): Observable<ApiResponse<PurchaseImport>> {
    return this.http.get<ApiResponse<PurchaseImport>>(`${API_BASE_URL}/purchase-imports/${id}`);
  }

  updatePurchaseImport(id: number, payload: unknown): Observable<ApiResponse<PurchaseImport>> {
    return this.http.put<ApiResponse<PurchaseImport>>(`${API_BASE_URL}/purchase-imports/${id}`, payload);
  }

  operatingCosts(params: { cost_type?: string; date_from?: string; date_to?: string; page?: number; pageSize?: number }): Observable<ApiResponse<OperatingCostListData>> {
    return this.http.get<ApiResponse<OperatingCostListData>>(`${API_BASE_URL}/operating-costs`, { params: this.params(params) });
  }

  createOperatingCost(payload: Partial<OperatingCost>): Observable<ApiResponse<OperatingCost>> {
    return this.http.post<ApiResponse<OperatingCost>>(`${API_BASE_URL}/operating-costs`, payload);
  }

  updateOperatingCost(id: number, payload: Partial<OperatingCost>): Observable<ApiResponse<OperatingCost>> {
    return this.http.put<ApiResponse<OperatingCost>>(`${API_BASE_URL}/operating-costs/${id}`, payload);
  }

  deleteOperatingCost(id: number): Observable<ApiResponse<unknown>> {
    return this.http.delete<ApiResponse<unknown>>(`${API_BASE_URL}/operating-costs/${id}`);
  }

  operatingFeeSettings(): Observable<ApiResponse<{ items: OperatingFeeSetting[] }>> {
    return this.http.get<ApiResponse<{ items: OperatingFeeSetting[] }>>(`${API_BASE_URL}/operating-cost-settings`);
  }

  saveOperatingFeeSettings(items: OperatingFeeSetting[]): Observable<ApiResponse<{ items: OperatingFeeSetting[] }>> {
    return this.http.put<ApiResponse<{ items: OperatingFeeSetting[] }>>(`${API_BASE_URL}/operating-cost-settings`, { items });
  }

  orders(params: { keyword?: string; status?: string; platform?: string; date_from?: string; date_to?: string; page?: number; pageSize?: number }): Observable<ApiResponse<OrderListData>> {
    return this.http.get<ApiResponse<OrderListData>>(`${API_BASE_URL}/orders`, { params: this.params(params) });
  }

  order(id: number): Observable<ApiResponse<Order>> {
    return this.http.get<ApiResponse<Order>>(`${API_BASE_URL}/orders/${id}`);
  }

  updateOrderStatus(id: number, status: string): Observable<ApiResponse<Order>> {
    return this.http.put<ApiResponse<Order>>(`${API_BASE_URL}/orders/${id}/status`, { status });
  }

  importTiktokOrder(orderId: string): Observable<ApiResponse<{ raw: unknown; orders: Order[] }>> {
    return this.http.post<ApiResponse<{ raw: unknown; orders: Order[] }>>(`${API_BASE_URL}/tiktok/orders-new/${orderId}/import`, {});
  }

  tiktokProducts(params: { keyword?: string; status?: string; page?: number; pageSize?: number }): Observable<ApiResponse<TiktokProductListData>> {
    return this.http.get<ApiResponse<TiktokProductListData>>(`${API_BASE_URL}/tiktok-products`, { params: this.params(params) });
  }

  tiktokProduct(id: number): Observable<ApiResponse<TiktokProduct>> {
    return this.http.get<ApiResponse<TiktokProduct>>(`${API_BASE_URL}/tiktok-products/${id}`);
  }

  createTiktokProduct(payload: Partial<TiktokProduct>): Observable<ApiResponse<TiktokProduct>> {
    return this.http.post<ApiResponse<TiktokProduct>>(`${API_BASE_URL}/tiktok-products`, payload);
  }

  updateTiktokProduct(id: number, payload: Partial<TiktokProduct>): Observable<ApiResponse<TiktokProduct>> {
    return this.http.put<ApiResponse<TiktokProduct>>(`${API_BASE_URL}/tiktok-products/${id}`, payload);
  }

  deleteTiktokProduct(id: number): Observable<ApiResponse<unknown>> {
    return this.http.delete<ApiResponse<unknown>>(`${API_BASE_URL}/tiktok-products/${id}`);
  }

  tiktokProductSkus(tiktokProductId: number): Observable<ApiResponse<{ items: TiktokSku[] }>> {
    return this.http.get<ApiResponse<{ items: TiktokSku[] }>>(`${API_BASE_URL}/tiktok-products/${tiktokProductId}/skus`);
  }

  createTiktokSku(tiktokProductId: number, payload: Partial<TiktokSku>): Observable<ApiResponse<TiktokSku>> {
    return this.http.post<ApiResponse<TiktokSku>>(`${API_BASE_URL}/tiktok-products/${tiktokProductId}/skus`, payload);
  }

  updateTiktokSku(id: number, payload: Partial<TiktokSku>): Observable<ApiResponse<TiktokSku>> {
    return this.http.put<ApiResponse<TiktokSku>>(`${API_BASE_URL}/tiktok-skus/${id}`, payload);
  }

  deleteTiktokSku(id: number): Observable<ApiResponse<unknown>> {
    return this.http.delete<ApiResponse<unknown>>(`${API_BASE_URL}/tiktok-skus/${id}`);
  }

  importTiktokSearchResponse(payload: unknown): Observable<ApiResponse<TiktokImportResult>> {
    return this.http.post<ApiResponse<TiktokImportResult>>(`${API_BASE_URL}/tiktok/import-search-response`, payload);
  }

  importTiktokSearchUrl(url: string): Observable<ApiResponse<TiktokImportResult>> {
    return this.http.post<ApiResponse<TiktokImportResult>>(`${API_BASE_URL}/tiktok/import-search-url`, { url });
  }

  tiktokConnections(): Observable<ApiResponse<{ items: TiktokConnection[] }>> {
    return this.http.get<ApiResponse<{ items: TiktokConnection[] }>>(`${API_BASE_URL}/tiktok/connections`);
  }

  createTiktokConnection(payload: Partial<TiktokConnection>): Observable<ApiResponse<TiktokConnection>> {
    return this.http.post<ApiResponse<TiktokConnection>>(`${API_BASE_URL}/tiktok/connections`, payload);
  }

  updateTiktokConnection(id: number, payload: Partial<TiktokConnection>): Observable<ApiResponse<TiktokConnection>> {
    return this.http.put<ApiResponse<TiktokConnection>>(`${API_BASE_URL}/tiktok/connections/${id}`, payload);
  }

  tiktokWebhookEvents(params: { process_status?: string; page?: number; pageSize?: number }): Observable<ApiResponse<{ items: TiktokWebhookEvent[]; pager: Pager }>> {
    return this.http.get<ApiResponse<{ items: TiktokWebhookEvent[]; pager: Pager }>>(`${API_BASE_URL}/tiktok/webhook-events`, { params: this.params(params) });
  }

  reportOverview(params: { date_from?: string; date_to?: string }): Observable<ApiResponse<ReportOverview>> {
    return this.http.get<ApiResponse<ReportOverview>>(`${API_BASE_URL}/reports/overview`, { params: this.params(params) });
  }

  reportByProduct(params: { date_from?: string; date_to?: string }): Observable<ApiResponse<{ items: ReportProductRow[] }>> {
    return this.http.get<ApiResponse<{ items: ReportProductRow[] }>>(`${API_BASE_URL}/reports/by-product`, { params: this.params(params) });
  }

  reportBySku(params: { date_from?: string; date_to?: string }): Observable<ApiResponse<{ items: ReportSkuRow[] }>> {
    return this.http.get<ApiResponse<{ items: ReportSkuRow[] }>>(`${API_BASE_URL}/reports/by-sku`, { params: this.params(params) });
  }

  reportStock(): Observable<ApiResponse<{ items: ReportStockRow[] }>> {
    return this.http.get<ApiResponse<{ items: ReportStockRow[] }>>(`${API_BASE_URL}/reports/stock`);
  }

  private params(source: Record<string, string | number | undefined>): HttpParams {
    let params = new HttpParams();

    Object.entries(source).forEach(([key, value]) => {
      if (value !== undefined && value !== '') {
        params = params.set(key, String(value));
      }
    });

    return params;
  }
}
