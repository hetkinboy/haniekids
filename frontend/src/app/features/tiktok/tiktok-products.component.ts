import {
  ChangeDetectionStrategy,
  Component,
  OnInit,
  computed,
  inject,
  signal,
} from '@angular/core';
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
  templateUrl: './tiktok-products.component.html',
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
  readonly summary = signal({ active_products: 0, linked_products: 0 });
  readonly page = signal(1);
  readonly pageSize = signal(20);
  readonly productModalVisible = signal(false);
  readonly skuModalVisible = signal(false);
  readonly importModalVisible = signal(false);
  readonly activeCount = computed(() => this.summary().active_products);
  readonly linkedCount = computed(() => this.summary().linked_products);

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
  // http://localhost:8080/api/tiktok/connections/1/refresh-token

  readonly importForm = this.fb.nonNullable.group({
    url: ['https://shopapi.totdep.com/api/tiktok/productsearch', Validators.required],
  });

  ngOnInit(): void {
    this.loadWarehouseProducts();
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.api
      .tiktokProducts({
        ...this.filters.getRawValue(),
        page: this.page(),
        pageSize: this.pageSize(),
      })
      .subscribe({
        next: (response) => {
          this.items.set(response.data.items);
          this.total.set(response.data.pager.total);
          this.summary.set({
            active_products: response.data.summary?.active_products ?? 0,
            linked_products: response.data.summary?.linked_products ?? 0,
          });
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
    this.productForm.reset({
      product_id: 0,
      tiktok_product_id: '',
      name: '',
      shop_name: '',
      status: 'active',
    });
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
    const request = editing
      ? this.api.updateTiktokProduct(editing.id, payload)
      : this.api.createTiktokProduct(payload);

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
    // http://localhost:8080/api/tiktok/connections/1/refresh-token

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
        this.message.success(
          `Đã import ${response.data.skus_created + response.data.skus_updated} SKU TikTok, liên kết ${response.data.skus_linked} SKU kho`,
        );
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
    const request = editing
      ? this.api.updateTiktokSku(editing.id, payload)
      : this.api.createTiktokSku(product.id, payload);

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
