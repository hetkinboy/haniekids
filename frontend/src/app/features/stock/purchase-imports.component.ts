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

import { Product, PurchaseImport, VariantOption } from '../../core/api/api.models';
import { AuthService } from '../../core/auth/auth.service';
import { ProductsApi } from '../../core/products/products.api';

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
  ],
  template: `
    <section class="app-shell space-y-4">
      <div class="page-header">
        <div>
          <h1 class="m-0 text-2xl font-semibold text-slate-950">Nhập hàng</h1>
          <p class="m-0 mt-1 text-sm text-slate-500">Tạo phiếu nhập để tăng tồn theo size và ghi lịch sử kho.</p>
        </div>
        @if (auth.isAdmin()) {
          <button nz-button nzType="primary" (click)="openCreate()">
            <span nz-icon nzType="plus"></span>
            Tạo phiếu nhập
          </button>
        }
      </div>

      <div class="metric-grid">
        <div class="metric-card">
          <div class="metric-label">Phiếu nhập</div>
          <div class="metric-value">{{ imports().length }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Tổng số lượng</div>
          <div class="metric-value text-blue-700">{{ importedQuantity() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Tổng giá trị</div>
          <div class="metric-value text-emerald-700">{{ importedAmount() | number: '1.0-0' }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Ghi kho</div>
          <div class="mt-2 text-sm font-medium text-slate-700">stock_by_size</div>
        </div>
      </div>

      <div class="data-table surface">
        <nz-table [nzData]="imports()" [nzLoading]="loading()" [nzScroll]="{ x: '820px' }" [nzFrontPagination]="false">
          <thead>
            <tr>
              <th>Mã phiếu</th>
              <th>Ngày</th>
              <th>Nhà cung cấp</th>
              <th>Tổng SL</th>
              <th>Tổng tiền</th>
              <th>Ghi chú</th>
            </tr>
          </thead>
          <tbody>
            @for (item of imports(); track item.id) {
              <tr>
                <td class="font-semibold text-slate-950">{{ item.import_code }}</td>
                <td>{{ item.import_date }}</td>
                <td>{{ item.supplier_name || '-' }}</td>
                <td class="font-medium">{{ item.total_quantity }}</td>
                <td>{{ item.total_amount | number: '1.0-0' }}</td>
                <td>{{ item.note || '-' }}</td>
              </tr>
            }
          </tbody>
        </nz-table>
      </div>
    </section>

    <nz-modal [nzVisible]="modalVisible()" nzTitle="Tạo phiếu nhập" nzOkText="Lưu phiếu" nzCancelText="Hủy" (nzOnCancel)="modalVisible.set(false)" (nzOnOk)="save()" [nzOkLoading]="saving()" nzWidth="920px">
      <ng-container *nzModalContent>
        <form nz-form nzLayout="vertical" [formGroup]="form">
          <div class="grid gap-3 md:grid-cols-3">
            <nz-form-item>
              <nz-form-label>Mã phiếu</nz-form-label>
              <input nz-input formControlName="import_code" placeholder="Tự sinh nếu để trống" />
            </nz-form-item>
            <nz-form-item>
              <nz-form-label nzRequired>Ngày nhập</nz-form-label>
              <input nz-input type="date" formControlName="import_date" />
            </nz-form-item>
            <nz-form-item>
              <nz-form-label>Nhà cung cấp</nz-form-label>
              <input nz-input formControlName="supplier_name" placeholder="Tên nhà cung cấp" />
            </nz-form-item>
          </div>
          <nz-form-item>
            <nz-form-label>Ghi chú</nz-form-label>
            <input nz-input formControlName="note" placeholder="Ghi chú cho phiếu nhập" />
          </nz-form-item>
        </form>

        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
          <div class="mb-2 text-sm font-semibold text-slate-800">Thêm dòng hàng</div>
          <div class="grid gap-3 md:grid-cols-[1fr_1fr_120px_140px_auto]">
          <nz-select [(ngModel)]="draft.product_id" nzPlaceHolder="Sản phẩm" (ngModelChange)="loadSizes($event)">
            @for (product of products(); track product.id) {
              <nz-option [nzValue]="product.id" [nzLabel]="product.product_code + ' - ' + product.name" />
            }
          </nz-select>
          <nz-select [(ngModel)]="draft.size_option_id" nzPlaceHolder="Size">
            @for (size of sizes(); track size.id) {
              <nz-option [nzValue]="size.id" [nzLabel]="'Size ' + size.name" />
            }
          </nz-select>
          <nz-input-number class="!w-full" [(ngModel)]="draft.quantity" [nzMin]="1" />
          <nz-input-number class="!w-full" [(ngModel)]="draft.unit_cost" [nzMin]="0" />
          <button nz-button nzType="default" (click)="addLine()">Thêm dòng</button>
          </div>
        </div>

        <div class="data-table">
          <nz-table nzSize="small" [nzData]="lines()" [nzFrontPagination]="false" [nzScroll]="{ x: '680px' }">
            <thead>
              <tr>
                <th>Sản phẩm</th>
                <th>Size</th>
                <th>SL</th>
                <th>Cost</th>
                <th>Thành tiền</th>
              </tr>
            </thead>
            <tbody>
              @for (line of lines(); track $index) {
                <tr>
                  <td>{{ productLabel(line.product_id) }}</td>
                  <td>{{ sizeLabel(line.size_option_id) }}</td>
                  <td>{{ line.quantity }}</td>
                  <td>{{ line.unit_cost | number: '1.0-0' }}</td>
                  <td>{{ line.quantity * line.unit_cost | number: '1.0-0' }}</td>
                </tr>
              }
            </tbody>
          </nz-table>
        </div>
        <div class="mt-3 text-right font-semibold">Tổng: {{ totalAmount() | number: '1.0-0' }}</div>
      </ng-container>
    </nz-modal>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PurchaseImportsComponent implements OnInit {
  readonly auth = inject(AuthService);
  private readonly api = inject(ProductsApi);
  private readonly fb = inject(FormBuilder);
  private readonly message = inject(NzMessageService);

  readonly imports = signal<PurchaseImport[]>([]);
  readonly products = signal<Product[]>([]);
  readonly sizes = signal<VariantOption[]>([]);
  readonly lines = signal<ImportLine[]>([]);
  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly modalVisible = signal(false);
  readonly totalAmount = computed(() => this.lines().reduce((sum, line) => sum + line.quantity * line.unit_cost, 0));
  readonly importedQuantity = computed(() => this.imports().reduce((sum, item) => sum + Number(item.total_quantity || 0), 0));
  readonly importedAmount = computed(() => this.imports().reduce((sum, item) => sum + Number(item.total_amount || 0), 0));

  readonly draft: ImportLine = { product_id: 0, size_option_id: 0, quantity: 1, unit_cost: 0 };
  readonly form = this.fb.nonNullable.group({
    import_code: [''],
    import_date: [new Date().toISOString().slice(0, 10), Validators.required],
    supplier_name: [''],
    note: [''],
  });

  ngOnInit(): void {
    this.loadProducts();
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.api.purchaseImports({ page: 1, pageSize: 20 }).subscribe({
      next: (response) => {
        this.imports.set(response.data.items);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.message.error('Không tải được phiếu nhập');
      },
    });
  }

  loadProducts(): void {
    this.api.products({ page: 1, pageSize: 100, status: 'active' }).subscribe({
      next: (response) => this.products.set(response.data.items),
      error: () => this.message.error('Không tải được sản phẩm'),
    });
  }

  loadSizes(productId: number): void {
    this.api.variantGroups(productId).subscribe({
      next: (response) => {
        const sizeGroup = response.data.items.find((group) => group.is_stock_dimension);
        this.sizes.set(sizeGroup?.options ?? []);
        this.draft.size_option_id = this.sizes()[0]?.id ?? 0;
      },
      error: () => this.message.error('Không tải được size'),
    });
  }

  openCreate(): void {
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
      this.message.warning('Chọn sản phẩm, size và số lượng');
      return;
    }

    this.lines.update((items) => [...items, { ...this.draft }]);
  }

  save(): void {
    if (this.form.invalid || this.lines().length === 0) {
      this.message.warning('Nhập thông tin phiếu và ít nhất 1 dòng hàng');
      return;
    }

    this.saving.set(true);
    this.api.createPurchaseImport({ ...this.form.getRawValue(), items: this.lines() }).subscribe({
      next: () => {
        this.message.success('Đã tạo phiếu nhập');
        this.saving.set(false);
        this.modalVisible.set(false);
        this.load();
      },
      error: () => {
        this.saving.set(false);
        this.message.error('Không tạo được phiếu nhập');
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
}
