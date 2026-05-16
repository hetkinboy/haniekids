import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzInputNumberModule } from 'ng-zorro-antd/input-number';
import { NzMessageService } from 'ng-zorro-antd/message';
import { NzTableModule } from 'ng-zorro-antd/table';
import { NzTabsModule } from 'ng-zorro-antd/tabs';
import { NzTagModule } from 'ng-zorro-antd/tag';

import { PublicStockCombo, PublicStockProduct, PublicStockRow } from '../../core/api/api.models';
import { ProductsApi } from '../../core/products/products.api';

@Component({
  selector: 'app-public-stock',
  imports: [
    CommonModule,
    FormsModule,
    NzButtonModule,
    NzIconModule,
    NzInputNumberModule,
    NzTableModule,
    NzTabsModule,
    NzTagModule,
  ],
  templateUrl: './public-stock.component.html',
  styleUrl: './public-stock.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PublicStockComponent implements OnInit {
  private readonly api = inject(ProductsApi);
  private readonly message = inject(NzMessageService);
  private readonly lowStockLimit = 3;

  readonly products = signal<PublicStockProduct[]>([]);
  readonly loading = signal(false);
  readonly savingKey = signal('');
  readonly drafts = signal<Record<string, number>>({});
  readonly editingKey = signal('');

  readonly totalProducts = computed(() => this.products().length);
  readonly totalSizes = computed(() => this.products().reduce((sum, product) => sum + product.rows.length, 0));

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.api.publicStock().subscribe({
      next: (response) => {
        this.products.set(response.data.items);
        this.drafts.set(this.buildDrafts(response.data.items));
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.message.error('Khong tai duoc ton kho');
      },
    });
  }

  comboOne(row: PublicStockRow): PublicStockCombo | undefined {
    return row.combos.find((combo) => combo.combo_quantity === 1);
  }

  comboQuantity(row: PublicStockRow, combo: PublicStockCombo): number {
    return intDiv(Math.max(0, this.draftValue(row)), Math.max(1, combo.combo_quantity));
  }

  comboColumns(product: PublicStockProduct): number[] {
    const quantities = new Set<number>([1]);
    product.rows.forEach((row) => {
      row.combos.forEach((combo) => quantities.add(combo.combo_quantity));
    });

    return Array.from(quantities).sort((a, b) => a - b);
  }

  comboByQuantity(row: PublicStockRow, quantity: number): PublicStockCombo | undefined {
    return row.combos.find((combo) => combo.combo_quantity === quantity);
  }

  comboLabel(quantity: number): string {
    return `${quantity} Bo`;
  }

  displayQuantity(row: PublicStockRow, comboQuantity: number, combo?: PublicStockCombo): number {
    if (comboQuantity === 1) {
      return this.draftValue(row);
    }

    if (!combo) {
      return 0;
    }

    return this.comboQuantity(row, combo);
  }

  stockClass(quantity: number): string {
    if (quantity <= 0) {
      return 'empty';
    }

    return quantity <= this.lowStockLimit ? 'low' : 'ok';
  }

  startEdit(row: PublicStockRow): void {
    this.editingKey.set(this.rowKey(row));
  }

  cancelEdit(row: PublicStockRow): void {
    this.setDraft(row, row.quantity_on_hand);
    this.editingKey.set('');
  }

  draftValue(row: PublicStockRow): number {
    return this.drafts()[this.rowKey(row)] ?? row.quantity_on_hand;
  }

  setDraft(row: PublicStockRow, value: number | null): void {
    const quantity = Math.max(0, Number(value ?? 0));
    const key = this.rowKey(row);
    this.drafts.update((drafts) => ({ ...drafts, [key]: quantity }));
  }

  isDirty(row: PublicStockRow): boolean {
    return this.draftValue(row) !== row.quantity_on_hand;
  }

  save(product: PublicStockProduct, row: PublicStockRow): void {
    if (!this.isDirty(row)) {
      return;
    }

    const key = this.rowKey(row);
    this.savingKey.set(key);
    this.api.updatePublicComboOne({
      product_id: product.id,
      size_option_id: row.size_option_id,
      quantity: this.draftValue(row),
    }).subscribe({
      next: () => {
        this.message.success('Da cap nhat so luong');
        this.savingKey.set('');
        this.editingKey.set('');
        this.load();
      },
      error: () => {
        this.savingKey.set('');
        this.message.error('Khong cap nhat duoc so luong');
      },
    });
  }

  rowKey(row: PublicStockRow): string {
    return String(row.size_option_id);
  }

  private buildDrafts(products: PublicStockProduct[]): Record<string, number> {
    return products.reduce<Record<string, number>>((drafts, product) => {
      product.rows.forEach((row) => {
        drafts[this.rowKey(row)] = row.quantity_on_hand;
      });
      return drafts;
    }, {});
  }
}

function intDiv(value: number, divisor: number): number {
  return Math.trunc(value / divisor);
}
