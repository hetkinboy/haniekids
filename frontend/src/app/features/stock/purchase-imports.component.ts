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
import { DisableNumberWheelDirective } from '../../core/directives/disable-number-wheel.directive';
import { ProductsApi } from '../../core/products/products.api';
import { DATE_RANGE_OPTIONS, DateRangePreset, resolveDateRange } from '../../core/utils/date-range';

interface ImportLine {
  product_id: number;
  size_option_id: number;
  quantity: number;
  unit_cost: number;
}

interface ImportSizeDraft {
  size_option_id: number;
  size_name: string;
  quantity: number | null;
  unit_cost: number;
}

interface CostBreakdownLine {
  unit_cost: number;
  quantity: number;
  amount: number;
  sizes: string[];
}

@Component({
  selector: 'app-purchase-imports',
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
  readonly sizeDrafts = signal<ImportSizeDraft[]>([]);
  readonly sizeNames = signal<Record<number, string>>({});
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
  readonly currentProductQuantity = computed(() => this.sizeDrafts().reduce((sum, line) => sum + (Number(line.quantity) || 0), 0));
  readonly currentProductAmount = computed(() =>
    this.sizeDrafts().reduce((sum, line) => sum + (Number(line.quantity) || 0) * line.unit_cost, 0)
  );
  readonly currentProductCostBreakdown = computed(() => this.groupDraftsByCost(this.sizeDrafts()));
  readonly importCostBreakdown = computed(() => this.groupLinesByCost(this.lines()));
  readonly selectedImportCostBreakdown = computed(() => this.groupImportItemsByCost(this.selectedImport()?.items ?? []));
  readonly importedQuantity = computed(() => this.summary().total_quantity);
  readonly importedAmount = computed(() => this.summary().total_amount);

  readonly draft = { product_id: 0 };
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
    this.draft.product_id = productId;
    this.sizeDrafts.set([]);

    this.api.variantGroups(productId).subscribe({
      next: (response) => {
        const sizeGroup = response.data.items.find((group) => group.is_stock_dimension);
        const options = sizeGroup?.options ?? [];
        this.sizes.set(options);
        this.sizeNames.update((names) => ({
          ...names,
          ...Object.fromEntries(options.map((option) => [option.id, option.name])),
        }));
        this.sizeDrafts.set(options.map((option) => {
          const existingLine = this.lines().find((line) => line.product_id === productId && line.size_option_id === option.id);

          return {
            size_option_id: option.id,
            size_name: option.name,
            quantity: existingLine?.quantity ?? null,
            unit_cost: existingLine?.unit_cost ?? option.base_cost ?? 0,
          };
        }));
      },
      error: () => this.message.error('Không tải được size'),
    });
  }

  openCreate(): void {
    this.editingId.set(null);
    this.lines.set([]);
    this.form.reset({ import_code: '', import_date: new Date().toISOString().slice(0, 10), supplier_name: '', note: '' });
    const first = this.products()[0];
    this.draft.product_id = first?.id ?? 0;
    this.sizeDrafts.set([]);
    if (first) {
      this.loadSizes(first.id);
    }
    this.modalVisible.set(true);
  }

  addLine(): void {
    if (!this.draft.product_id || this.currentProductQuantity() < 1) {
      this.message.warning('Chọn sản phẩm và nhập số lượng cho ít nhất 1 size');
      return;
    }

    this.syncCurrentProductLines();
    this.message.success('Đã cập nhật size vào phiếu');
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
        if (firstProductId) {
          this.loadSizes(firstProductId);
        }
        this.modalVisible.set(true);
      },
      error: () => this.message.error('KhÃ´ng táº£i Ä‘Æ°á»£c phiáº¿u nháº­p Ä‘á»ƒ sá»­a'),
    });
  }

  save(): void {
    this.syncCurrentProductLines();

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
    return this.sizeNames()[id] ?? this.sizes().find((item) => item.id === id)?.name ?? String(id);
  }

  costBreakdownLabel(line: CostBreakdownLine): string {
    return `${line.quantity} x ${this.formatNumber(line.unit_cost)} = ${this.formatNumber(line.amount)}`;
  }

  updateSizeQuantity(sizeOptionId: number, quantity: number | null): void {
    this.sizeDrafts.update((items) => items.map((item) => (
      item.size_option_id === sizeOptionId ? { ...item, quantity: quantity === null ? null : Number(quantity) } : item
    )));
    this.syncCurrentProductLines();
  }

  updateSizeCost(sizeOptionId: number, unitCost: number | null): void {
    this.sizeDrafts.update((items) => items.map((item) => (
      item.size_option_id === sizeOptionId ? { ...item, unit_cost: Number(unitCost) || 0 } : item
    )));
    this.syncCurrentProductLines();
  }

  private syncCurrentProductLines(): void {
    const productId = this.draft.product_id;

    if (!productId) {
      return;
    }

    const currentLines = this.sizeDrafts()
      .filter((item) => (Number(item.quantity) || 0) > 0)
      .map((item) => ({
        product_id: productId,
        size_option_id: item.size_option_id,
        quantity: Number(item.quantity) || 0,
        unit_cost: item.unit_cost,
      }));

    this.lines.update((items) => [
      ...items.filter((item) => item.product_id !== productId),
      ...currentLines,
    ]);
  }

  private groupDraftsByCost(drafts: ImportSizeDraft[]): CostBreakdownLine[] {
    const groups = new Map<number, CostBreakdownLine>();

    drafts.forEach((draft) => {
      const quantity = Number(draft.quantity) || 0;

      if (quantity <= 0) {
        return;
      }

      const unitCost = Number(draft.unit_cost) || 0;
      const existing = groups.get(unitCost) ?? { unit_cost: unitCost, quantity: 0, amount: 0, sizes: [] };
      existing.quantity += quantity;
      existing.amount += quantity * unitCost;
      existing.sizes.push(draft.size_name);
      groups.set(unitCost, existing);
    });

    return Array.from(groups.values()).sort((a, b) => a.unit_cost - b.unit_cost);
  }

  private groupLinesByCost(lines: ImportLine[]): CostBreakdownLine[] {
    const groups = new Map<number, CostBreakdownLine>();

    lines.forEach((line) => {
      const quantity = Number(line.quantity) || 0;

      if (quantity <= 0) {
        return;
      }

      const unitCost = Number(line.unit_cost) || 0;
      const existing = groups.get(unitCost) ?? { unit_cost: unitCost, quantity: 0, amount: 0, sizes: [] };
      existing.quantity += quantity;
      existing.amount += quantity * unitCost;
      existing.sizes.push(this.sizeLabel(line.size_option_id));
      groups.set(unitCost, existing);
    });

    return Array.from(groups.values()).sort((a, b) => a.unit_cost - b.unit_cost);
  }

  private groupImportItemsByCost(items: PurchaseImport['items']): CostBreakdownLine[] {
    const groups = new Map<number, CostBreakdownLine>();

    (items ?? []).forEach((item) => {
      const quantity = Number(item.quantity) || 0;

      if (quantity <= 0) {
        return;
      }

      const unitCost = Number(item.unit_cost) || 0;
      const existing = groups.get(unitCost) ?? { unit_cost: unitCost, quantity: 0, amount: 0, sizes: [] };
      existing.quantity += quantity;
      existing.amount += quantity * unitCost;
      existing.sizes.push(item.size_name ?? String(item.size_option_id));
      groups.set(unitCost, existing);
    });

    return Array.from(groups.values()).sort((a, b) => a.unit_cost - b.unit_cost);
  }

  private formatNumber(value: number): string {
    return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(value);
  }

  private applyDatePreset(preset: DateRangePreset): void {
    const range = resolveDateRange(preset);
    if (range) {
      this.filters.patchValue(range, { emitEvent: false });
    }
  }
}
