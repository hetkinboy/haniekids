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
import { DisableNumberWheelDirective } from '../../core/directives/disable-number-wheel.directive';
import { ProductsApi } from '../../core/products/products.api';

@Component({
  selector: 'app-product-skus',
  imports: [
    DecimalPipe,
    DisableNumberWheelDirective,
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
  templateUrl: './product-skus.component.html',
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
  readonly summary = signal({ sellable_skus: 0, active_skus: 0 });
  readonly page = signal(1);
  readonly pageSize = signal(20);
  readonly sellableCount = computed(() => this.summary().sellable_skus);
  readonly activeCount = computed(() => this.summary().active_skus);

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
        this.summary.set({
          sellable_skus: response.data.summary?.sellable_skus ?? 0,
          active_skus: response.data.summary?.active_skus ?? 0,
        });
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
      sale_price: sku.sale_price,
      is_sellable: sku.is_sellable,
      is_active: sku.is_active,
    }).subscribe({
      next: () => this.message.success('Đã lưu SKU'),
      error: () => this.message.error('Không lưu được SKU'),
    });
  }
}
