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
  templateUrl: './stock-page.component.html',
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
