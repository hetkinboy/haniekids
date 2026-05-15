import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzTableModule } from 'ng-zorro-antd/table';
import { NzTagModule } from 'ng-zorro-antd/tag';

import { Order, Product, PurchaseImport, ReportOverview, ReportProductRow, ReportSkuRow, ReportStockRow } from '../../core/api/api.models';
import { ProductsApi } from '../../core/products/products.api';
import { resolveDateRange } from '../../core/utils/date-range';

@Component({
  selector: 'app-dashboard',
  imports: [DecimalPipe, RouterLink, NzButtonModule, NzIconModule, NzTableModule, NzTagModule],
  templateUrl: './dashboard.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardComponent implements OnInit {
  private readonly api = inject(ProductsApi);

  readonly loading = signal(false);
  readonly products = signal<Product[]>([]);
  readonly orders = signal<Order[]>([]);
  readonly imports = signal<PurchaseImport[]>([]);
  readonly overview = signal<ReportOverview | null>(null);
  readonly productRows = signal<ReportProductRow[]>([]);
  readonly skuRows = signal<ReportSkuRow[]>([]);
  readonly stockRows = signal<ReportStockRow[]>([]);

  readonly productTotal = signal(0);
  readonly activeProducts = computed(() => this.products().filter((product) => product.status === 'active').length);
  readonly revenueOrders = computed(() => this.orders().filter((order) => !['cancelled', 'returned'].includes(order.status)));
  readonly cancelledOrders = computed(() => this.orders().filter((order) => ['cancelled', 'returned'].includes(order.status)).length);
  readonly importAmount = computed(() => this.imports().reduce((sum, item) => sum + Number(item.total_amount || 0), 0));
  readonly importQuantity = computed(() => this.imports().reduce((sum, item) => sum + Number(item.total_quantity || 0), 0));
  readonly lowStock = computed(() => this.stockRows().filter((item) => Number(item.quantity_available || 0) <= 5));
  readonly topProducts = computed(() => this.productRows().slice(0, 6));
  readonly topSkus = computed(() => this.skuRows().slice(0, 6));

  readonly dateRange = resolveDateRange('today') ?? { date_from: '', date_to: '' };

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);

    forkJoin({
      products: this.api.products({ page: 1, pageSize: 100 }).pipe(catchError(() => of(null))),
      orders: this.api.orders({ platform: 'tiktok', ...this.dateRange, page: 1, pageSize: 8 }).pipe(catchError(() => of(null))),
      imports: this.api.purchaseImports({ ...this.dateRange, page: 1, pageSize: 8 }).pipe(catchError(() => of(null))),
      overview: this.api.reportOverview(this.dateRange).pipe(catchError(() => of(null))),
      byProduct: this.api.reportByProduct(this.dateRange).pipe(catchError(() => of(null))),
      bySku: this.api.reportBySku(this.dateRange).pipe(catchError(() => of(null))),
      stock: this.api.reportStock().pipe(catchError(() => of(null))),
    }).subscribe((response) => {
      this.products.set(response.products?.data.items ?? []);
      this.productTotal.set(response.products?.data.pager.total ?? 0);
      this.orders.set(response.orders?.data.items ?? []);
      this.imports.set(response.imports?.data.items ?? []);
      this.overview.set(response.overview?.data ?? null);
      this.productRows.set(response.byProduct?.data.items ?? []);
      this.skuRows.set(response.bySku?.data.items ?? []);
      this.stockRows.set(response.stock?.data.items ?? []);
      this.loading.set(false);
    });
  }

  isNegative(value: number | null | undefined): boolean {
    return Number(value || 0) < 0;
  }
}
