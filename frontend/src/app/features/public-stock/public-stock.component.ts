import { CommonModule, DOCUMENT } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnDestroy, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzInputModule } from 'ng-zorro-antd/input';
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
    NzInputModule,
    NzTableModule,
    NzTabsModule,
    NzTagModule,
  ],
  templateUrl: './public-stock.component.html',
  styleUrl: './public-stock.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PublicStockComponent implements OnDestroy, OnInit {
  private readonly document = inject(DOCUMENT);
  private readonly api = inject(ProductsApi);
  private readonly message = inject(NzMessageService);
  private readonly lowStockLimit = 3;

  readonly products = signal<PublicStockProduct[]>([]);
  readonly loading = signal(false);
  readonly savingKey = signal('');
  readonly drafts = signal<Record<string, number>>({});
  readonly editingKey = signal('');
  readonly keyboardSpacerHeight = signal(0);

  readonly totalProducts = computed(() => this.products().length);
  readonly totalSizes = computed(() => this.products().reduce((sum, product) => sum + product.rows.length, 0));

  private readonly updateKeyboardSpacer = (): void => {
    const view = this.document.defaultView;

    if (!view || this.editingKey() === '') {
      this.keyboardSpacerHeight.set(0);
      return;
    }

    const viewport = view.visualViewport;
    const keyboardHeight = viewport ? Math.max(0, view.innerHeight - viewport.height - viewport.offsetTop) : 0;
    const isCompactTouchScreen = view.innerWidth <= 1024 && view.matchMedia('(pointer: coarse)').matches;
    const fallbackHeight = isCompactTouchScreen ? 420 : 0;
    const spacerHeight = Math.max(keyboardHeight + 120, fallbackHeight);

    this.keyboardSpacerHeight.set(spacerHeight);
  };

  ngOnInit(): void {
    this.load();
    const view = this.document.defaultView;
    view?.visualViewport?.addEventListener('resize', this.updateKeyboardSpacer);
    view?.visualViewport?.addEventListener('scroll', this.updateKeyboardSpacer);
    view?.addEventListener('resize', this.updateKeyboardSpacer);
  }

  ngOnDestroy(): void {
    const view = this.document.defaultView;
    view?.visualViewport?.removeEventListener('resize', this.updateKeyboardSpacer);
    view?.visualViewport?.removeEventListener('scroll', this.updateKeyboardSpacer);
    view?.removeEventListener('resize', this.updateKeyboardSpacer);
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
    const key = this.rowKey(row);
    this.editingKey.set(key);
    this.updateKeyboardSpacer();
    this.focusEditingInput(key);
  }

  cancelEdit(row: PublicStockRow): void {
    this.setDraft(row, row.quantity_on_hand);
    this.editingKey.set('');
    this.updateKeyboardSpacer();
  }

  draftValue(row: PublicStockRow): number {
    return this.drafts()[this.rowKey(row)] ?? row.quantity_on_hand;
  }

  setDraft(row: PublicStockRow, value: number | string | null): void {
    const digits = String(value ?? '').replace(/\D/g, '');
    const quantity = digits === '' ? 0 : Math.max(0, Number(digits));
    const key = this.rowKey(row);
    this.drafts.update((drafts) => ({ ...drafts, [key]: quantity }));
  }

  onQuantityKeydown(event: KeyboardEvent, product: PublicStockProduct, row: PublicStockRow): void {
    if (event.key === 'Enter') {
      this.save(product, row);
      return;
    }

    if (event.key === 'Escape') {
      this.cancelEdit(row);
      return;
    }

    if (event.ctrlKey || event.metaKey || event.altKey) {
      return;
    }

    const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];
    if (allowedKeys.includes(event.key) || /^\d$/.test(event.key)) {
      return;
    }

    event.preventDefault();
  }

  onQuantityPaste(event: ClipboardEvent, row: PublicStockRow): void {
    const text = event.clipboardData?.getData('text') ?? '';

    if (/^\d+$/.test(text.trim())) {
      return;
    }

    event.preventDefault();
    const digits = text.replace(/\D/g, '');
    this.setDraft(row, digits === '' ? 0 : Number(digits));
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
        this.updateKeyboardSpacer();
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

  private focusEditingInput(key: string): void {
    window.setTimeout(() => {
      const input = this.document.querySelector<HTMLInputElement>(`input[data-edit-key="${key}"]`);

      if (!input) {
        return;
      }

      input.setAttribute('inputmode', 'numeric');
      input.setAttribute('pattern', '[0-9]*');
      input.setAttribute('enterkeyhint', 'done');
      input.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
      input.focus();
      input.select();

      [120, 320, 650, 950].forEach((delay) => {
        window.setTimeout(() => {
          this.updateKeyboardSpacer();
          this.keepInputAboveKeyboard(input);
        }, delay);
      });
    });
  }

  private keepInputAboveKeyboard(input: HTMLInputElement): void {
    const view = this.document.defaultView;

    if (!view || this.document.activeElement !== input) {
      return;
    }

    const rect = input.getBoundingClientRect();
    const viewport = view.visualViewport;
    const viewportTop = viewport?.offsetTop ?? 0;
    const viewportHeight = viewport?.height ?? view.innerHeight;
    const safeTop = viewportTop + 16;
    const safeBottom = viewportTop + viewportHeight - 160;

    if (rect.bottom > safeBottom) {
      view.scrollBy({ top: rect.bottom - safeBottom, behavior: 'smooth' });
      return;
    }

    if (rect.top < safeTop) {
      view.scrollBy({ top: rect.top - safeTop, behavior: 'smooth' });
    }
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
