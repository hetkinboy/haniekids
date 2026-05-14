import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzCardModule } from 'ng-zorro-antd/card';
import { NzFormModule } from 'ng-zorro-antd/form';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzInputModule } from 'ng-zorro-antd/input';
import { NzMessageService } from 'ng-zorro-antd/message';

import { AuthService } from '../../core/auth/auth.service';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule, NzButtonModule, NzCardModule, NzFormModule, NzIconModule, NzInputModule],
  template: `
    <main class="min-h-dvh bg-[#f6f8fc] px-4 py-8">
      <div class="mx-auto grid min-h-[calc(100dvh-64px)] w-full max-w-5xl items-center gap-6 lg:grid-cols-[1fr_420px]">
        <section class="hidden lg:block">
          <div class="mb-4 inline-flex items-center gap-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700">
            <span nz-icon nzType="shop"></span>
            TikTok Cost
          </div>
          <h1 class="m-0 max-w-xl text-4xl font-semibold leading-tight text-slate-950">Quản lý sản phẩm kho, SKU và tồn theo size</h1>
          <p class="mt-3 max-w-lg text-base text-slate-600">Combo chỉ là cách bán ra. Tồn kho thật luôn nằm trên size để tính giá vốn và trừ kho chính xác.</p>
          <div class="mt-6 grid max-w-lg grid-cols-3 gap-3">
            <div class="surface p-3">
              <div class="text-xs text-slate-500">Kho</div>
              <div class="mt-1 font-semibold text-slate-950">Size</div>
            </div>
            <div class="surface p-3">
              <div class="text-xs text-slate-500">Ban ra</div>
              <div class="mt-1 font-semibold text-slate-950">Combo</div>
            </div>
            <div class="surface p-3">
              <div class="text-xs text-slate-500">Giá vốn</div>
              <div class="mt-1 font-semibold text-slate-950">SKU</div>
            </div>
          </div>
        </section>

        <nz-card class="w-full shadow-sm" [nzBordered]="false">
          <div class="mb-6">
            <div class="text-xl font-semibold text-slate-950">Đăng nhập</div>
            <div class="text-sm text-slate-500">Quản lý sản phẩm kho, size, combo và SKU</div>
          </div>

          <form nz-form [formGroup]="form" (ngSubmit)="submit()" nzLayout="vertical">
            <nz-form-item>
              <nz-form-label>Email</nz-form-label>
              <nz-form-control nzErrorTip="Nhap email hop le">
                <input nz-input formControlName="email" autocomplete="username" />
              </nz-form-control>
            </nz-form-item>

            <nz-form-item>
              <nz-form-label>Mật khẩu</nz-form-label>
              <nz-form-control nzErrorTip="Nhập mật khẩu">
                <input nz-input type="password" formControlName="password" autocomplete="current-password" />
              </nz-form-control>
            </nz-form-item>

            <button nz-button nzType="primary" class="w-full" [nzLoading]="loading()" [disabled]="form.invalid">
              <span nz-icon nzType="login"></span>
              Đăng nhập
            </button>
          </form>
        </nz-card>
        </div>
    </main>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LoginComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly message = inject(NzMessageService);

  readonly loading = signal(false);
  readonly form = this.fb.nonNullable.group({
    email: ['admin@example.com', [Validators.required, Validators.email]],
    password: ['Admin@123', [Validators.required]],
  });

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.loading.set(true);
    this.auth.login(this.form.controls.email.value, this.form.controls.password.value).subscribe({
      next: () => {
        this.loading.set(false);
        this.router.navigateByUrl('/products');
      },
      error: () => {
        this.loading.set(false);
        this.message.error('Đăng nhập không thành công');
      },
    });
  }
}
