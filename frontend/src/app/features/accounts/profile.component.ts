import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzFormModule } from 'ng-zorro-antd/form';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzInputModule } from 'ng-zorro-antd/input';
import { NzMessageService } from 'ng-zorro-antd/message';
import { NzTagModule } from 'ng-zorro-antd/tag';

import { AuthService } from '../../core/auth/auth.service';

@Component({
  selector: 'app-profile',
  imports: [
    ReactiveFormsModule,
    NzButtonModule,
    NzFormModule,
    NzIconModule,
    NzInputModule,
    NzTagModule,
  ],
  templateUrl: './profile.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ProfileComponent {
  readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);
  private readonly message = inject(NzMessageService);

  readonly saving = signal(false);
  readonly form = this.fb.nonNullable.group({
    current_password: ['', [Validators.required]],
    new_password: ['', [Validators.required, Validators.minLength(8)]],
    confirm_password: ['', [Validators.required]],
  });

  submit(): void {
    if (this.form.invalid) {
      Object.values(this.form.controls).forEach((control) => control.markAsDirty());
      return;
    }

    const form = this.form.getRawValue();

    if (form.new_password !== form.confirm_password) {
      this.message.error('Mật khẩu xác nhận không khớp');
      return;
    }

    this.saving.set(true);
    this.auth
      .changePassword({
        current_password: form.current_password,
        new_password: form.new_password,
      })
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.form.reset({
            current_password: '',
            new_password: '',
            confirm_password: '',
          });
          this.message.success('Đã đổi mật khẩu');
        },
        error: (error) => {
          this.saving.set(false);
          this.message.error(error?.error?.message || 'Không đổi được mật khẩu');
        },
      });
  }
}
