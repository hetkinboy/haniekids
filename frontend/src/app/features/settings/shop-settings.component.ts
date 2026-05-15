import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzFormModule } from 'ng-zorro-antd/form';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzInputModule } from 'ng-zorro-antd/input';
import { NzMessageService } from 'ng-zorro-antd/message';
import { NzSelectModule } from 'ng-zorro-antd/select';
import { NzTableModule } from 'ng-zorro-antd/table';
import { NzTagModule } from 'ng-zorro-antd/tag';

import { TiktokConnection, TiktokWebhookEvent } from '../../core/api/api.models';
import { ProductsApi } from '../../core/products/products.api';

@Component({
  selector: 'app-shop-settings',
  imports: [
    ReactiveFormsModule,
    FormsModule,
    NzButtonModule,
    NzFormModule,
    NzIconModule,
    NzInputModule,
    NzSelectModule,
    NzTableModule,
    NzTagModule,
  ],
  templateUrl: './shop-settings.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ShopSettingsComponent implements OnInit {
  private readonly api = inject(ProductsApi);
  private readonly fb = inject(FormBuilder);
  private readonly message = inject(NzMessageService);

  readonly connections = signal<TiktokConnection[]>([]);
  readonly events = signal<TiktokWebhookEvent[]>([]);
  readonly editing = signal<TiktokConnection | null>(null);
  readonly shop = computed<TiktokConnection | null>(() => this.connections()[0] ?? null);
  readonly loading = signal(false);
  readonly eventLoading = signal(false);
  readonly saving = signal(false);
  readonly eventStatus = signal('');
  readonly rejectedEvents = computed(() => this.events().filter((item) => item.process_status === 'rejected').length);

  readonly form = this.fb.nonNullable.group({
    shop_name: [''],
    shop_id: ['', Validators.required],
    shop_cipher: ['', Validators.required],
    app_key: ['', Validators.required],
    app_secret: [''],
    status: ['active'],
  });

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.api.tiktokConnections().subscribe({
      next: (response) => {
        this.connections.set(response.data.items);
        const current = response.data.items[0] ?? null;
        this.editing.set(current);
        if (current) {
          this.form.reset({
            shop_name: current.shop_name ?? '',
            shop_id: current.shop_id,
            shop_cipher: current.shop_cipher,
            app_key: current.app_key,
            app_secret: '',
            status: current.status,
          });
        }
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.message.error('Khong tai duoc cau hinh shop');
      },
    });
    this.loadEvents();
  }

  loadEvents(): void {
    this.eventLoading.set(true);
    this.api.tiktokWebhookEvents({ process_status: this.eventStatus(), page: 1, pageSize: 20 }).subscribe({
      next: (response) => {
        this.events.set(response.data.items);
        this.eventLoading.set(false);
      },
      error: () => {
        this.eventLoading.set(false);
        this.message.error('Khong tai duoc webhook');
      },
    });
  }

  edit(item: TiktokConnection): void {
    this.editing.set(item);
    this.form.reset({
      shop_name: item.shop_name ?? '',
      shop_id: item.shop_id,
      shop_cipher: item.shop_cipher,
      app_key: item.app_key,
      app_secret: '',
      status: item.status,
    });
  }

  save(): void {
    if (this.form.invalid || (!this.editing() && this.form.controls.app_secret.value.trim() === '')) {
      Object.values(this.form.controls).forEach((control) => control.markAsDirty());
      if (!this.editing() && this.form.controls.app_secret.value.trim() === '') {
        this.message.error('Nhap app secret khi them shop moi');
      }
      return;
    }

    const form = this.form.getRawValue();
    const payload: Partial<TiktokConnection> = {
      shop_name: form.shop_name || null,
      shop_id: form.shop_id,
      shop_cipher: form.shop_cipher,
      app_key: form.app_key,
      app_secret: form.app_secret || undefined,
      status: form.status as 'active' | 'inactive',
      base_url: 'https://open-api.tiktokglobalshop.com',
      auth_base_url: 'https://auth.tiktok-shops.com',
    };
    const editing = this.editing();
    const request = editing ? this.api.updateTiktokConnection(editing.id, payload) : this.api.createTiktokConnection(payload);

    this.saving.set(true);
    request.subscribe({
      next: () => {
        this.saving.set(false);
        this.message.success('Đã lưu cấu hình shop');
        this.load();
      },
      error: () => {
        this.saving.set(false);
        this.message.error('Khong luu duoc cau hinh shop');
      },
    });
  }

  statusColor(status: string): string {
    if (status === 'processed') {
      return 'green';
    }
    if (status === 'rejected' || status === 'failed') {
      return 'red';
    }
    return 'blue';
  }
}
