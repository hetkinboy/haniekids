import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
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

import { Product, TiktokProduct, TiktokSku } from '../../core/api/api.models';
import { AuthService } from '../../core/auth/auth.service';
import { ProductsApi } from '../../core/products/products.api';

@Component({
  selector: 'app-tiktok-products',
  imports: [
    ReactiveFormsModule,
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
    <section class="app-shell space-y-5">
      <div class="page-header">
        <div>
          <h1 class="m-0 text-3xl font-bold tracking-tight text-slate-950">Sản phẩm TikTok</h1>
          <p class="m-0 mt-1 text-sm text-slate-500">Liên kết sản phẩm/SKU TikTok với SKU kho để lấy giá vốn, combo_quantity và tồn theo size.</p>
        </div>
        @if (auth.isAdmin()) {
          <button nz-button (click)="openImportSearch()">
            <span nz-icon nzType="import"></span>
            Import từ search
          </button>
          <button nz-button nzType="primary" (click)="openCreateProduct()">
            <span nz-icon nzType="plus"></span>
            Thêm sản phẩm TikTok
          </button>
        }
      </div>

      <div class="metric-grid">
        <div class="metric-card">
          <div class="metric-label">Tổng sản phẩm TikTok</div>
          <div class="metric-value">{{ total() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Đang hoạt động</div>
          <div class="metric-value text-[#006a65]">{{ activeCount() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Đã liên kết kho</div>
          <div class="metric-value text-blue-700">{{ linkedCount() }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">SKU TikTok đã tải</div>
          <div class="metric-value text-emerald-700">{{ tiktokSkus().length }}</div>
        </div>
      </div>

      <div class="surface p-4">
        <form class="filter-bar" [formGroup]="filters" (ngSubmit)="load()">
          <input nz-input class="!w-80" formControlName="keyword" placeholder="Tìm ID TikTok, tên sản phẩm, mã kho" />
          <nz-select class="!w-44" formControlName="status" nzAllowClear nzPlaceHolder="Trạng thái">
            <nz-option nzValue="active" nzLabel="Active" />
            <nz-option nzValue="inactive" nzLabel="Inactive" />
          </nz-select>
          <button nz-button type="submit">
            <span nz-icon nzType="reload"></span>
            Lọc
          </button>
        </form>
      </div>

      <div class="space-y-5">
        <div class="data-table surface">
          <div class="flex flex-col gap-1 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 class="m-0 text-lg font-semibold text-slate-950">Danh sách sản phẩm TikTok</h2>
              <p class="m-0 text-sm text-slate-500">Chọn một dòng để xem và quản lý SKU TikTok bên dưới.</p>
            </div>
            <div class="text-sm font-medium text-slate-600">{{ total() }} sản phẩm</div>
          </div>
          <nz-table
            [nzData]="items()"
            [nzLoading]="loading()"
            [nzFrontPagination]="false"
            [nzTotal]="total()"
            [nzPageIndex]="page()"
            [nzPageSize]="pageSize()"
            [nzScroll]="{ x: '1020px' }"
            (nzPageIndexChange)="page.set($event); load()"
            (nzPageSizeChange)="pageSize.set($event); load()"
          >
            <thead>
              <tr>
                <th>ID TikTok</th>
                <th>Sản phẩm TikTok</th>
                <th>Sản phẩm kho</th>
                <th>Shop</th>
                <th>Trạng thái</th>
                <th nzRight class="w-[220px]">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              @for (item of items(); track item.id) {
                <tr [class.bg-teal-50]="selectedProduct()?.id === item.id">
                  <td class="font-semibold text-slate-950">{{ item.tiktok_product_id }}</td>
                  <td>
                    <div class="font-medium text-slate-950">{{ item.name }}</div>
                    <div class="text-xs text-slate-500">#{{ item.id }}</div>
                  </td>
                  <td>
                    @if (item.product_id) {
                      <div class="font-medium">{{ item.product_code }}</div>
                      <div class="text-xs text-slate-500">{{ item.warehouse_product_name }}</div>
                    } @else {
                      <nz-tag nzColor="orange">Chưa liên kết</nz-tag>
                    }
                  </td>
                  <td>{{ item.shop_name || '-' }}</td>
                  <td><nz-tag [nzColor]="item.status === 'active' ? 'green' : 'default'">{{ item.status }}</nz-tag></td>
                  <td nzRight>
                    <div class="flex flex-wrap gap-2">
                      <button nz-button nzSize="small" (click)="selectProduct(item)">
                        <span nz-icon nzType="eye"></span>
                        Xem SKU
                      </button>
                      @if (auth.isAdmin()) {
                        <button nz-button nzSize="small" (click)="openEditProduct(item)">
                          <span nz-icon nzType="edit"></span>
                          Sửa
                        </button>
                        <button nz-button nzDanger nzSize="small" nz-popconfirm nzPopconfirmTitle="Xóa sản phẩm TikTok này?" (nzOnConfirm)="deleteProduct(item.id)">
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

        <div class="surface p-5">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div>
              <h2 class="m-0 text-xl font-semibold text-slate-950">SKU TikTok</h2>
              <p class="m-0 mt-1 text-sm text-slate-500">
                @if (selectedProduct()) {
                  Đang quản lý: {{ selectedProduct()?.name }}
                } @else {
                  Chọn một sản phẩm TikTok để quản lý SKU.
                }
              </p>
            </div>
            @if (auth.isAdmin() && selectedProduct()) {
              <button nz-button nzType="primary" (click)="openCreateSku()">
                <span nz-icon nzType="plus"></span>
                Thêm SKU
              </button>
            }
          </div>

          @if (selectedProduct()) {
            <div class="mb-4 grid gap-3 md:grid-cols-3">
              <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="text-xs font-semibold uppercase text-slate-500">ID TikTok</div>
                <div class="mt-1 font-semibold text-slate-950">{{ selectedProduct()?.tiktok_product_id }}</div>
              </div>
              <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="text-xs font-semibold uppercase text-slate-500">Sản phẩm kho</div>
                <div class="mt-1 font-semibold text-slate-950">{{ selectedProduct()?.product_code || 'Chưa liên kết' }}</div>
                <div class="text-xs text-slate-500">{{ selectedProduct()?.warehouse_product_name || '' }}</div>
              </div>
              <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="text-xs font-semibold uppercase text-slate-500">Số SKU</div>
                <div class="mt-1 font-semibold text-slate-950">{{ tiktokSkus().length }}</div>
              </div>
            </div>
            <div class="data-table">
              <nz-table nzSize="small" [nzData]="tiktokSkus()" [nzLoading]="skuLoading()" [nzFrontPagination]="false" [nzScroll]="{ x: '980px' }">
                <thead>
                  <tr>
                    <th>ID SKU TikTok</th>
                    <th>Seller SKU</th>
                    <th>SKU kho</th>
                    <th>Giá / Tồn TikTok</th>
                    <th>Trạng thái</th>
                    @if (auth.isAdmin()) {
                      <th class="w-[150px]">Thao tác</th>
                    }
                  </tr>
                </thead>
                <tbody>
                  @for (sku of tiktokSkus(); track sku.id) {
                    <tr>
                      <td>
                        <div class="font-medium">{{ sku.tiktok_sku_id }}</div>
                        <div class="text-xs text-slate-500">{{ sku.name || '-' }}</div>
                      </td>
                      <td>{{ sku.seller_sku || '-' }}</td>
                      <td>
                        @if (sku.product_sku_id) {
                          <div class="font-medium">{{ sku.warehouse_sku_code }}</div>
                          <div class="text-xs text-slate-500">{{ sku.warehouse_sku_name }}</div>
                        } @else {
                          <nz-tag nzColor="orange">Chưa liên kết</nz-tag>
                        }
                      </td>
                      <td>
                        <div class="font-medium">{{ sku.tiktok_price || 0 }}</div>
                        <div class="text-xs text-slate-500">Tồn TikTok: {{ sku.tiktok_inventory_quantity || 0 }}</div>
                      </td>
                      <td><nz-tag [nzColor]="sku.status === 'active' ? 'green' : 'default'">{{ sku.status }}</nz-tag></td>
                      @if (auth.isAdmin()) {
                        <td>
                          <button nz-button nzSize="small" (click)="openEditSku(sku)">
                            <span nz-icon nzType="edit"></span>
                          </button>
                          <button nz-button nzDanger nzSize="small" class="ml-2" nz-popconfirm nzPopconfirmTitle="Xóa SKU TikTok này?" (nzOnConfirm)="deleteSku(sku.id)">
                            Xóa
                          </button>
                        </td>
                      }
                    </tr>
                  }
                </tbody>
              </nz-table>
            </div>
          } @else {
            <div class="rounded-lg bg-slate-100 p-4 text-sm text-slate-600">Danh sách SKU TikTok sẽ hiển thị ở đây sau khi chọn sản phẩm.</div>
          }
        </div>
      </div>
    </section>

    <nz-modal
      [nzVisible]="importModalVisible()"
      nzTitle="Import từ link search TikTok"
      nzOkText="Import"
      nzCancelText="Hủy"
      [nzOkLoading]="importing()"
      (nzOnCancel)="importModalVisible.set(false)"
      (nzOnOk)="importSearchResponse()"
      nzWidth="920px"
    >
      <ng-container *nzModalContent>
        <form nz-form nzLayout="vertical" [formGroup]="importForm">
          <nz-form-item>
            <nz-form-label nzRequired>Link API search TikTok</nz-form-label>
            <nz-form-control nzErrorTip="Nhập link API search TikTok">
              <input
                nz-input
                formControlName="url"
                placeholder="Ví dụ: https://shopapi.totdep.com/api/tiktok/productsearch"
              />
            </nz-form-control>
          </nz-form-item>
        </form>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
          Backend sẽ gọi link này, đọc JSON trả về, lấy product.id làm ID sản phẩm TikTok, sku.id làm ID SKU TikTok và tự liên kết nếu seller_sku trùng mã SKU kho.
        </div>
      </ng-container>
    </nz-modal>

    <nz-modal
      [nzVisible]="productModalVisible()"
      [nzTitle]="editingProduct() ? 'Sửa sản phẩm TikTok' : 'Thêm sản phẩm TikTok'"
      nzOkText="Lưu"
      nzCancelText="Hủy"
      [nzOkLoading]="saving()"
      (nzOnCancel)="productModalVisible.set(false)"
      (nzOnOk)="saveProduct()"
      nzWidth="720px"
    >
      <ng-container *nzModalContent>
        <form nz-form nzLayout="vertical" class="form-grid" [formGroup]="productForm">
          <nz-form-item>
            <nz-form-label nzRequired>ID sản phẩm TikTok</nz-form-label>
            <nz-form-control nzErrorTip="Nhập ID sản phẩm TikTok">
              <input nz-input formControlName="tiktok_product_id" placeholder="Ví dụ: 1729384756" />
            </nz-form-control>
          </nz-form-item>
          <nz-form-item>
            <nz-form-label nzRequired>Tên sản phẩm TikTok</nz-form-label>
            <nz-form-control nzErrorTip="Nhập tên sản phẩm">
              <input nz-input formControlName="name" placeholder="Tên hiển thị trên TikTok Shop" />
            </nz-form-control>
          </nz-form-item>
          <nz-form-item>
            <nz-form-label>Sản phẩm kho liên kết</nz-form-label>
            <nz-select formControlName="product_id" nzAllowClear nzShowSearch nzPlaceHolder="Chọn sản phẩm kho">
              @for (product of warehouseProducts(); track product.id) {
                <nz-option [nzValue]="product.id" [nzLabel]="product.product_code + ' - ' + product.name" />
              }
            </nz-select>
          </nz-form-item>
          <nz-form-item>
            <nz-form-label>Tên shop</nz-form-label>
            <input nz-input formControlName="shop_name" placeholder="Tên shop TikTok" />
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

    <nz-modal
      [nzVisible]="skuModalVisible()"
      [nzTitle]="editingSku() ? 'Sửa SKU TikTok' : 'Thêm SKU TikTok'"
      nzOkText="Lưu"
      nzCancelText="Hủy"
      [nzOkLoading]="saving()"
      (nzOnCancel)="skuModalVisible.set(false)"
      (nzOnOk)="saveSku()"
      nzWidth="760px"
    >
      <ng-container *nzModalContent>
        <form nz-form nzLayout="vertical" class="form-grid" [formGroup]="skuForm">
          <nz-form-item>
            <nz-form-label nzRequired>ID SKU TikTok</nz-form-label>
            <nz-form-control nzErrorTip="Nhập ID SKU TikTok">
              <input nz-input formControlName="tiktok_sku_id" placeholder="Ví dụ: TT_SKU_123" />
            </nz-form-control>
          </nz-form-item>
          <nz-form-item>
            <nz-form-label>Seller SKU</nz-form-label>
            <input nz-input formControlName="seller_sku" placeholder="Mã seller SKU trên TikTok" />
          </nz-form-item>
          <nz-form-item>
            <nz-form-label>Tên SKU TikTok</nz-form-label>
            <input nz-input formControlName="name" placeholder="Tên biến thể trên TikTok" />
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
export class TiktokProductsComponent implements OnInit {
  readonly auth = inject(AuthService);
  private readonly api = inject(ProductsApi);
  private readonly fb = inject(FormBuilder);
  private readonly message = inject(NzMessageService);

  readonly items = signal<TiktokProduct[]>([]);
  readonly warehouseProducts = signal<Product[]>([]);
  readonly tiktokSkus = signal<TiktokSku[]>([]);
  readonly selectedProduct = signal<TiktokProduct | null>(null);
  readonly editingProduct = signal<TiktokProduct | null>(null);
  readonly editingSku = signal<TiktokSku | null>(null);
  readonly loading = signal(false);
  readonly skuLoading = signal(false);
  readonly saving = signal(false);
  readonly importing = signal(false);
  readonly total = signal(0);
  readonly page = signal(1);
  readonly pageSize = signal(20);
  readonly productModalVisible = signal(false);
  readonly skuModalVisible = signal(false);
  readonly importModalVisible = signal(false);
  readonly activeCount = computed(() => this.items().filter((item) => item.status === 'active').length);
  readonly linkedCount = computed(() => this.items().filter((item) => item.product_id).length);

  readonly filters = this.fb.nonNullable.group({
    keyword: [''],
    status: [''],
  });

  readonly productForm = this.fb.nonNullable.group({
    product_id: [0],
    tiktok_product_id: ['', Validators.required],
    name: ['', Validators.required],
    shop_name: [''],
    status: ['active'],
  });

  readonly skuForm = this.fb.nonNullable.group({
    tiktok_sku_id: ['', Validators.required],
    seller_sku: [''],
    name: [''],
    status: ['active'],
  });
  readonly importForm = this.fb.nonNullable.group({
    url: ['https://shopapi.totdep.com/api/tiktok/productsearch', Validators.required],
  });

  ngOnInit(): void {
    this.loadWarehouseProducts();
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.api.tiktokProducts({ ...this.filters.getRawValue(), page: this.page(), pageSize: this.pageSize() }).subscribe({
      next: (response) => {
        this.items.set(response.data.items);
        this.total.set(response.data.pager.total);
        this.loading.set(false);
        const selected = this.selectedProduct();
        if (selected) {
          const fresh = response.data.items.find((item) => item.id === selected.id) ?? null;
          this.selectedProduct.set(fresh);
        }
      },
      error: () => {
        this.loading.set(false);
        this.message.error('Không tải được sản phẩm TikTok');
      },
    });
  }

  selectProduct(product: TiktokProduct): void {
    this.selectedProduct.set(product);
    this.loadTiktokSkus(product.id);
  }

  openCreateProduct(): void {
    this.editingProduct.set(null);
    this.productForm.reset({ product_id: 0, tiktok_product_id: '', name: '', shop_name: '', status: 'active' });
    this.productModalVisible.set(true);
  }

  openEditProduct(product: TiktokProduct): void {
    this.editingProduct.set(product);
    this.productForm.reset({
      product_id: product.product_id ?? 0,
      tiktok_product_id: product.tiktok_product_id,
      name: product.name,
      shop_name: product.shop_name ?? '',
      status: product.status,
    });
    this.productModalVisible.set(true);
  }

  saveProduct(): void {
    if (this.productForm.invalid) {
      Object.values(this.productForm.controls).forEach((control) => control.markAsDirty());
      return;
    }

    const form = this.productForm.getRawValue();
    const payload: Partial<TiktokProduct> = {
      product_id: form.product_id || null,
      tiktok_product_id: form.tiktok_product_id,
      name: form.name,
      shop_name: form.shop_name || null,
      status: form.status as 'active' | 'inactive',
    };
    const editing = this.editingProduct();
    const request = editing ? this.api.updateTiktokProduct(editing.id, payload) : this.api.createTiktokProduct(payload);

    this.saving.set(true);
    request.subscribe({
      next: () => {
        this.message.success('Đã lưu sản phẩm TikTok');
        this.saving.set(false);
        this.productModalVisible.set(false);
        this.load();
      },
      error: () => {
        this.saving.set(false);
        this.message.error('Không lưu được sản phẩm TikTok');
      },
    });
  }

  deleteProduct(id: number): void {
    this.api.deleteTiktokProduct(id).subscribe({
      next: () => {
        this.message.success('Đã xóa sản phẩm TikTok');
        if (this.selectedProduct()?.id === id) {
          this.selectedProduct.set(null);
          this.tiktokSkus.set([]);
        }
        this.load();
      },
      error: () => this.message.error('Không xóa được sản phẩm TikTok'),
    });
  }

  openImportSearch(): void {
    this.importForm.reset({ url: 'https://shopapi.totdep.com/api/tiktok/productsearch' });
    this.importModalVisible.set(true);
  }

  importSearchResponse(): void {
    if (this.importForm.invalid) {
      Object.values(this.importForm.controls).forEach((control) => control.markAsDirty());
      return;
    }

    this.importing.set(true);
    this.api.importTiktokSearchUrl(this.importForm.controls.url.value).subscribe({
      next: (response) => {
        this.importing.set(false);
        this.importModalVisible.set(false);
        this.message.success(`Đã import ${response.data.skus_created + response.data.skus_updated} SKU TikTok, liên kết ${response.data.skus_linked} SKU kho`);
        this.load();
        const selected = this.selectedProduct();
        if (selected) {
          this.loadTiktokSkus(selected.id);
        }
      },
      error: () => {
        this.importing.set(false);
        this.message.error('Không import được dữ liệu TikTok');
      },
    });
  }

  openCreateSku(): void {
    this.editingSku.set(null);
    this.skuForm.reset({ tiktok_sku_id: '', seller_sku: '', name: '', status: 'active' });
    this.skuModalVisible.set(true);
  }

  openEditSku(sku: TiktokSku): void {
    this.editingSku.set(sku);
    this.skuForm.reset({
      tiktok_sku_id: sku.tiktok_sku_id,
      seller_sku: sku.seller_sku ?? '',
      name: sku.name ?? '',
      status: sku.status,
    });
    this.skuModalVisible.set(true);
  }

  saveSku(): void {
    const product = this.selectedProduct();
    if (!product || this.skuForm.invalid) {
      Object.values(this.skuForm.controls).forEach((control) => control.markAsDirty());
      return;
    }

    const form = this.skuForm.getRawValue();
    const payload: Partial<TiktokSku> = {
      tiktok_sku_id: form.tiktok_sku_id,
      seller_sku: form.seller_sku || null,
      name: form.name || null,
      status: form.status as 'active' | 'inactive',
    };
    const editing = this.editingSku();
    const request = editing ? this.api.updateTiktokSku(editing.id, payload) : this.api.createTiktokSku(product.id, payload);

    this.saving.set(true);
    request.subscribe({
      next: () => {
        this.message.success('Đã lưu SKU TikTok');
        this.saving.set(false);
        this.skuModalVisible.set(false);
        this.loadTiktokSkus(product.id);
      },
      error: () => {
        this.saving.set(false);
        this.message.error('Không lưu được SKU TikTok');
      },
    });
  }

  deleteSku(id: number): void {
    const product = this.selectedProduct();
    if (!product) {
      return;
    }

    this.api.deleteTiktokSku(id).subscribe({
      next: () => {
        this.message.success('Đã xóa SKU TikTok');
        this.loadTiktokSkus(product.id);
      },
      error: () => this.message.error('Không xóa được SKU TikTok'),
    });
  }

  private loadWarehouseProducts(): void {
    this.api.products({ page: 1, pageSize: 100, status: 'active' }).subscribe({
      next: (response) => this.warehouseProducts.set(response.data.items),
      error: () => this.message.error('Không tải được sản phẩm kho'),
    });
  }

  private loadTiktokSkus(productId: number): void {
    this.skuLoading.set(true);
    this.api.tiktokProductSkus(productId).subscribe({
      next: (response) => {
        this.tiktokSkus.set(response.data.items);
        this.skuLoading.set(false);
      },
      error: () => {
        this.skuLoading.set(false);
        this.message.error('Không tải được SKU TikTok');
      },
    });
  }

}
