import { ChangeDetectionStrategy, Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
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

import { AuthUser } from '../../core/auth/auth.models';
import { AuthService } from '../../core/auth/auth.service';

@Component({
  selector: 'app-accounts',
  imports: [
    ReactiveFormsModule,
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
  templateUrl: './accounts.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AccountsComponent implements OnInit {
  readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);
  private readonly message = inject(NzMessageService);

  readonly users = signal<AuthUser[]>([]);
  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly editVisible = signal(false);
  readonly editing = signal<AuthUser | null>(null);

  readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.minLength(2)]],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(8)]],
    role: ['member' as 'admin' | 'member', [Validators.required]],
    status: ['active' as 'active' | 'inactive', [Validators.required]],
  });

  readonly editForm = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.minLength(2)]],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.minLength(8)]],
    role: ['member' as 'admin' | 'member', [Validators.required]],
    status: ['active' as 'active' | 'inactive', [Validators.required]],
  });

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.auth.users().subscribe({
      next: (response) => {
        this.users.set(response.data.items);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.message.error('Không tải được danh sách tài khoản');
      },
    });
  }

  submit(): void {
    if (this.form.invalid) {
      Object.values(this.form.controls).forEach((control) => control.markAsDirty());
      return;
    }

    this.saving.set(true);
    this.auth.createUser(this.form.getRawValue()).subscribe({
      next: () => {
        this.saving.set(false);
        this.message.success('Đã tạo tài khoản');
        this.form.reset({
          name: '',
          email: '',
          password: '',
          role: 'member',
          status: 'active',
        });
        this.load();
      },
      error: (error) => {
        this.saving.set(false);
        this.message.error(error?.error?.message || 'Không tạo được tài khoản');
      },
    });
  }

  openEdit(user: AuthUser): void {
    this.editing.set(user);
    this.editForm.reset({
      name: user.name,
      email: user.email,
      password: '',
      role: user.role,
      status: user.status === 'inactive' ? 'inactive' : 'active',
    });
    this.editVisible.set(true);
  }

  saveEdit(): void {
    const user = this.editing();

    if (!user || this.editForm.invalid) {
      Object.values(this.editForm.controls).forEach((control) => control.markAsDirty());
      return;
    }

    const form = this.editForm.getRawValue();
    this.saving.set(true);
    this.auth
      .updateUser(user.id, {
        name: form.name,
        email: form.email,
        role: form.role,
        status: form.status,
        password: form.password || undefined,
      })
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.editVisible.set(false);
          this.message.success('Đã cập nhật tài khoản');
          this.load();
        },
        error: (error) => {
          this.saving.set(false);
          this.message.error(error?.error?.message || 'Không cập nhật được tài khoản');
        },
      });
  }

  delete(user: AuthUser): void {
    this.auth.deleteUser(user.id).subscribe({
      next: () => {
        this.message.success('Đã xóa tài khoản');
        this.load();
      },
      error: (error) => this.message.error(error?.error?.message || 'Không xóa được tài khoản'),
    });
  }

  roleLabel(role: string): string {
    return role === 'admin' ? 'Admin' : 'Nhân viên';
  }
}
