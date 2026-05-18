import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzCheckboxModule } from 'ng-zorro-antd/checkbox';
import { NzFormModule } from 'ng-zorro-antd/form';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzInputModule } from 'ng-zorro-antd/input';
import { NzInputNumberModule } from 'ng-zorro-antd/input-number';
import { NzMessageService } from 'ng-zorro-antd/message';
import { NzModalModule } from 'ng-zorro-antd/modal';
import { NzPopconfirmModule } from 'ng-zorro-antd/popconfirm';
import { NzSelectModule } from 'ng-zorro-antd/select';
import { NzSwitchModule } from 'ng-zorro-antd/switch';
import { NzTableModule } from 'ng-zorro-antd/table';
import { NzTagModule } from 'ng-zorro-antd/tag';

import { VariantGroup, VariantOption } from '../../core/api/api.models';
import { AuthService } from '../../core/auth/auth.service';
import { DisableNumberWheelDirective } from '../../core/directives/disable-number-wheel.directive';
import { ProductsApi } from '../../core/products/products.api';

@Component({
  selector: 'app-product-variants',
  imports: [
    DecimalPipe,
    DisableNumberWheelDirective,
    ReactiveFormsModule,
    RouterLink,
    NzButtonModule,
    NzCheckboxModule,
    NzFormModule,
    NzIconModule,
    NzInputModule,
    NzInputNumberModule,
    NzModalModule,
    NzPopconfirmModule,
    NzSelectModule,
    NzSwitchModule,
    NzTableModule,
    NzTagModule,
  ],
  templateUrl: './product-variants.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ProductVariantsComponent implements OnInit {
  readonly auth = inject(AuthService);
  private readonly api = inject(ProductsApi);
  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly message = inject(NzMessageService);

  readonly productId = signal(Number(this.route.snapshot.paramMap.get('id')));
  readonly groups = signal<VariantGroup[]>([]);
  readonly generating = signal(false);
  readonly savingOption = signal(false);
  readonly optionModalVisible = signal(false);
  readonly editingOption = signal<VariantOption | null>(null);
  readonly editingOptionGroup = signal<VariantGroup | null>(null);
  readonly hasSizeGroup = computed(() => this.groups().some((group) => group.is_stock_dimension));
  readonly hasComboGroup = computed(() => this.groups().some((group) => group.type === 'combo'));

  readonly sizeForm = this.fb.nonNullable.group({ name: ['Size', Validators.required], sort_order: [1] });
  readonly comboForm = this.fb.nonNullable.group({ name: ['Combo', Validators.required], sort_order: [2] });
  readonly optionForm = this.fb.nonNullable.group({
    name: ['', Validators.required],
    base_cost: [0],
    combo_quantity: [1],
    sort_order: [1],
  });
  readonly optionEditForm = this.fb.nonNullable.group({
    name: ['', Validators.required],
    base_cost: [0],
    combo_quantity: [1],
    sort_order: [1],
    is_active: [true],
  });
  readonly skuForm = this.fb.nonNullable.group({
    sku_prefix: [''],
    overwrite: [false],
    size_prefix: [''],
    size_prefix_position: ['before'],
    combo_prefix: [''],
    combo_prefix_position: ['before'],
    variant_order: ['size_combo'],
  });

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.api.variantGroups(this.productId()).subscribe({
      next: (response) => this.groups.set(response.data.items),
      error: () => this.message.error('Không tải được biến thể'),
    });
  }

  createSizeGroup(): void {
    this.api.createVariantGroup(this.productId(), {
      name: this.sizeForm.controls.name.value,
      type: 'text',
      is_stock_dimension: true,
      sort_order: this.sizeForm.controls.sort_order.value,
    }).subscribe({ next: () => this.afterChange('Đã tạo nhóm Size'), error: () => this.message.error('Không tạo được nhóm') });
  }

  createComboGroup(): void {
    this.api.createVariantGroup(this.productId(), {
      name: this.comboForm.controls.name.value,
      type: 'combo',
      is_stock_dimension: false,
      sort_order: this.comboForm.controls.sort_order.value,
    }).subscribe({ next: () => this.afterChange('Đã tạo nhóm Combo'), error: () => this.message.error('Không tạo được nhóm') });
  }

  createOption(group: VariantGroup): void {
    const form = this.optionForm.getRawValue();
    const payload = group.type === 'combo'
      ? { name: form.name, combo_quantity: form.combo_quantity, sort_order: form.sort_order }
      : { name: form.name, value: form.name, base_cost: form.base_cost, sort_order: form.sort_order };

    this.api.createVariantOption(group.id, payload).subscribe({
      next: () => {
        this.optionForm.reset({ name: '', base_cost: 0, combo_quantity: 1, sort_order: 1 });
        this.afterChange('Đã thêm tùy chọn');
      },
      error: () => this.message.error('Không thêm được tùy chọn'),
    });
  }

  toggleOption(option: VariantOption): void {
    this.api.updateVariantOption(option.id, { is_active: !option.is_active }).subscribe({
      next: () => this.afterChange('Đã cập nhật tùy chọn'),
      error: () => this.message.error('Không cập nhật được tùy chọn'),
    });
  }

  openEditOption(group: VariantGroup, option: VariantOption): void {
    this.editingOptionGroup.set(group);
    this.editingOption.set(option);
    this.optionEditForm.reset({
      name: option.name,
      base_cost: option.base_cost ?? 0,
      combo_quantity: option.combo_quantity ?? 1,
      sort_order: option.sort_order,
      is_active: option.is_active,
    });
    this.optionModalVisible.set(true);
  }

  closeEditOption(): void {
    this.optionModalVisible.set(false);
    this.editingOption.set(null);
    this.editingOptionGroup.set(null);
  }

  saveOption(): void {
    const option = this.editingOption();
    const group = this.editingOptionGroup();

    if (!option || !group) {
      return;
    }

    if (this.optionEditForm.invalid) {
      Object.values(this.optionEditForm.controls).forEach((control) => control.markAsDirty());
      return;
    }

    const form = this.optionEditForm.getRawValue();
    const payload = group.type === 'combo'
      ? {
          name: form.name,
          combo_quantity: form.combo_quantity,
          sort_order: form.sort_order,
          is_active: form.is_active,
        }
      : {
          name: form.name,
          value: form.name,
          base_cost: form.base_cost,
          sort_order: form.sort_order,
          is_active: form.is_active,
        };

    this.savingOption.set(true);
    this.api.updateVariantOption(option.id, payload).subscribe({
      next: () => {
        this.savingOption.set(false);
        this.closeEditOption();
        this.afterChange('Đã cập nhật tùy chọn');
      },
      error: () => {
        this.savingOption.set(false);
        this.message.error('Không cập nhật được tùy chọn');
      },
    });
  }

  deleteGroup(id: number): void {
    this.api.deleteVariantGroup(id).subscribe({ next: () => this.afterChange('Đã xóa nhóm'), error: () => this.message.error('Không xóa được nhóm') });
  }

  deleteOption(id: number): void {
    this.api.deleteVariantOption(id).subscribe({ next: () => this.afterChange('Đã xóa tùy chọn'), error: () => this.message.error('Không xóa được tùy chọn') });
  }

  generateSkus(): void {
    this.generating.set(true);
    const prefix = this.skuForm.controls.sku_prefix.value || 'SKU';
    const form = this.skuForm.getRawValue();
    this.api.generateSkus(this.productId(), {
      sku_prefix: prefix,
      overwrite: form.overwrite,
      size_prefix: form.size_prefix,
      size_prefix_position: form.size_prefix_position,
      combo_prefix: form.combo_prefix,
      combo_prefix_position: form.combo_prefix_position,
      variant_order: form.variant_order,
    }).subscribe({
      next: () => {
        this.generating.set(false);
        this.message.success('Đã sinh SKU');
      },
      error: () => {
        this.generating.set(false);
        this.message.error('Không sinh được SKU');
      },
    });
  }

  private afterChange(message: string): void {
    this.message.success(message);
    this.load();
  }
}
