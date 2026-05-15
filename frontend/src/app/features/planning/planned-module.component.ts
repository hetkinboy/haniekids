import { ChangeDetectionStrategy, Component, computed, inject } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzTableModule } from 'ng-zorro-antd/table';
import { NzTagModule } from 'ng-zorro-antd/tag';

interface ModulePlan {
  title: string;
  subtitle: string;
  backendStatus: string;
  backendReady: boolean;
  apiPlan: string[];
  columns: string[];
}

const PLANS: Record<string, ModulePlan> = {
  tiktokProducts: {
    title: 'Sản phẩm TikTok',
    subtitle: 'Liên kết sản phẩm/SKU TikTok với SKU kho để tính giá vốn và lợi nhuận.',
    backendStatus: 'Đã có bảng tiktok_products/tiktok_skus và API liên kết SKU kho.',
    backendReady: true,
    apiPlan: [
      'Migration tiktok_products: product_id, tiktok_product_id, name, status.',
      'Migration tiktok_skus: tiktok_product_id, product_sku_id, tiktok_sku_id, seller_sku, status.',
      'GET/POST/PUT /api/tiktok-products, GET /api/tiktok-products/{id}/skus.',
    ],
    columns: ['Sản phẩm TikTok', 'SKU TikTok', 'SKU kho liên kết', 'Trạng thái', 'Thao tác'],
  },
  tiktokOrders: {
    title: 'Đơn hàng TikTok',
    subtitle: 'Quản lý đơn TikTok/thủ công và trừ tồn size theo combo_quantity.',
    backendStatus: 'Đã có OrdersController và route tạo đơn/đổi trạng thái.',
    backendReady: true,
    apiPlan: [
      'OrdersController: GET/POST/GET detail/PUT status.',
      'Khi tạo đơn: copy cost_price, sale_price, combo_quantity vào order_items.',
      'Trừ stock_by_size = quantity x combo_quantity, ghi stock_movements type sale.',
      'Xử lý cancel/return: cộng kho lại theo size.',
    ],
    columns: ['Mã đơn', 'Ngày tạo', 'Khách hàng', 'Doanh thu', 'Lợi nhuận', 'Trạng thái'],
  },
  settlements: {
    title: 'Đối soát tiền',
    subtitle: 'So sánh tiền sàn báo, tiền thực nhận, phí sàn và chênh lệch theo đơn.',
    backendStatus: 'Đã có migration settlements/settlement_items và API tạo kỳ đối soát.',
    backendReady: true,
    apiPlan: [
      'Migration settlements và settlement_items.',
      'Import file đối soát TikTok hoặc nhập thủ công.',
      'GET /api/settlements, POST /api/settlements, GET /api/settlements/{id}.',
      'Tính mismatch theo order_code/tiktok_order_id.',
    ],
    columns: ['Mã đối soát', 'Kỳ đối soát', 'Tiền sàn báo', 'Tiền thực nhận', 'Chênh lệch', 'Trạng thái'],
  },
  operatingCosts: {
    title: 'Chi phí vận hành',
    subtitle: 'Ghi nhận chi phí ads, vật tư, nhân sự, khấu hao và phân bổ vào lợi nhuận.',
    backendStatus: 'Đã có CRUD API operating_costs.',
    backendReady: true,
    apiPlan: [
      'OperatingCostsController CRUD.',
      'Lọc theo ngày, cost_type, product_id, order_id.',
      'Quy tắc allocation_type: manual/product/order/period.',
    ],
    columns: ['Ngày', 'Loại chi phí', 'Số tiền', 'Phân bổ', 'Sản phẩm/Đơn hàng', 'Ghi chú'],
  },
  reports: {
    title: 'Báo cáo',
    subtitle: 'Báo cáo doanh thu, giá vốn, chi phí, lợi nhuận theo sản phẩm/SKU/kho.',
    backendStatus: 'Đã có ReportsController và API tổng hợp.',
    backendReady: true,
    apiPlan: [
      'GET /api/reports/overview.',
      'GET /api/reports/by-product.',
      'GET /api/reports/by-sku.',
      'GET /api/reports/stock.',
    ],
    columns: ['Chỉ số', 'Hôm nay', '7 ngày', 'Tháng này', 'Ghi chú'],
  },
};

@Component({
  selector: 'app-planned-module',
  imports: [RouterLink, NzButtonModule, NzIconModule, NzTableModule, NzTagModule],
  templateUrl: './planned-module.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PlannedModuleComponent {
  private readonly route = inject(ActivatedRoute);
  readonly previewRows = [{ id: 1, value: '-' }, { id: 2, value: '-' }, { id: 3, value: '-' }];
  readonly plan = computed(() => PLANS[this.route.snapshot.data['module'] as string] ?? PLANS['reports']);
}
