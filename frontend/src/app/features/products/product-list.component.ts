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
  templateUrl: './product-list.component.html',
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
  readonly copying = signal(false);
  readonly total = signal(0);
  readonly summary = signal({ active_products: 0, inactive_products: 0 });
  readonly page = signal(1);
  readonly pageSize = signal(20);
  readonly modalVisible = signal(false);
  readonly copyModalVisible = signal(false);
  readonly editing = signal<Product | null>(null);
  readonly copySource = signal<Product | null>(null);
  readonly activeCount = computed(() => this.summary().active_products);
  readonly inactiveCount = computed(() => this.summary().inactive_products);

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

  readonly copyForm = this.fb.nonNullable.group({
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
        this.summary.set({
          active_products: response.data.summary?.active_products ?? 0,
          inactive_products: response.data.summary?.inactive_products ?? 0,
        });
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

  openCopy(product: Product): void {
    this.copySource.set(product);
    this.copyForm.reset({
      product_code: this.suggestNextCode(product.product_code),
      name: product.name,
      category: product.category ?? '',
      description: product.description ?? '',
      status: 'active',
    });
    this.copyModalVisible.set(true);
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

  copyProduct(): void {
    const source = this.copySource();
    if (!source || this.copyForm.invalid) {
      Object.values(this.copyForm.controls).forEach((control) => control.markAsDirty());
      return;
    }

    this.copying.set(true);
    this.api.copyProduct(source.id, this.copyForm.getRawValue() as Partial<Product>).subscribe({
      next: () => {
        this.message.success('Đã copy sản phẩm và biến thể');
        this.copying.set(false);
        this.copyModalVisible.set(false);
        this.load();
      },
      error: () => {
        this.message.error('Không copy được sản phẩm');
        this.copying.set(false);
      },
    });
  }

  private suggestNextCode(code: string): string {
    const match = code.match(/^(.*?)(\d+)$/);
    if (!match) {
      return `${code}-COPY`;
    }

    const prefix = match[1];
    const number = match[2];
    const next = String(Number(number) + 1).padStart(number.length, '0');

    return `${prefix}${next}`;
  }
}
