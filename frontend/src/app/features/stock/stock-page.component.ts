import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
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

import { Product, StockBySize, StockMovement } from '../../core/api/api.models';
import { AuthService } from '../../core/auth/auth.service';
import { ProductsApi } from '../../core/products/products.api';

@Component({
  selector: 'app-stock-page',
  imports: [
    DecimalPipe,
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
  template: `
    <section class="app-shell space-y-4">
      <div class="page-header">
        <div>
          <h1 class="m-0 text-2xl font-semibold text-slate-950">Tồn kho theo size</h1>
          <p class="m-0 mt-1 text-sm text-slate-500">Theo dõi tồn thật trên từng size, không tạo tồn riêng cho combo.</p>
        </div>
        @if (auth.isAdmin() && selectedProductId()) {
          <button nz-button nzType="primary" (click)="openAdjust()">
            <span nz-icon nzType="edit"></span>
            Điều chỉnh tồn
          </button>
        }
      </div>

      <div class="metric-grid">
        <div class="metric-card">
          <div class="metric-label">Tổng tồn</div>
          <div class="metric-value">{{ totalOnHand() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Có thể bán</div>
          <div class="metric-value text-emerald-700">{{ totalAvailable() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Đang giữ</div>
          <div class="metric-value text-amber-600">{{ totalReserved() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Size sắp hết</div>
          <div class="metric-value text-red-600">{{ lowStockCount() }}</div>
        </div>
      </div>

      <div class="surface p-3">
        <form class="filter-bar" [formGroup]="filterForm" (ngSubmit)="loadStock()">
          <nz-select class="!w-full sm:!w-96" formControlName="product_id" nzPlaceHolder="Chọn sản phẩm" nzShowSearch>
            @for (product of products(); track product.id) {
              <nz-option [nzValue]="product.id" [nzLabel]="product.product_code + ' - ' + product.name" />
            }
          </nz-select>
          <button nz-button nzType="default">
            <span nz-icon nzType="reload"></span>
            Tải tồn
          </button>
        </form>
      </div>

      <div class="data-table surface">
        <nz-table [nzData]="stock()" [nzLoading]="loading()" [nzScroll]="{ x: '820px' }" [nzFrontPagination]="false">
          <thead>
            <tr>
              <th>Sản phẩm</th>
              <th>Size</th>
              <th>Tồn hiện tại</th>
              <th>Đã giữ</th>
              <th>Có thể bán</th>
              <th>Cost TB</th>
              <th>Giá trị tồn</th>
            </tr>
          </thead>
          <tbody>
            @for (item of stock(); track item.id) {
              <tr>
                <td>{{ item.product_name }}</td>
                <td><nz-tag [nzColor]="item.quantity_available <= 0 ? 'red' : 'blue'">{{ item.size_name }}</nz-tag></td>
                <td class="font-semibold">{{ item.quantity_on_hand }}</td>
                <td>{{ item.quantity_reserved }}</td>
                <td class="font-medium" [class.text-red-600]="item.quantity_available <= 0">{{ item.quantity_available }}</td>
                <td>{{ item.avg_cost | number: '1.0-0' }}</td>
                <td>{{ item.stock_value | number: '1.0-0' }}</td>
              </tr>
            }
          </tbody>
        </nz-table>
      </div>

      <div class="surface p-4">
        <div class="mb-3 font-semibold text-slate-950">Lịch sử kho gần đây</div>
        <div class="data-table">
          <nz-table nzSize="small" [nzData]="movements()" [nzLoading]="movementLoading()" [nzScroll]="{ x: '820px' }" [nzFrontPagination]="false">
            <thead>
              <tr>
                <th>Ngày</th>
                <th>Size</th>
                <th>Loại</th>
                <th>SL</th>
                <th>Trước</th>
                <th>Sau</th>
                <th>Ghi chú</th>
              </tr>
            </thead>
            <tbody>
              @for (movement of movements(); track movement.id) {
                <tr>
                  <td>{{ movement.created_at }}</td>
                  <td>{{ movement.size_name }}</td>
                  <td><nz-tag>{{ movement.movement_type }}</nz-tag></td>
                  <td>{{ movement.quantity }}</td>
                  <td>{{ movement.quantity_before }}</td>
                  <td>{{ movement.quantity_after }}</td>
                  <td>{{ movement.note || '-' }}</td>
                </tr>
              }
            </tbody>
          </nz-table>
        </div>
      </div>
    </section>

    <nz-modal [nzVisible]="adjustVisible()" nzTitle="Điều chỉnh tồn kho" nzOkText="Lưu" nzCancelText="Hủy" (nzOnCancel)="adjustVisible.set(false)" (nzOnOk)="adjust()" [nzOkLoading]="saving()">
      <ng-container *nzModalContent>
        <form nz-form nzLayout="vertical" class="form-grid" [formGroup]="adjustForm">
          <nz-form-item>
            <nz-form-label>Size</nz-form-label>
            <nz-select formControlName="size_option_id">
              @for (item of stock(); track item.size_option_id) {
                <nz-option [nzValue]="item.size_option_id" [nzLabel]="'Size ' + item.size_name" />
              }
            </nz-select>
          </nz-form-item>
          <nz-form-item>
            <nz-form-label>Kiểu điều chỉnh</nz-form-label>
            <nz-select formControlName="mode">
              <nz-option nzValue="increase" nzLabel="Tăng" />
              <nz-option nzValue="decrease" nzLabel="Giảm" />
              <nz-option nzValue="set" nzLabel="Đặt lại" />
            </nz-select>
          </nz-form-item>
          <nz-form-item>
            <nz-form-label>Số lượng</nz-form-label>
            <nz-input-number class="!w-full" formControlName="quantity" [nzMin]="1" />
          </nz-form-item>
          <nz-form-item class="full-span">
            <nz-form-label>Ghi chú</nz-form-label>
            <input nz-input formControlName="note" placeholder="Lý do điều chỉnh" />
          </nz-form-item>
        </form>
      </ng-container>
    </nz-modal>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class StockPageComponent implements OnInit {
  readonly auth = inject(AuthService);
  private readonly api = inject(ProductsApi);
  private readonly fb = inject(FormBuilder);
  private readonly message = inject(NzMessageService);

  readonly products = signal<Product[]>([]);
  readonly stock = signal<StockBySize[]>([]);
  readonly movements = signal<StockMovement[]>([]);
  readonly loading = signal(false);
  readonly movementLoading = signal(false);
  readonly saving = signal(false);
  readonly adjustVisible = signal(false);

  readonly filterForm = this.fb.nonNullable.group({ product_id: [0, Validators.required] });
  readonly adjustForm = this.fb.nonNullable.group({
    size_option_id: [0, Validators.required],
    mode: ['increase', Validators.required],
    quantity: [1, Validators.required],
    note: [''],
  });

  readonly totalOnHand = computed(() => this.stock().reduce((sum, item) => sum + Number(item.quantity_on_hand || 0), 0));
  readonly totalReserved = computed(() => this.stock().reduce((sum, item) => sum + Number(item.quantity_reserved || 0), 0));
  readonly totalAvailable = computed(() => this.stock().reduce((sum, item) => sum + Number(item.quantity_available || 0), 0));
  readonly lowStockCount = computed(() => this.stock().filter((item) => Number(item.quantity_available || 0) <= 0).length);

  selectedProductId(): number {
    return Number(this.filterForm.controls.product_id.value || 0);
  }

  ngOnInit(): void {
    this.api.products({ page: 1, pageSize: 100, status: 'active' }).subscribe({
      next: (response) => {
        this.products.set(response.data.items);
        const first = response.data.items[0];
        if (first) {
          this.filterForm.controls.product_id.setValue(first.id);
          this.loadStock();
        }
      },
      error: () => this.message.error('Không tải được sản phẩm'),
    });
  }

  loadStock(): void {
    const productId = this.selectedProductId();
    if (!productId) {
      return;
    }

    this.loading.set(true);
    this.api.stock(productId).subscribe({
      next: (response) => {
        this.stock.set(response.data.items);
        this.loading.set(false);
        this.loadMovements();
      },
      error: () => {
        this.loading.set(false);
        this.message.error('Không tải được tồn kho');
      },
    });
  }

  loadMovements(): void {
    this.movementLoading.set(true);
    this.api.stockMovements({ product_id: this.selectedProductId(), page: 1, pageSize: 10 }).subscribe({
      next: (response) => {
        this.movements.set(response.data.items);
        this.movementLoading.set(false);
      },
      error: () => {
        this.movementLoading.set(false);
        this.message.error('Không tải được lịch sử kho');
      },
    });
  }

  openAdjust(): void {
    const first = this.stock()[0];
    this.adjustForm.reset({ size_option_id: first?.size_option_id ?? 0, mode: 'increase', quantity: 1, note: '' });
    this.adjustVisible.set(true);
  }

  adjust(): void {
    if (this.adjustForm.invalid || !this.selectedProductId()) {
      return;
    }

    this.saving.set(true);
    this.api.adjustStock({
      product_id: this.selectedProductId(),
      size_option_id: this.adjustForm.controls.size_option_id.value,
      mode: this.adjustForm.controls.mode.value,
      quantity: this.adjustForm.controls.quantity.value,
      note: this.adjustForm.controls.note.value,
    }).subscribe({
      next: () => {
        this.message.success('Đã điều chỉnh tồn kho');
        this.saving.set(false);
        this.adjustVisible.set(false);
        this.loadStock();
      },
      error: () => {
        this.saving.set(false);
        this.message.error('Không điều chỉnh được tồn kho');
      },
    });
  }
}
