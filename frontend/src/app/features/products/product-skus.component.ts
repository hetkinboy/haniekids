import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, FormsModule, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzFormModule } from 'ng-zorro-antd/form';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzInputModule } from 'ng-zorro-antd/input';
import { NzInputNumberModule } from 'ng-zorro-antd/input-number';
import { NzMessageService } from 'ng-zorro-antd/message';
import { NzSelectModule } from 'ng-zorro-antd/select';
import { NzSwitchModule } from 'ng-zorro-antd/switch';
import { NzTableModule } from 'ng-zorro-antd/table';
import { NzTagModule } from 'ng-zorro-antd/tag';

import { ProductSku } from '../../core/api/api.models';
import { AuthService } from '../../core/auth/auth.service';
import { ProductsApi } from '../../core/products/products.api';

@Component({
  selector: 'app-product-skus',
  imports: [
    DecimalPipe,
    FormsModule,
    ReactiveFormsModule,
    RouterLink,
    NzButtonModule,
    NzFormModule,
    NzIconModule,
    NzInputModule,
    NzInputNumberModule,
    NzSelectModule,
    NzSwitchModule,
    NzTableModule,
    NzTagModule,
  ],
  template: `
    <section class="app-shell space-y-4">
      <div class="page-header">
        <div>
          <h1 class="m-0 text-2xl font-semibold text-slate-950">SKU kho</h1>
          <p class="m-0 mt-1 text-sm text-slate-500">Mỗi SKU là một cặp Size x Combo, tồn kho vẫn trừ theo Size.</p>
        </div>
        <div class="flex gap-2">
          <a nz-button routerLink="/products">Sản phẩm</a>
          <a nz-button [routerLink]="['/products', productId(), 'variants']">Biến thể</a>
        </div>
      </div>

      <div class="metric-grid">
        <div class="metric-card">
          <div class="metric-label">Tổng SKU</div>
          <div class="metric-value">{{ total() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Đang bán</div>
          <div class="metric-value text-emerald-700">{{ sellableCount() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Active</div>
          <div class="metric-value text-blue-700">{{ activeCount() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Quy đổi combo</div>
          <div class="mt-2 text-sm font-medium text-slate-700">Lấy từ combo_quantity</div>
        </div>
      </div>

      <div class="surface p-3">
        <form class="filter-bar" [formGroup]="filters" (ngSubmit)="load()">
          <input nz-input class="!w-80" formControlName="keyword" placeholder="Tìm SKU, size, combo" />
          <nz-select class="!w-40" formControlName="is_sellable" nzAllowClear nzPlaceHolder="Ban TikTok">
            <nz-option nzValue="true" nzLabel="Có bán" />
            <nz-option nzValue="false" nzLabel="Không bán" />
          </nz-select>
          <nz-select class="!w-36" formControlName="is_active" nzAllowClear nzPlaceHolder="Active">
            <nz-option nzValue="true" nzLabel="Active" />
            <nz-option nzValue="false" nzLabel="Inactive" />
          </nz-select>
          <button nz-button>
            <span nz-icon nzType="reload"></span>
            Lọc
          </button>
        </form>
      </div>

      <div class="data-table surface">
        <nz-table
          [nzData]="skus()"
          [nzLoading]="loading()"
          [nzFrontPagination]="false"
          [nzTotal]="total()"
          [nzPageIndex]="page()"
          [nzPageSize]="pageSize()"
          [nzScroll]="{ x: '860px' }"
          (nzPageIndexChange)="page.set($event); load()"
          (nzPageSizeChange)="pageSize.set($event); load()"
        >
          <thead>
            <tr>
              <th>SKU</th>
              <th>Cost gợi ý</th>
              <th>Cost thực tế</th>
              <th>Giá bán</th>
              <th>Bán</th>
              <th>Active</th>
              @if (auth.isAdmin()) {
                <th nzRight class="w-[120px]">Lưu</th>
              }
            </tr>
          </thead>
          <tbody>
            @for (sku of skus(); track sku.id) {
              <tr>
                <td>
                  <div class="font-semibold text-slate-950">{{ sku.sku_code }}</div>
                  <div class="text-xs text-slate-500">size{{ sku.size_name }} - combo {{ sku.combo_quantity }}</div>
                </td>
                <td>{{ sku.suggested_cost | number: '1.0-0' }}</td>
                <td>
                  @if (auth.isAdmin()) {
                    <nz-input-number class="!w-32" [(ngModel)]="sku.cost_price" [nzMin]="0" />
                  } @else {
                    {{ sku.cost_price | number: '1.0-0' }}
                  }
                </td>
                <td>
                  @if (auth.isAdmin()) {
                    <nz-input-number class="!w-32" [(ngModel)]="sku.sale_price" [nzMin]="0" />
                  } @else {
                    {{ sku.sale_price | number: '1.0-0' }}
                  }
                </td>
                <td>
                  @if (auth.isAdmin()) {
                    <nz-switch [(ngModel)]="sku.is_sellable" />
                  } @else {
                    <nz-tag [nzColor]="sku.is_sellable ? 'green' : 'default'">{{ sku.is_sellable ? 'Có' : 'Không' }}</nz-tag>
                  }
                </td>
                <td>
                  @if (auth.isAdmin()) {
                    <nz-switch [(ngModel)]="sku.is_active" />
                  } @else {
                    <nz-tag [nzColor]="sku.is_active ? 'green' : 'default'">{{ sku.is_active ? 'Active' : 'Inactive' }}</nz-tag>
                  }
                </td>
                @if (auth.isAdmin()) {
                  <td nzRight>
                    <button nz-button nzType="primary" nzSize="small" (click)="save(sku)">
                      <span nz-icon nzType="save"></span>
                      Lưu
                    </button>
                  </td>
                }
              </tr>
            }
          </tbody>
        </nz-table>
      </div>
    </section>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ProductSkusComponent implements OnInit {
  readonly auth = inject(AuthService);
  private readonly api = inject(ProductsApi);
  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly message = inject(NzMessageService);

  readonly productId = signal(Number(this.route.snapshot.paramMap.get('id')));
  readonly skus = signal<ProductSku[]>([]);
  readonly loading = signal(false);
  readonly total = signal(0);
  readonly page = signal(1);
  readonly pageSize = signal(20);
  readonly sellableCount = computed(() => this.skus().filter((sku) => sku.is_sellable).length);
  readonly activeCount = computed(() => this.skus().filter((sku) => sku.is_active).length);

  readonly filters = this.fb.nonNullable.group({
    keyword: [''],
    is_sellable: [''],
    is_active: [''],
  });

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.api.skus(this.productId(), { ...this.filters.getRawValue(), page: this.page(), pageSize: this.pageSize() }).subscribe({
      next: (response) => {
        this.skus.set(response.data.items);
        this.total.set(response.data.pager.total);
        this.loading.set(false);
      },
      error: () => {
        this.message.error('Không tải được SKU');
        this.loading.set(false);
      },
    });
  }

  save(sku: ProductSku): void {
    this.api.updateSku(sku.id, {
      cost_price: sku.cost_price,
      sale_price: sku.sale_price,
      is_sellable: sku.is_sellable,
      is_active: sku.is_active,
    }).subscribe({
      next: () => this.message.success('Đã lưu SKU'),
      error: () => this.message.error('Không lưu được SKU'),
    });
  }
}
