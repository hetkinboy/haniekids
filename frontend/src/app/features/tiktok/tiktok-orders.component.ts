import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzFormModule } from 'ng-zorro-antd/form';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzInputModule } from 'ng-zorro-antd/input';
import { NzMessageService } from 'ng-zorro-antd/message';
import { NzSelectModule } from 'ng-zorro-antd/select';
import { NzTableModule } from 'ng-zorro-antd/table';
import { NzTagModule } from 'ng-zorro-antd/tag';

import { Order } from '../../core/api/api.models';
import { AuthService } from '../../core/auth/auth.service';
import { ProductsApi } from '../../core/products/products.api';
import { DATE_RANGE_OPTIONS, DateRangePreset, resolveDateRange } from '../../core/utils/date-range';

@Component({
  selector: 'app-tiktok-orders',
  imports: [
    DecimalPipe,
    ReactiveFormsModule,
    NzButtonModule,
    NzFormModule,
    NzIconModule,
    NzInputModule,
    NzSelectModule,
    NzTableModule,
    NzTagModule,
  ],
  templateUrl: './tiktok-orders.component.html',
  styles: [
    `
      .money-lines {
        display: grid;
        gap: 6px;
      }

      .money-lines > div {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-width: 0;
        font-size: 13px;
        color: #475569;
      }

      .money-lines span {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .money-lines strong {
        flex: 0 0 auto;
        font-weight: 600;
        color: #0f172a;
      }

      .money-lines .settlement {
        margin-top: 4px;
        padding-top: 8px;
        border-top: 1px solid #e2e8f0;
      }

      .money-lines .settlement strong {
        color: #006a65;
      }

      .money-lines .profit strong {
        color: #047857;
      }

      .money-lines .profit.negative strong {
        color: #b91c1c;
      }
    `,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TiktokOrdersComponent implements OnInit {
  readonly auth = inject(AuthService);
  private readonly api = inject(ProductsApi);
  private readonly fb = inject(FormBuilder);
  private readonly message = inject(NzMessageService);

  readonly orders = signal<Order[]>([]);
  readonly dateRangeOptions = DATE_RANGE_OPTIONS;
  readonly selectedOrder = signal<Order | null>(null);
  readonly loading = signal(false);
  readonly importing = signal(false);
  readonly savingStatus = signal(false);
  readonly total = signal(0);
  readonly summary = signal({ net_revenue: 0, total_profit: 0, unmatched_orders: 0 });
  readonly page = signal(1);
  readonly pageSize = signal(20);
  readonly filteredRevenue = computed(() => this.summary().net_revenue);
  readonly filteredProfit = computed(() => this.summary().total_profit);
  readonly unmatchedOrders = computed(() => this.summary().unmatched_orders);

  readonly filters = this.fb.nonNullable.group({
    keyword: [''],
    status: [''],
    date_preset: ['today' as DateRangePreset],
    date_from: [''],
    date_to: [''],
  });

  readonly importForm = this.fb.nonNullable.group({
    order_id: ['', Validators.required],
  });

  readonly statusForm = this.fb.nonNullable.group({
    status: ['pending'],
  });

  ngOnInit(): void {
    this.applyDatePreset('today');
    this.load();
  }

  onDatePresetChange(preset: DateRangePreset): void {
    this.applyDatePreset(preset);
  }

  load(): void {
    this.loading.set(true);
    const { date_preset: _datePreset, ...filters } = this.filters.getRawValue();
    this.api.orders({ ...filters, platform: 'tiktok', page: this.page(), pageSize: this.pageSize() }).subscribe({
      next: (response) => {
        this.orders.set(response.data.items);
        this.total.set(response.data.pager.total);
        this.summary.set({
          net_revenue: response.data.summary?.net_revenue ?? 0,
          total_profit: response.data.summary?.total_profit ?? 0,
          unmatched_orders: response.data.summary?.unmatched_orders ?? 0,
        });
        this.loading.set(false);
        const selected = this.selectedOrder();
        if (selected) {
          const fresh = response.data.items.find((order) => order.id === selected.id) ?? null;
          if (fresh) {
            this.selectOrder(fresh);
          }
        }
      },
      error: () => {
        this.loading.set(false);
        this.message.error('Không tải được đơn TikTok');
      },
    });
  }

  private applyDatePreset(preset: DateRangePreset): void {
    const range = resolveDateRange(preset);
    if (range) {
      this.filters.patchValue(range, { emitEvent: false });
    }
  }

  selectOrder(order: Order): void {
    this.api.order(order.id).subscribe({
      next: (response) => {
        this.selectedOrder.set(response.data);
        this.statusForm.reset({ status: response.data.status });
      },
      error: () => this.message.error('Không tải được chi tiết đơn'),
    });
  }

  importOrder(): void {
    if (this.importForm.invalid) {
      Object.values(this.importForm.controls).forEach((control) => control.markAsDirty());
      return;
    }

    const orderId = this.importForm.controls.order_id.value.trim();
    this.importing.set(true);
    this.api.importTiktokOrder(orderId).subscribe({
      next: (response) => {
        this.importing.set(false);
        const imported = response.data.orders[0];
        this.message.success('Đã kéo chi tiết đơn TikTok');
        this.load();
        if (imported) {
          this.selectOrder(imported);
        }
      },
      error: () => {
        this.importing.set(false);
        this.message.error('Không kéo được chi tiết đơn TikTok');
      },
    });
  }

  updateStatus(): void {
    const order = this.selectedOrder();
    if (!order) {
      return;
    }

    this.savingStatus.set(true);
    this.api.updateOrderStatus(order.id, this.statusForm.controls.status.value).subscribe({
      next: (response) => {
        this.savingStatus.set(false);
        this.selectedOrder.set(response.data);
        this.message.success('Đã cập nhật trạng thái đơn');
        this.load();
      },
      error: () => {
        this.savingStatus.set(false);
        this.message.error('Không cập nhật được trạng thái đơn');
      },
    });
  }

  statusColor(status: string): string {
    if (['completed'].includes(status)) {
      return 'green';
    }
    if (['cancelled', 'returned'].includes(status)) {
      return 'red';
    }
    if (['shipped', 'confirmed'].includes(status)) {
      return 'blue';
    }
    return 'default';
  }

  isRevenueOrder(order: Order | null | undefined): boolean {
    return !!order && !['cancelled', 'returned'].includes(order.status);
  }

  revenueValue(order: Order | null | undefined): number {
    return this.isRevenueOrder(order) ? Number(order?.net_revenue || 0) : 0;
  }

  profitValue(order: Order | null | undefined): number {
    return this.isRevenueOrder(order) ? Number(order?.total_profit || 0) : 0;
  }
}
