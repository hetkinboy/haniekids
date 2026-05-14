import { ChangeDetectionStrategy, Component, computed, signal } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { NzButtonModule } from 'ng-zorro-antd/button';
import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzLayoutModule } from 'ng-zorro-antd/layout';
import { NzMenuModule } from 'ng-zorro-antd/menu';

import { AuthService } from '../core/auth/auth.service';

@Component({
  selector: 'app-admin-layout',
  imports: [RouterOutlet, RouterLink, RouterLinkActive, NzButtonModule, NzIconModule, NzLayoutModule, NzMenuModule],
  template: `
    <nz-layout class="min-h-dvh">
      <nz-sider
        nzCollapsible
        nzBreakpoint="md"
        [nzCollapsed]="collapsed()"
        (nzCollapsedChange)="collapsed.set($event)"
        nzWidth="256px"
        class="!bg-white border-r border-slate-200 shadow-sm"
      >
        <div class="h-20 px-5 flex items-center gap-3 overflow-hidden text-slate-950">
          <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[#00b5ad]/15 text-[#006a65]" nz-icon nzType="shop" nzTheme="outline"></span>
          @if (!collapsed()) {
          <div>
            <div class="text-xl font-bold leading-6 text-[#006a65]">TikTok Manager</div>
            <div class="text-xs text-slate-500">Small Shop Owner</div>
          </div>
          }
        </div>
        <ul nz-menu nzTheme="light" nzMode="inline" class="!border-0">
          <li nz-menu-item routerLink="/dashboard" routerLinkActive="ant-menu-item-selected">
            <span nz-icon nzType="appstore"></span>
            <span>Dashboard</span>
          </li>
          <li nz-menu-item routerLink="/products" routerLinkActive="ant-menu-item-selected">
            <span nz-icon nzType="appstore"></span>
            <span>Sản phẩm kho</span>
          </li>
          <li nz-menu-item routerLink="/stock" routerLinkActive="ant-menu-item-selected">
            <span nz-icon nzType="tags"></span>
            <span>Tồn kho</span>
          </li>
          <li nz-menu-item routerLink="/purchase-imports" routerLinkActive="ant-menu-item-selected">
            <span nz-icon nzType="plus"></span>
            <span>Nhập hàng</span>
          </li>
          <li nz-menu-item routerLink="/tiktok-products" routerLinkActive="ant-menu-item-selected">
            <span nz-icon nzType="shop"></span>
            <span>Sản phẩm TikTok</span>
          </li>
          <li nz-menu-item routerLink="/tiktok-orders" routerLinkActive="ant-menu-item-selected">
            <span nz-icon nzType="tags"></span>
            <span>Đơn hàng TikTok</span>
          </li>
          <li nz-menu-item routerLink="/settlements" routerLinkActive="ant-menu-item-selected">
            <span nz-icon nzType="save"></span>
            <span>Đối soát tiền</span>
          </li>
          <li nz-menu-item routerLink="/operating-costs" routerLinkActive="ant-menu-item-selected">
            <span nz-icon nzType="edit"></span>
            <span>Chi phí vận hành</span>
          </li>
          <li nz-menu-item routerLink="/reports" routerLinkActive="ant-menu-item-selected">
            <span nz-icon nzType="reload"></span>
            <span>Báo cáo</span>
          </li>
        </ul>
      </nz-sider>
      <nz-layout>
        <nz-header class="!h-auto min-h-16 !bg-white border-b border-slate-200 !px-3 sm:!px-5 py-3 sticky top-0 z-10">
          <div class="app-shell flex flex-wrap items-center justify-between gap-3">
          <div class="min-w-0 flex flex-wrap items-center gap-4">
            <div>
              <div class="font-semibold text-slate-950">TikTok Shop Cost Manager</div>
              <div class="hidden text-xs text-slate-500 sm:block">Tồn kho thật nằm theo size, combo chỉ là cách bán ra.</div>
            </div>
            <div class="hidden w-72 items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-sm text-slate-500 lg:flex">
              <span nz-icon nzType="reload"></span>
              <span>Tìm kiếm dữ liệu...</span>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <span class="hidden rounded-md border border-slate-200 px-2 py-1 text-xs font-medium text-slate-600 sm:inline">{{ userLabel() }}</span>
            <button nz-button nzType="default" (click)="logout()">
              <span nz-icon nzType="logout"></span>
              <span class="hidden sm:inline">Đăng xuất</span>
            </button>
          </div>
          </div>
        </nz-header>
        <nz-content class="app-page p-3 sm:p-4 lg:p-6">
          <router-outlet />
        </nz-content>
      </nz-layout>
    </nz-layout>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminLayoutComponent {
  readonly collapsed = signal(false);
  readonly userLabel = computed(() => {
    const user = this.auth.user();
    return user ? `${user.name} (${user.role})` : '';
  });

  constructor(
    private readonly auth: AuthService,
    private readonly router: Router,
  ) {}

  logout(): void {
    this.auth.logout();
    this.router.navigateByUrl('/login');
  }
}
