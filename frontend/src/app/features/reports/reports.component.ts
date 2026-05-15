import { DecimalPipe } from '@angular/common';
import {
  ChangeDetectionStrategy,
  Component,
  OnInit,
  computed,
  inject,
  signal,
} from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzFormModule } from 'ng-zorro-antd/form';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzInputModule } from 'ng-zorro-antd/input';
import { NzMessageService } from 'ng-zorro-antd/message';
import { NzSelectModule } from 'ng-zorro-antd/select';
import { NzTableModule } from 'ng-zorro-antd/table';
import { NzTagModule } from 'ng-zorro-antd/tag';
import { NzDatePickerModule } from 'ng-zorro-antd/date-picker';

import {
  ReportOverview,
  ReportProductRow,
  ReportSkuRow,
  ReportStockRow,
} from '../../core/api/api.models';
import { ProductsApi } from '../../core/products/products.api';
import { DATE_RANGE_OPTIONS, DateRangePreset, resolveDateRange } from '../../core/utils/date-range';

@Component({
  selector: 'app-reports',
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
    NzDatePickerModule,
  ],
  templateUrl: './reports.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ReportsComponent implements OnInit {
  private readonly api = inject(ProductsApi);
  private readonly fb = inject(FormBuilder);
  private readonly message = inject(NzMessageService);
  private syncingPreset = false;

  readonly loading = signal(false);
  readonly dateRangeOptions = DATE_RANGE_OPTIONS;
  readonly overview = signal<ReportOverview | null>(null);
  readonly products = signal<ReportProductRow[]>([]);
  readonly skus = signal<ReportSkuRow[]>([]);
  readonly stock = signal<ReportStockRow[]>([]);

  readonly filters = this.fb.group({
    date_preset: this.fb.nonNullable.control<DateRangePreset>('today'),
    date_from: this.fb.nonNullable.control(''),
    date_to: this.fb.nonNullable.control(''),
    date_range: this.fb.control<Date[]>([]),
  });

  readonly profitMargin = computed(() => {
    const data = this.overview();
    if (!data || data.orders.net_revenue <= 0) {
      return 0;
    }

    return (data.orders.gross_profit / data.orders.net_revenue) * 100;
  });

  readonly lowStock = computed(() => this.stock().filter((item) => item.quantity_available <= 5));

  ngOnInit(): void {
    this.applyDatePreset('today');
    this.load();
  }

  load(): void {
    this.loading.set(true);
    const { date_preset: _datePreset, date_range: _dateRange, ...params } = this.filters.getRawValue();

    let remaining = 4;
    const finish = (): void => {
      remaining--;
      if (remaining === 0) {
        this.loading.set(false);
      }
    };

    this.api.reportOverview(params).subscribe({
      next: (response) => this.overview.set(response.data),
      error: () => this.message.error('Không tải được báo cáo tổng quan'),
      complete: finish,
    });

    this.api.reportByProduct(params).subscribe({
      next: (response) => this.products.set(response.data.items),
      error: () => this.message.error('Không tải được báo cáo theo sản phẩm'),
      complete: finish,
    });

    this.api.reportBySku(params).subscribe({
      next: (response) => this.skus.set(response.data.items),
      error: () => this.message.error('Không tải được báo cáo theo SKU'),
      complete: finish,
    });

    this.api.reportStock().subscribe({
      next: (response) => this.stock.set(response.data.items),
      error: () => this.message.error('Không tải được báo cáo tồn kho'),
      complete: finish,
    });
  }

  onDatePresetChange(preset: DateRangePreset): void {
    this.applyDatePreset(preset);
  }

  onDateRangeChange(value: Date[] | null): void {
    if (this.syncingPreset) {
      return;
    }

    if (!value || value.length < 2 || !value[0] || !value[1]) {
      return;
    }

    this.filters.patchValue({
      date_preset: 'custom',
      date_from: this.formatDate(value[0]),
      date_to: this.formatDate(value[1]),
    }, { emitEvent: false });
  }

  private applyDatePreset(preset: DateRangePreset): void {
    const range = resolveDateRange(preset);
    if (range) {
      this.syncingPreset = true;
      this.filters.patchValue({
        ...range,
        date_range: [this.parseDate(range.date_from), this.parseDate(range.date_to)],
      }, { emitEvent: false });
      queueMicrotask(() => {
        this.syncingPreset = false;
      });
    }
  }

  private parseDate(value: string): Date {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
  }

  private formatDate(value: Date): string {
    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${value.getFullYear()}-${month}-${day}`;
  }
}
