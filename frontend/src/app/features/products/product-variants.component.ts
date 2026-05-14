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
import { ProductsApi } from '../../core/products/products.api';

@Component({
  selector: 'app-product-variants',
  imports: [
    DecimalPipe,
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
  template: `
    <section class="app-shell space-y-4">
      <div class="page-header">
        <div>
          <h1 class="m-0 text-2xl font-semibold text-slate-950">Biến thể Size/Combo</h1>
          <p class="m-0 mt-1 text-sm text-slate-500">Cấu hình size quản lý tồn và combo quy đổi số bộ khi bán.</p>
        </div>
        <div class="flex gap-2">
          <a nz-button routerLink="/products">Sản phẩm</a>
          <a nz-button nzType="primary" [routerLink]="['/products', productId(), 'skus']">Xem SKU</a>
        </div>
      </div>

      <div class="status-note">
        Tồn kho chỉ được tạo theo Size. Combo 3/5 không phải kho riêng, chỉ lưu combo_quantity để sau này trừ đúng số bộ theo size.
      </div>

      @if (auth.isAdmin()) {
        <div class="grid gap-3 lg:grid-cols-2">
          <form class="surface p-4" [formGroup]="sizeForm" (ngSubmit)="createSizeGroup()">
            <div class="mb-3">
              <div class="font-semibold text-slate-950">Nhóm Size</div>
              <div class="text-xs text-slate-500">Biến thể quản lý tồn kho thật</div>
            </div>
            <div class="grid gap-2 sm:grid-cols-[1fr_120px_auto]">
              <input nz-input formControlName="name" />
              <nz-input-number class="!w-full" formControlName="sort_order" />
              <button nz-button nzType="default" [disabled]="hasSizeGroup()">Tạo</button>
            </div>
          </form>
          <form class="surface p-4" [formGroup]="comboForm" (ngSubmit)="createComboGroup()">
            <div class="mb-3">
              <div class="font-semibold text-slate-950">Nhóm Combo</div>
              <div class="text-xs text-slate-500">Cách bán ra, không tạo tồn kho</div>
            </div>
            <div class="grid gap-2 sm:grid-cols-[1fr_120px_auto]">
              <input nz-input formControlName="name" />
              <nz-input-number class="!w-full" formControlName="sort_order" />
              <button nz-button nzType="default" [disabled]="hasComboGroup()">Tạo</button>
            </div>
          </form>
        </div>
      }

      <div class="grid gap-4 xl:grid-cols-2">
        @for (group of groups(); track group.id) {
          <div class="surface p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
              <div>
                <div class="font-semibold text-slate-950">{{ group.name }}</div>
                <div class="mt-1 flex flex-wrap items-center gap-1 text-xs text-slate-500">
                  <nz-tag>{{ group.type }}</nz-tag>
                  @if (group.is_stock_dimension) {
                    <nz-tag nzColor="blue">Tồn kho theo size</nz-tag>
                  }
                </div>
              </div>
              @if (auth.isAdmin()) {
                <button
                  nz-button
                  nzDanger
                  nzSize="small"
                  nz-popconfirm
                  nzPopconfirmTitle="Xóa nhóm này?"
                  (nzOnConfirm)="deleteGroup(group.id)"
                >
                  Xóa nhóm
                </button>
              }
            </div>

            @if (auth.isAdmin()) {
              <form class="mb-3 grid gap-2 md:grid-cols-4" [formGroup]="optionForm" (ngSubmit)="createOption(group)">
                <input nz-input formControlName="name" placeholder="Tên tùy chọn" />
                @if (group.type === 'text') {
                  <nz-input-number class="!w-full" formControlName="base_cost" nzPlaceHolder="Giá vốn 1 bộ" />
                } @else {
                  <nz-input-number class="!w-full" formControlName="combo_quantity" nzPlaceHolder="Số bộ" />
                }
                <nz-input-number class="!w-full" formControlName="sort_order" nzPlaceHolder="Thứ tự" />
                <button nz-button nzType="primary">
                  <span nz-icon nzType="plus"></span>
                  Thêm
                </button>
              </form>
            }

            <div class="data-table">
              <nz-table nzSize="small" [nzData]="group.options" [nzFrontPagination]="false" [nzScroll]="{ x: '620px' }">
                <thead>
                  <tr>
                    <th>Tên</th>
                    <th>{{ group.type === 'combo' ? 'Số bộ' : 'Giá vốn 1 bộ' }}</th>
                    <th>Thứ tự</th>
                    <th>Active</th>
                    @if (auth.isAdmin()) {
                      <th class="w-[230px]">Thao tác</th>
                    }
                  </tr>
                </thead>
                <tbody>
                  @for (option of group.options; track option.id) {
                    <tr>
                      <td class="font-medium">{{ option.name }}</td>
                      <td>{{ group.type === 'combo' ? option.combo_quantity : (option.base_cost | number: '1.0-0') }}</td>
                      <td>{{ option.sort_order }}</td>
                      <td>
                        <nz-tag [nzColor]="option.is_active ? 'green' : 'default'">{{ option.is_active ? 'Active' : 'Inactive' }}</nz-tag>
                      </td>
                      @if (auth.isAdmin()) {
                        <td>
                          <div class="flex flex-wrap gap-2">
                            <button nz-button nzSize="small" (click)="openEditOption(group, option)">
                              <span nz-icon nzType="edit"></span>
                              Sửa
                            </button>
                            <button nz-button nzSize="small" (click)="toggleOption(option)">
                              {{ option.is_active ? 'Tắt' : 'Bật' }}
                            </button>
                            <button nz-button nzDanger nzSize="small" nz-popconfirm nzPopconfirmTitle="Xóa tùy chọn này?" (nzOnConfirm)="deleteOption(option.id)">
                              Xóa
                            </button>
                          </div>
                        </td>
                      }
                    </tr>
                  }
                </tbody>
              </nz-table>
            </div>
          </div>
        }
      </div>

      @if (auth.isAdmin()) {
        <div class="surface p-5">
          <div class="mb-4">
            <div class="font-semibold text-slate-950">Sinh SKU kho tự động</div>
            <div class="text-sm text-slate-500">Các phần SKU được nối bằng dấu gạch ngang, giữ nguyên chữ hoa/thường bạn nhập. Ví dụ: AT-01-2bo-5.</div>
          </div>
          <form class="space-y-4" [formGroup]="skuForm" (ngSubmit)="generateSkus()">
            <div class="grid gap-4 md:grid-cols-[260px_220px_180px]">
            <nz-form-item>
              <nz-form-label>Tiền tố SKU</nz-form-label>
              <nz-form-control>
                <input nz-input formControlName="sku_prefix" placeholder="Ví dụ: SP001" />
              </nz-form-control>
            </nz-form-item>
            <nz-form-item>
              <nz-form-label>Thứ tự biến thể</nz-form-label>
              <nz-form-control>
                <nz-select formControlName="variant_order">
                  <nz-option nzValue="size_combo" nzLabel="Size trước - Combo sau" />
                  <nz-option nzValue="combo_size" nzLabel="Combo trước - Size sau" />
                </nz-select>
              </nz-form-control>
            </nz-form-item>
            <nz-form-item>
              <nz-form-label>Ghi đè</nz-form-label>
              <nz-form-control>
                <label nz-checkbox formControlName="overwrite">Ghi đè SKU đã có</label>
              </nz-form-control>
            </nz-form-item>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
              <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="mb-3 font-semibold text-slate-800">Biến thể Size</div>
                <div class="grid gap-3 sm:grid-cols-[1fr_180px]">
                  <nz-form-item>
                    <nz-form-label>Tiền tố Size</nz-form-label>
                    <nz-form-control>
                      <input nz-input formControlName="size_prefix" placeholder="Để trống nếu chỉ lấy tên size" />
                    </nz-form-control>
                  </nz-form-item>
                  <nz-form-item>
                    <nz-form-label>Vị trí</nz-form-label>
                    <nz-form-control>
                      <nz-select formControlName="size_prefix_position">
                        <nz-option nzValue="before" nzLabel="Hiển thị trước" />
                        <nz-option nzValue="after" nzLabel="Hiển thị sau" />
                      </nz-select>
                    </nz-form-control>
                  </nz-form-item>
                </div>
              </div>

              <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="mb-3 font-semibold text-slate-800">Biến thể Combo</div>
                <div class="grid gap-3 sm:grid-cols-[1fr_180px]">
                  <nz-form-item>
                    <nz-form-label>Tiền tố Combo</nz-form-label>
                    <nz-form-control>
                      <input nz-input formControlName="combo_prefix" placeholder="Ví dụ: bo" />
                    </nz-form-control>
                  </nz-form-item>
                  <nz-form-item>
                    <nz-form-label>Vị trí</nz-form-label>
                    <nz-form-control>
                      <nz-select formControlName="combo_prefix_position">
                        <nz-option nzValue="before" nzLabel="Hiển thị trước" />
                        <nz-option nzValue="after" nzLabel="Hiển thị sau" />
                      </nz-select>
                    </nz-form-control>
                  </nz-form-item>
                </div>
              </div>
            </div>

            <div class="flex justify-end">
            <button nz-button nzType="primary" [nzLoading]="generating()">
              <span nz-icon nzType="tags"></span>
              Sinh SKU
            </button>
            </div>
          </form>
        </div>
      }
    </section>

    <nz-modal
      [nzVisible]="optionModalVisible()"
      [nzTitle]="editingOptionGroup()?.type === 'combo' ? 'Sửa tùy chọn Combo' : 'Sửa tùy chọn Size'"
      nzOkText="Lưu"
      nzCancelText="Hủy"
      [nzOkLoading]="savingOption()"
      (nzOnCancel)="closeEditOption()"
      (nzOnOk)="saveOption()"
      nzWidth="640px"
    >
      <ng-container *nzModalContent>
        <form nz-form nzLayout="vertical" class="form-grid" [formGroup]="optionEditForm">
          <nz-form-item>
            <nz-form-label nzRequired>Tên tùy chọn</nz-form-label>
            <nz-form-control nzErrorTip="Nhập tên tùy chọn">
              <input nz-input formControlName="name" placeholder="Ví dụ: 5 hoặc Combo 3" />
            </nz-form-control>
          </nz-form-item>
          @if (editingOptionGroup()?.type === 'combo') {
            <nz-form-item>
              <nz-form-label nzRequired>Số bộ</nz-form-label>
              <nz-form-control>
                <nz-input-number class="!w-full" formControlName="combo_quantity" [nzMin]="1" />
              </nz-form-control>
            </nz-form-item>
          } @else {
            <nz-form-item>
              <nz-form-label>Giá vốn 1 bộ</nz-form-label>
              <nz-form-control>
                <nz-input-number class="!w-full" formControlName="base_cost" [nzMin]="0" />
              </nz-form-control>
            </nz-form-item>
          }
          <nz-form-item>
            <nz-form-label>Thứ tự</nz-form-label>
            <nz-form-control>
              <nz-input-number class="!w-full" formControlName="sort_order" [nzMin]="0" />
            </nz-form-control>
          </nz-form-item>
          <nz-form-item>
            <nz-form-label>Trạng thái</nz-form-label>
            <nz-form-control>
              <nz-select formControlName="is_active">
                <nz-option [nzValue]="true" nzLabel="Active" />
                <nz-option [nzValue]="false" nzLabel="Inactive" />
              </nz-select>
            </nz-form-control>
          </nz-form-item>
        </form>
        @if (editingOptionGroup()?.type === 'combo') {
          <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            Nếu Combo đã được dùng để sinh SKU, backend sẽ không cho đổi số bộ. Bạn vẫn sửa được tên, thứ tự và trạng thái.
          </div>
        }
      </ng-container>
    </nz-modal>
  `,
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
