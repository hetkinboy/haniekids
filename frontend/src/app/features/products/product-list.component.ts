import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzFormModule } from 'ng-zorro-antd/form';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzInputModule } from 'ng-zorro-antd/input';
import { NzMessageService } from 'ng-zorro-antd/message';
import { NzModalModule } from 'ng-zorro-antd/modal';
import { NzPopconfirmModule } from 'ng-zorro-antd/popconfirm';
import { NzSelectModule } from 'ng-zorro-antd/select';
import { NzTableModule } from 'ng-zorro-antd/table';
import { NzTagModule } from 'ng-zorro-antd/tag';

import { Product } from '../../core/api/api.models';
import { AuthService } from '../../core/auth/auth.service';
import { ProductsApi } from '../../core/products/products.api';
import { productSelected, productsLoaded } from '../../store/product.actions';

@Component({
  selector: 'app-product-list',
  imports: [
    ReactiveFormsModule,
    RouterLink,
    NzButtonModule,
    NzFormModule,
    NzIconModule,
    NzInputModule,
    NzModalModule,
    NzPopconfirmModule,
    NzSelectModule,
    NzTableModule,
    NzTagModule,
  ],
  template: `
    <section class="app-shell space-y-4">
      <div class="page-header">
        <div>
          <h1 class="m-0 text-2xl font-semibold text-slate-950">Sản phẩm kho gốc</h1>
          <p class="m-0 mt-1 text-sm text-slate-500">Quản lý mặt hàng gốc, sau đó cấu hình Size/Combo và sinh SKU kho.</p>
        </div>
        @if (auth.isAdmin()) {
          <button nz-button nzType="primary" (click)="openCreate()">
            <span nz-icon nzType="plus"></span>
            Thêm sản phẩm
          </button>
        }
      </div>

      <div class="metric-grid">
        <div class="metric-card">
          <div class="metric-label">Tổng sản phẩm</div>
          <div class="metric-value">{{ total() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Đang bán</div>
          <div class="metric-value text-emerald-700">{{ activeCount() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Tạm dừng</div>
          <div class="metric-value text-slate-600">{{ inactiveCount() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Quy tắc tồn kho</div>
          <div class="mt-2 text-sm font-medium text-blue-700">Theo size</div>
        </div>
      </div>

      <div class="surface p-3">
        <form class="filter-bar" [formGroup]="filters" (ngSubmit)="load()">
          <input nz-input class="!w-80" formControlName="keyword" placeholder="Tìm mã hoặc tên sản phẩm" />
          <nz-select class="!w-40" formControlName="status" nzAllowClear nzPlaceHolder="Trạng thái">
            <nz-option nzValue="active" nzLabel="Active" />
            <nz-option nzValue="inactive" nzLabel="Inactive" />
          </nz-select>
          <button nz-button nzType="default" type="submit">
            <span nz-icon nzType="reload"></span>
            Lọc
          </button>
        </form>
      </div>

      <div class="data-table desktop-table surface">
        <nz-table
          [nzData]="products()"
          [nzLoading]="loading()"
          [nzPageIndex]="page()"
          [nzPageSize]="pageSize()"
          [nzTotal]="total()"
          [nzFrontPagination]="false"
          [nzScroll]="{ x: '920px' }"
          (nzPageIndexChange)="page.set($event); load()"
          (nzPageSizeChange)="pageSize.set($event); load()"
        >
          <thead>
            <tr>
              <th>Mã</th>
              <th>Tên sản phẩm</th>
              <th>Danh mục</th>
              <th>Trạng thái</th>
              <th nzRight class="w-[280px]">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @for (product of products(); track product.id) {
              <tr>
                <td class="font-semibold text-slate-900">{{ product.product_code }}</td>
                <td>
                  <div class="font-medium text-slate-900">{{ product.name }}</div>
                  <div class="text-xs text-slate-500">{{ product.description || 'Chưa có mô tả' }}</div>
                </td>
                <td>{{ product.category || '-' }}</td>
                <td>
                  <nz-tag [nzColor]="product.status === 'active' ? 'green' : 'default'">{{ product.status }}</nz-tag>
                </td>
                <td nzRight>
                  <div class="flex flex-wrap gap-2">
                    <a nz-button nzSize="small" [routerLink]="['/products', product.id, 'variants']">Biến thể</a>
                    <a nz-button nzSize="small" [routerLink]="['/products', product.id, 'skus']">SKU</a>
                    @if (auth.isAdmin()) {
                      <button nz-button nzSize="small" (click)="openEdit(product)">
                        <span nz-icon nzType="edit"></span>
                        Sửa
                      </button>
                      <button nz-button nzDanger nzSize="small" nz-popconfirm nzPopconfirmTitle="Xóa sản phẩm này?" (nzOnConfirm)="delete(product.id)">
                        Xóa
                      </button>
                    }
                  </div>
                </td>
              </tr>
            }
          </tbody>
        </nz-table>
      </div>

      <div class="mobile-list">
        @for (product of products(); track product.id) {
          <article class="surface p-3">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="font-semibold text-slate-950">{{ product.product_code }}</div>
                <div class="truncate text-sm text-slate-700">{{ product.name }}</div>
                <div class="text-xs text-slate-500">{{ product.category || '-' }}</div>
              </div>
              <nz-tag [nzColor]="product.status === 'active' ? 'green' : 'default'">{{ product.status }}</nz-tag>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <a nz-button nzSize="small" [routerLink]="['/products', product.id, 'variants']">Biến thể</a>
              <a nz-button nzSize="small" [routerLink]="['/products', product.id, 'skus']">SKU</a>
              @if (auth.isAdmin()) {
                <button nz-button nzSize="small" (click)="openEdit(product)"><span nz-icon nzType="edit"></span> Sửa</button>
              }
            </div>
          </article>
        }
      </div>
    </section>

    <nz-modal
      [nzVisible]="modalVisible()"
      [nzTitle]="editing() ? 'Sửa sản phẩm' : 'Thêm sản phẩm'"
      nzOkText="Lưu"
      nzCancelText="Hủy"
      (nzOnCancel)="modalVisible.set(false)"
      (nzOnOk)="save()"
      [nzOkLoading]="saving()"
    >
      <ng-container *nzModalContent>
        <form nz-form nzLayout="vertical" class="form-grid" [formGroup]="form">
          <nz-form-item>
            <nz-form-label nzRequired>Mã sản phẩm</nz-form-label>
            <nz-form-control nzErrorTip="Nhập mã sản phẩm">
              <input nz-input formControlName="product_code" placeholder="Ví dụ: SP001" />
            </nz-form-control>
          </nz-form-item>
          <nz-form-item>
            <nz-form-label nzRequired>Tên sản phẩm</nz-form-label>
            <nz-form-control nzErrorTip="Nhập tên sản phẩm">
              <input nz-input formControlName="name" placeholder="Ví dụ: Áo thun cotton" />
            </nz-form-control>
          </nz-form-item>
          <nz-form-item>
            <nz-form-label>Danh mục</nz-form-label>
            <input nz-input formControlName="category" placeholder="Ví dụ: Thời trang" />
          </nz-form-item>
          <nz-form-item class="full-span">
            <nz-form-label>Mô tả</nz-form-label>
            <textarea nz-input rows="3" formControlName="description" placeholder="Ghi chú ngắn về sản phẩm"></textarea>
          </nz-form-item>
          <nz-form-item>
            <nz-form-label>Trạng thái</nz-form-label>
            <nz-select formControlName="status">
              <nz-option nzValue="active" nzLabel="Active" />
              <nz-option nzValue="inactive" nzLabel="Inactive" />
            </nz-select>
          </nz-form-item>
        </form>
      </ng-container>
    </nz-modal>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ProductListComponent implements OnInit {
  readonly auth = inject(AuthService);
  private readonly api = inject(ProductsApi);
  private readonly fb = inject(FormBuilder);
  private readonly message = inject(NzMessageService);
  private readonly store = inject(Store);

  readonly products = signal<Product[]>([]);
  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly total = signal(0);
  readonly page = signal(1);
  readonly pageSize = signal(20);
  readonly modalVisible = signal(false);
  readonly editing = signal<Product | null>(null);
  readonly activeCount = computed(() => this.products().filter((product) => product.status === 'active').length);
  readonly inactiveCount = computed(() => this.products().filter((product) => product.status !== 'active').length);

  readonly filters = this.fb.nonNullable.group({
    keyword: [''],
    status: [''],
  });

  readonly form = this.fb.nonNullable.group({
    product_code: ['', [Validators.required]],
    name: ['', [Validators.required]],
    category: [''],
    description: [''],
    status: ['active'],
  });

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.api.products({ ...this.filters.getRawValue(), page: this.page(), pageSize: this.pageSize() }).subscribe({
      next: (response) => {
        this.products.set(response.data.items);
        this.total.set(response.data.pager.total);
        this.store.dispatch(productsLoaded({ products: response.data.items, total: response.data.pager.total }));
        this.loading.set(false);
      },
      error: () => {
        this.message.error('Không tải được sản phẩm');
        this.loading.set(false);
      },
    });
  }

  openCreate(): void {
    this.editing.set(null);
    this.form.reset({ product_code: '', name: '', category: '', description: '', status: 'active' });
    this.modalVisible.set(true);
  }

  openEdit(product: Product): void {
    this.editing.set(product);
    this.store.dispatch(productSelected({ product }));
    this.form.reset({
      product_code: product.product_code,
      name: product.name,
      category: product.category ?? '',
      description: product.description ?? '',
      status: product.status,
    });
    this.modalVisible.set(true);
  }

  save(): void {
    if (this.form.invalid) {
      Object.values(this.form.controls).forEach((control) => control.markAsDirty());
      return;
    }

    this.saving.set(true);
    const product = this.editing();
    const request = product
      ? this.api.updateProduct(product.id, this.form.getRawValue() as Partial<Product>)
      : this.api.createProduct(this.form.getRawValue() as Partial<Product>);

    request.subscribe({
      next: () => {
        this.message.success('Đã lưu sản phẩm');
        this.saving.set(false);
        this.modalVisible.set(false);
        this.load();
      },
      error: () => {
        this.message.error('Không lưu được sản phẩm');
        this.saving.set(false);
      },
    });
  }

  delete(id: number): void {
    this.api.deleteProduct(id).subscribe({
      next: () => {
        this.message.success('Đã xóa sản phẩm');
        this.load();
      },
      error: () => this.message.error('Không xóa được sản phẩm'),
    });
  }
}
