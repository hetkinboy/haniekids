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
import { NzSelectModule } from 'ng-zorro-antd/select';
import { NzTableModule } from 'ng-zorro-antd/table';
import { NzTagModule } from 'ng-zorro-antd/tag';

import { Product, PurchaseImport, VariantOption } from '../../core/api/api.models';
import { AuthService } from '../../core/auth/auth.service';
import { ProductsApi } from '../../core/products/products.api';
import { DATE_RANGE_OPTIONS, DateRangePreset, resolveDateRange } from '../../core/utils/date-range';

interface ImportLine {
  product_id: number;
  size_option_id: number;
  quantity: number;
  unit_cost: number;
}

@Component({
  selector: 'app-purchase-imports',
  imports: [
    DecimalPipe,
    FormsModule,
    ReactiveFormsModule,
    NzButtonModule,
    NzFormModule,
    NzIconModule,
    NzInputModule,
    NzInputNumberModule,
    NzModalModule,
    NzSelectModule,
    NzTableModule,
    NzTagModule,
  ],
  templateUrl: './purchase-imports.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PurchaseImportsComponent implements OnInit {
  readonly auth = inject(AuthService);
  private readonly api = inject(ProductsApi);
  private readonly fb = inject(FormBuilder);
  private readonly message = inject(NzMessageService);

  readonly imports = signal<PurchaseImport[]>([]);
  readonly dateRangeOptions = DATE_RANGE_OPTIONS;
  readonly products = signal<Product[]>([]);
  readonly sizes = signal<VariantOption[]>([]);
  readonly lines = signal<ImportLine[]>([]);
  readonly selectedImport = signal<PurchaseImport | null>(null);
  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly modalVisible = signal(false);
  readonly detailVisible = signal(false);
  readonly editingId = signal<number | null>(null);
  readonly total = signal(0);
  readonly page = signal(1);
  readonly pageSize = signal(20);
  readonly summary = signal({ total_quantity: 0, total_amount: 0 });
  readonly totalAmount = computed(() => this.lines().reduce((sum, line) => sum + line.quantity * line.unit_cost, 0));
  readonly importedQuantity = computed(() => this.summary().total_quantity);
  readonly importedAmount = computed(() => this.summary().total_amount);

  readonly draft: ImportLine = { product_id: 0, size_option_id: 0, quantity: 1, unit_cost: 0 };
  readonly filters = this.fb.nonNullable.group({
    keyword: [''],
    date_preset: ['today' as DateRangePreset],
    date_from: [''],
    date_to: [''],
  });

  readonly form = this.fb.nonNullable.group({
    import_code: [''],
    import_date: [new Date().toISOString().slice(0, 10), Validators.required],
    supplier_name: [''],
    note: [''],
  });

  ngOnInit(): void {
    this.applyDatePreset('today');
    this.loadProducts();
    this.load();
  }

  load(): void {
    this.loading.set(true);
    const { date_preset: _datePreset, ...filters } = this.filters.getRawValue();
    this.api.purchaseImports({ ...filters, page: this.page(), pageSize: this.pageSize() }).subscribe({
      next: (response) => {
        this.imports.set(response.data.items);
        this.total.set(response.data.pager.total);
        this.summary.set({
          total_quantity: response.data.summary?.total_quantity ?? 0,
          total_amount: response.data.summary?.total_amount ?? 0,
        });
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.message.error('KhÃ´ng táº£i Ä‘Æ°á»£c phiáº¿u nháº­p');
      },
    });
  }

  onDatePresetChange(preset: DateRangePreset): void {
    this.applyDatePreset(preset);
  }

  loadProducts(): void {
    this.api.products({ page: 1, pageSize: 100, status: 'active' }).subscribe({
      next: (response) => this.products.set(response.data.items),
      error: () => this.message.error('KhÃ´ng táº£i Ä‘Æ°á»£c sáº£n pháº©m'),
    });
  }

  loadSizes(productId: number): void {
    this.api.variantGroups(productId).subscribe({
      next: (response) => {
        const sizeGroup = response.data.items.find((group) => group.is_stock_dimension);
        this.sizes.set(sizeGroup?.options ?? []);
        this.draft.size_option_id = this.sizes()[0]?.id ?? 0;
      },
      error: () => this.message.error('KhÃ´ng táº£i Ä‘Æ°á»£c size'),
    });
  }

  openCreate(): void {
    this.editingId.set(null);
    this.lines.set([]);
    this.form.reset({ import_code: '', import_date: new Date().toISOString().slice(0, 10), supplier_name: '', note: '' });
    const first = this.products()[0];
    this.draft.product_id = first?.id ?? 0;
    this.draft.quantity = 1;
    this.draft.unit_cost = 0;
    if (first) {
      this.loadSizes(first.id);
    }
    this.modalVisible.set(true);
  }

  addLine(): void {
    if (!this.draft.product_id || !this.draft.size_option_id || this.draft.quantity < 1) {
      this.message.warning('Chá»n sáº£n pháº©m, size vÃ  sá»‘ lÆ°á»£ng');
      return;
    }

    this.lines.update((items) => [...items, { ...this.draft }]);
  }

  removeLine(index: number): void {
    this.lines.update((items) => items.filter((_, itemIndex) => itemIndex !== index));
  }

  openDetail(item: PurchaseImport): void {
    this.api.purchaseImport(item.id).subscribe({
      next: (response) => {
        this.selectedImport.set(response.data);
        this.detailVisible.set(true);
      },
      error: () => this.message.error('KhÃ´ng táº£i Ä‘Æ°á»£c chi tiáº¿t phiáº¿u nháº­p'),
    });
  }

  openEdit(item: PurchaseImport): void {
    this.api.purchaseImport(item.id).subscribe({
      next: (response) => {
        if (!response.data.can_edit) {
          this.message.warning('Phiáº¿u nháº­p chá»‰ Ä‘Æ°á»£c sá»­a trong 10 phÃºt sau khi táº¡o');
          return;
        }

        this.editingId.set(response.data.id);
        this.form.reset({
          import_code: response.data.import_code,
          import_date: response.data.import_date,
          supplier_name: response.data.supplier_name ?? '',
          note: response.data.note ?? '',
        });
        this.lines.set((response.data.items ?? []).map((line) => ({
          product_id: line.product_id,
          size_option_id: line.size_option_id,
          quantity: line.quantity,
          unit_cost: line.unit_cost,
        })));

        const firstProductId = this.lines()[0]?.product_id ?? this.products()[0]?.id ?? 0;
        this.draft.product_id = firstProductId;
        this.draft.quantity = 1;
        this.draft.unit_cost = 0;
        if (firstProductId) {
          this.loadSizes(firstProductId);
        }
        this.modalVisible.set(true);
      },
      error: () => this.message.error('KhÃ´ng táº£i Ä‘Æ°á»£c phiáº¿u nháº­p Ä‘á»ƒ sá»­a'),
    });
  }

  save(): void {
    if (this.form.invalid || this.lines().length === 0) {
      this.message.warning('Nháº­p thÃ´ng tin phiáº¿u vÃ  Ã­t nháº¥t 1 dÃ²ng hÃ ng');
      return;
    }

    this.saving.set(true);
    const editingId = this.editingId();
    const request = editingId
      ? this.api.updatePurchaseImport(editingId, { ...this.form.getRawValue(), items: this.lines() })
      : this.api.createPurchaseImport({ ...this.form.getRawValue(), items: this.lines() });

    request.subscribe({
      next: () => {
        this.message.success(editingId ? 'Đã cập nhật phiếu nhập' : 'Đã tạo phiếu nhập');
        this.saving.set(false);
        this.modalVisible.set(false);
        this.editingId.set(null);
        this.load();
      },
      error: () => {
        this.saving.set(false);
        this.message.error(editingId ? 'Không cập nhật được phiếu nhập' : 'Không tạo được phiếu nhập');
      },
    });
  }

  productLabel(id: number): string {
    const product = this.products().find((item) => item.id === id);
    return product ? `${product.product_code} - ${product.name}` : String(id);
  }

  sizeLabel(id: number): string {
    return this.sizes().find((item) => item.id === id)?.name ?? String(id);
  }

  private applyDatePreset(preset: DateRangePreset): void {
    const range = resolveDateRange(preset);
    if (range) {
      this.filters.patchValue(range, { emitEvent: false });
    }
  }
}
