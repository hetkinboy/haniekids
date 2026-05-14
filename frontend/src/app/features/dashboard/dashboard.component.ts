import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzTableModule } from 'ng-zorro-antd/table';
import { NzTagModule } from 'ng-zorro-antd/tag';

import { Product, PurchaseImport, StockBySize } from '../../core/api/api.models';
import { ProductsApi } from '../../core/products/products.api';

@Component({
  selector: 'app-dashboard',
  imports: [DecimalPipe, RouterLink, NzButtonModule, NzIconModule, NzTableModule, NzTagModule],
  template: `
    <section class="app-shell space-y-6">
      <div class="page-header">
        <div>
          <h1 class="m-0 text-3xl font-bold tracking-tight text-slate-950">Dashboard Tổng Quan</h1>
          <p class="m-0 mt-1 text-sm text-slate-500">Tổng hợp nhanh sản phẩm, tồn kho, nhập hàng và các module cần hoàn thiện backend.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button nz-button>
            <span nz-icon nzType="reload"></span>
            Hôm nay
          </button>
          <button nz-button nzType="primary">
            <span nz-icon nzType="save"></span>
            Xuất báo cáo
          </button>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="metric-card">
          <div class="flex items-start justify-between gap-3">
            <span class="metric-label">Tổng sản phẩm kho</span>
            <span class="text-[#006a65]" nz-icon nzType="appstore"></span>
          </div>
          <div class="metric-value">{{ productTotal() }}</div>
          <div class="mt-2 text-xs font-semibold text-[#006a65]">{{ activeProducts() }} đang kinh doanh</div>
        </div>
        <div class="metric-card">
          <div class="flex items-start justify-between gap-3">
            <span class="metric-label">Giá trị nhập gần đây</span>
            <span class="text-blue-700" nz-icon nzType="plus"></span>
          </div>
          <div class="metric-value">{{ importAmount() | number: '1.0-0' }} đ</div>
          <div class="mt-2 text-xs text-slate-500">{{ importQuantity() }} sản phẩm đã nhập</div>
        </div>
        <div class="metric-card">
          <div class="flex items-start justify-between gap-3">
            <span class="metric-label">Tồn có thể bán</span>
            <span class="text-emerald-700" nz-icon nzType="tags"></span>
          </div>
          <div class="metric-value text-[#006a65]">{{ availableStock() }}</div>
          <div class="mt-2 text-xs text-slate-500">Tổng hợp từ các size đã tải</div>
        </div>
        <div class="metric-card">
          <div class="flex items-start justify-between gap-3">
            <span class="metric-label">Size sắp hết</span>
            <span class="text-red-600" nz-icon nzType="reload"></span>
          </div>
          <div class="metric-value text-red-600">{{ lowStock().length }}</div>
          <div class="mt-2 text-xs text-red-600">Cần kiểm tra nhập hàng</div>
        </div>
        <div class="metric-card">
          <div class="flex items-start justify-between gap-3">
            <span class="metric-label">Module cần backend</span>
            <span class="text-amber-600" nz-icon nzType="edit"></span>
          </div>
          <div class="metric-value text-emerald-700">5</div>
          <div class="mt-2 text-xs text-slate-500">Đã có API, cần nối giao diện</div>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[2fr_1fr]">
        <div class="surface p-6">
          <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 class="m-0 text-xl font-semibold text-slate-950">Hiệu suất 7 ngày qua</h2>
              <p class="m-0 text-sm text-slate-500">Biểu đồ tạm theo phong cách Stitch, sẽ nối API báo cáo khi backend sẵn sàng.</p>
            </div>
            <div class="flex gap-4 text-xs font-semibold">
              <span class="inline-flex items-center gap-2"><i class="h-3 w-3 rounded-full bg-[#006a65]"></i>Doanh thu</span>
              <span class="inline-flex items-center gap-2"><i class="h-3 w-3 rounded-full bg-[#ee865a]"></i>Lợi nhuận</span>
            </div>
          </div>
          <div class="flex h-64 items-end gap-3 border-b border-l border-slate-200 px-3 pt-4">
            @for (bar of chartBars; track bar.day) {
              <div class="flex min-w-0 flex-1 flex-col justify-end gap-1">
                <div class="rounded-t bg-[#ee865a]/80" [style.height.%]="bar.profit"></div>
                <div class="rounded-t bg-[#006a65]/90" [style.height.%]="bar.revenue"></div>
                <span class="mt-2 truncate text-center text-[11px] text-slate-500">{{ bar.day }}</span>
              </div>
            }
          </div>
        </div>

        <div class="surface p-6">
          <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="m-0 text-xl font-semibold text-slate-950">Cảnh báo tồn kho</h2>
            <nz-tag nzColor="orange">Cần nhập hàng</nz-tag>
          </div>
          <div class="space-y-3">
            @for (item of lowStock(); track item.id) {
              <div class="rounded-lg bg-slate-100 p-3">
                <div class="font-semibold text-slate-950">{{ item.product_name || 'Sản phẩm' }}</div>
                <div class="text-sm text-red-600">Size {{ item.size_name }} còn {{ item.quantity_available }}</div>
              </div>
            } @empty {
              <div class="rounded-lg bg-slate-100 p-3 text-sm text-slate-500">Chưa có cảnh báo tồn kho trong các sản phẩm đã tải.</div>
            }
          </div>
          <a class="mt-4 block" nz-button nzType="default" routerLink="/stock">Xem tồn kho</a>
        </div>
      </div>

      <div class="surface overflow-hidden">
        <div class="border-b border-slate-200 p-5">
          <div class="flex items-center gap-2">
            <span class="text-red-600" nz-icon nzType="reload"></span>
            <h2 class="m-0 text-xl font-semibold text-slate-950">Module backend đã sẵn sàng</h2>
          </div>
        </div>
        <div class="data-table">
          <nz-table [nzData]="backendPlan" [nzFrontPagination]="false" [nzScroll]="{ x: '900px' }">
            <thead>
              <tr>
                <th>Module</th>
                <th>Trạng thái backend</th>
                <th>Cần làm tiếp</th>
                <th>Màn hình</th>
              </tr>
            </thead>
            <tbody>
              @for (item of backendPlan; track item.module) {
                <tr>
                  <td class="font-semibold">{{ item.module }}</td>
                  <td><nz-tag [nzColor]="item.ready ? 'green' : 'orange'">{{ item.status }}</nz-tag></td>
                  <td>{{ item.todo }}</td>
                  <td><a [routerLink]="item.route">Mở màn hình</a></td>
                </tr>
              }
            </tbody>
          </nz-table>
        </div>
      </div>
    </section>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardComponent implements OnInit {
  private readonly api = inject(ProductsApi);

  readonly products = signal<Product[]>([]);
  readonly imports = signal<PurchaseImport[]>([]);
  readonly stockItems = signal<StockBySize[]>([]);

  readonly productTotal = signal(0);
  readonly activeProducts = computed(() => this.products().filter((product) => product.status === 'active').length);
  readonly importAmount = computed(() => this.imports().reduce((sum, item) => sum + Number(item.total_amount || 0), 0));
  readonly importQuantity = computed(() => this.imports().reduce((sum, item) => sum + Number(item.total_quantity || 0), 0));
  readonly availableStock = computed(() => this.stockItems().reduce((sum, item) => sum + Number(item.quantity_available || 0), 0));
  readonly lowStock = computed(() => this.stockItems().filter((item) => Number(item.quantity_available || 0) <= 5));

  readonly chartBars = [
    { day: 'Thứ 2', revenue: 60, profit: 30 },
    { day: 'Thứ 3', revenue: 70, profit: 35 },
    { day: 'Thứ 4', revenue: 55, profit: 25 },
    { day: 'Thứ 5', revenue: 85, profit: 40 },
    { day: 'Thứ 6', revenue: 45, profit: 20 },
    { day: 'Thứ 7', revenue: 90, profit: 45 },
    { day: 'CN', revenue: 100, profit: 50 },
  ];

  readonly backendPlan = [
    { module: 'Sản phẩm TikTok', ready: true, status: 'Đã có API', todo: 'Nối UI CRUD và form liên kết SKU TikTok với SKU kho.', route: '/tiktok-products' },
    { module: 'Đơn hàng TikTok', ready: true, status: 'Đã có API', todo: 'Nối UI tạo đơn, đổi trạng thái, kiểm tra trừ/cộng kho.', route: '/tiktok-orders' },
    { module: 'Đối soát tiền', ready: true, status: 'Đã có API', todo: 'Nối UI tạo kỳ đối soát và bảng chênh lệch.', route: '/settlements' },
    { module: 'Chi phí vận hành', ready: true, status: 'Đã có API', todo: 'Nối UI CRUD chi phí và bộ lọc theo ngày/loại.', route: '/operating-costs' },
    { module: 'Báo cáo', ready: true, status: 'Đã có API', todo: 'Nối dashboard/report vào overview, by-product, by-sku, stock.', route: '/reports' },
  ];

  ngOnInit(): void {
    this.api.products({ page: 1, pageSize: 20 }).subscribe((response) => {
      this.products.set(response.data.items);
      this.productTotal.set(response.data.pager.total);
      this.loadStock(response.data.items.slice(0, 5));
    });

    this.api.purchaseImports({ page: 1, pageSize: 20 }).subscribe((response) => {
      this.imports.set(response.data.items);
    });
  }

  private loadStock(products: Product[]): void {
    if (!products.length) {
      return;
    }

    forkJoin(products.map((product) => this.api.stock(product.id).pipe(catchError(() => of({ data: { items: [] } }))))).subscribe((responses) => {
      this.stockItems.set(responses.flatMap((response) => response.data.items));
    });
  }
}
