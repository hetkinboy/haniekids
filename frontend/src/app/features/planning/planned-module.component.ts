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
  template: `
    <section class="app-shell space-y-6">
      <div class="page-header">
        <div>
          <h1 class="m-0 text-3xl font-bold tracking-tight text-slate-950">{{ plan().title }}</h1>
          <p class="m-0 mt-1 text-sm text-slate-500">{{ plan().subtitle }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <a nz-button routerLink="/dashboard">Dashboard</a>
          <button nz-button nzType="primary" disabled>
            <span nz-icon nzType="plus"></span>
            Thêm mới
          </button>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr]">
        <div class="metric-card">
          <div class="metric-label">Trạng thái backend</div>
          <div class="mt-3"><nz-tag [nzColor]="plan().backendReady ? 'green' : 'orange'">{{ plan().backendReady ? 'Sẵn sàng' : 'Cần bổ sung' }}</nz-tag></div>
          <div class="mt-3 text-sm text-slate-600">{{ plan().backendStatus }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Ưu tiên</div>
          <div class="metric-value text-[#006a65]">MVP</div>
          <div class="mt-2 text-xs text-slate-500">Backend đã sẵn sàng, bước tiếp theo là nối UI thật.</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Ngữ cảnh nghiệp vụ</div>
          <div class="mt-3 text-sm font-medium text-slate-800">Không tạo tồn kho theo combo. Đơn combo trừ kho theo size.</div>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[1fr_1fr]">
        <div class="surface p-5">
          <h2 class="m-0 mb-4 text-xl font-semibold text-slate-950">Kế hoạch backend cần viết</h2>
          <div class="space-y-3">
            @for (item of plan().apiPlan; track item) {
              <div class="rounded-lg bg-slate-100 p-3 text-sm text-slate-700">{{ item }}</div>
            }
          </div>
        </div>

        <div class="surface p-5">
          <h2 class="m-0 mb-4 text-xl font-semibold text-slate-950">Giao diện dự kiến</h2>
          <div class="status-note mb-4">Bảng bên dưới là layout giao diện theo Stitch. Nút tạo/sửa sẽ bật sau khi API thật được thêm.</div>
          <div class="data-table">
            <nz-table [nzData]="previewRows" [nzFrontPagination]="false" [nzScroll]="{ x: '760px' }">
              <thead>
                <tr>
                  @for (column of plan().columns; track column) {
                    <th>{{ column }}</th>
                  }
                </tr>
              </thead>
              <tbody>
                @for (row of previewRows; track row.id) {
                  <tr>
                    @for (column of plan().columns; track column) {
                      <td>{{ row.value }}</td>
                    }
                  </tr>
                }
              </tbody>
            </nz-table>
          </div>
        </div>
      </div>
    </section>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PlannedModuleComponent {
  private readonly route = inject(ActivatedRoute);
  readonly previewRows = [{ id: 1, value: '-' }, { id: 2, value: '-' }, { id: 3, value: '-' }];
  readonly plan = computed(() => PLANS[this.route.snapshot.data['module'] as string] ?? PLANS['reports']);
}
