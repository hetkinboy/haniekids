import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzFormModule } from 'ng-zorro-antd/form';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzInputModule } from 'ng-zorro-antd/input';
import { NzInputNumberModule } from 'ng-zorro-antd/input-number';
import { NzMessageService } from 'ng-zorro-antd/message';
import { NzModalModule } from 'ng-zorro-antd/modal';
import { NzPopconfirmModule } from 'ng-zorro-antd/popconfirm';
import { NzSelectModule } from 'ng-zorro-antd/select';
import { NzTableModule } from 'ng-zorro-antd/table';
import { NzTagModule } from 'ng-zorro-antd/tag';

import { OperatingCost, OperatingFeeSetting } from '../../core/api/api.models';
import { DisableNumberWheelDirective } from '../../core/directives/disable-number-wheel.directive';
import { ProductsApi } from '../../core/products/products.api';
import { DATE_RANGE_OPTIONS, DateRangePreset, resolveDateRange } from '../../core/utils/date-range';

const COST_TYPES = ['ads', 'packing', 'staff', 'rent', 'tools', 'other'];
const PERIOD_TYPES = ['day', 'month'];

@Component({
  selector: 'app-operating-costs',
  imports: [
    DecimalPipe,
    DisableNumberWheelDirective,
    FormsModule,
    ReactiveFormsModule,
    NzButtonModule,
    NzFormModule,
    NzIconModule,
    NzInputModule,
    NzInputNumberModule,
    NzModalModule,
    NzPopconfirmModule,
    NzSelectModule,
    NzTableModule,
    NzTagModule,
  ],
  templateUrl: './operating-costs.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class OperatingCostsComponent implements OnInit {
  private readonly api = inject(ProductsApi);
  private readonly fb = inject(FormBuilder);
  private readonly message = inject(NzMessageService);

  readonly costTypes = COST_TYPES;
  readonly periodTypes = PERIOD_TYPES;
  readonly dateRangeOptions = DATE_RANGE_OPTIONS;
  readonly items = signal<OperatingCost[]>([]);
  readonly feeSettings = signal<OperatingFeeSetting[]>([]);
  readonly loading = signal(false);
  readonly settingsLoading = signal(false);
  readonly saving = signal(false);
  readonly savingSettings = signal(false);
  readonly modalVisible = signal(false);
  readonly editing = signal<OperatingCost | null>(null);
  readonly total = signal(0);
  readonly summary = signal({ total_amount: 0 });
  readonly page = signal(1);
  readonly pageSize = signal(20);
  readonly filteredTotal = computed(() => this.summary().total_amount);
  readonly activeFeeSettings = computed(() => this.feeSettings().filter((item) => item.status === 'active').length);

  readonly filters = this.fb.nonNullable.group({
    date_preset: ['today' as DateRangePreset],
    date_from: [''],
    date_to: [''],
    cost_type: [''],
  });

  readonly form = this.fb.nonNullable.group({
    cost_date: ['', Validators.required],
    cost_type: ['ads', Validators.required],
    amount: [0, Validators.min(0)],
    allocation_type: ['day'],
    note: [''],
  });

  ngOnInit(): void {
    this.applyDatePreset('today');
    this.loadFeeSettings();
    this.load();
  }

  load(): void {
    this.loading.set(true);
    const { date_preset: _datePreset, ...filters } = this.filters.getRawValue();
    this.api.operatingCosts({ ...filters, page: this.page(), pageSize: this.pageSize() }).subscribe({
      next: (response) => {
        this.items.set(response.data.items);
        this.total.set(response.data.pager.total);
        this.summary.set({ total_amount: response.data.summary?.total_amount ?? 0 });
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.message.error('Không tải được chi phí vận hành');
      },
    });
  }

  onDatePresetChange(preset: DateRangePreset): void {
    this.applyDatePreset(preset);
  }

  loadFeeSettings(): void {
    this.settingsLoading.set(true);
    this.api.operatingFeeSettings().subscribe({
      next: (response) => {
        this.feeSettings.set(response.data.items);
        this.settingsLoading.set(false);
      },
      error: () => {
        this.settingsLoading.set(false);
        this.message.error('Không tải được cấu hình phí');
      },
    });
  }

  saveFeeSettings(): void {
    this.savingSettings.set(true);
    this.api.saveOperatingFeeSettings(this.feeSettings()).subscribe({
      next: (response) => {
        this.feeSettings.set(response.data.items);
        this.savingSettings.set(false);
        this.message.success('Đã lưu cấu hình phí');
      },
      error: () => {
        this.savingSettings.set(false);
        this.message.error('Không lưu được cấu hình phí');
      },
    });
  }

  openCreate(): void {
    this.editing.set(null);
    this.form.reset({ cost_date: new Date().toISOString().slice(0, 10), cost_type: 'ads', amount: 0, allocation_type: 'day', note: '' });
    this.modalVisible.set(true);
  }

  openEdit(item: OperatingCost): void {
    this.editing.set(item);
    this.form.reset({
      cost_date: item.cost_date,
      cost_type: item.cost_type,
      amount: item.amount,
      allocation_type: item.allocation_type || 'day',
      note: item.note ?? '',
    });
    this.modalVisible.set(true);
  }

  save(): void {
    if (this.form.invalid) {
      Object.values(this.form.controls).forEach((control) => control.markAsDirty());
      return;
    }

    const form = this.form.getRawValue();
    const payload: Partial<OperatingCost> = {
      cost_date: form.cost_date,
      cost_type: form.cost_type,
      amount: form.amount,
      allocation_type: form.allocation_type,
      note: form.note || null,
    };
    const editing = this.editing();
    const request = editing ? this.api.updateOperatingCost(editing.id, payload) : this.api.createOperatingCost(payload);

    this.saving.set(true);
    request.subscribe({
      next: () => {
        this.saving.set(false);
        this.modalVisible.set(false);
        this.message.success('Đã lưu chi phí');
        this.load();
      },
      error: () => {
        this.saving.set(false);
        this.message.error('Không lưu được chi phí');
      },
    });
  }

  delete(id: number): void {
    this.api.deleteOperatingCost(id).subscribe({
      next: () => {
        this.message.success('Đã xóa chi phí');
        this.load();
      },
      error: () => this.message.error('Không xóa được chi phí'),
    });
  }

  typeLabel(type: string): string {
    const labels: Record<string, string> = {
      ads: 'Ads',
      packing: 'Đóng gói',
      staff: 'Nhân sự',
      rent: 'Mặt bằng',
      tools: 'Công cụ',
      other: 'Khác',
    };

    return labels[type] ?? type;
  }

  periodLabel(type: string): string {
    const labels: Record<string, string> = {
      day: 'Theo ngày',
      month: 'Theo tháng',
    };

    return labels[type] ?? type;
  }

  private applyDatePreset(preset: DateRangePreset): void {
    const range = resolveDateRange(preset);
    if (range) {
      this.filters.patchValue(range, { emitEvent: false });
    }
  }
}
