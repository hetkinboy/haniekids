import { Routes } from '@angular/router';
import { authGuard } from './core/auth/auth.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login.component').then((m) => m.LoginComponent),
  },
  {
    path: '',
    canActivate: [authGuard],
    loadComponent: () => import('./layout/admin-layout.component').then((m) => m.AdminLayoutComponent),
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
      {
        path: 'dashboard',
        loadComponent: () => import('./features/dashboard/dashboard.component').then((m) => m.DashboardComponent),
      },
      {
        path: 'products',
        loadComponent: () => import('./features/products/product-list.component').then((m) => m.ProductListComponent),
      },
      {
        path: 'products/:id/variants',
        loadComponent: () => import('./features/products/product-variants.component').then((m) => m.ProductVariantsComponent),
      },
      {
        path: 'products/:id/skus',
        loadComponent: () => import('./features/products/product-skus.component').then((m) => m.ProductSkusComponent),
      },
      {
        path: 'stock',
        loadComponent: () => import('./features/stock/stock-page.component').then((m) => m.StockPageComponent),
      },
      {
        path: 'purchase-imports',
        loadComponent: () => import('./features/stock/purchase-imports.component').then((m) => m.PurchaseImportsComponent),
      },
      {
        path: 'tiktok-products',
        loadComponent: () => import('./features/tiktok/tiktok-products.component').then((m) => m.TiktokProductsComponent),
      },
      {
        path: 'tiktok-orders',
        data: { module: 'tiktokOrders' },
        loadComponent: () => import('./features/planning/planned-module.component').then((m) => m.PlannedModuleComponent),
      },
      {
        path: 'settlements',
        data: { module: 'settlements' },
        loadComponent: () => import('./features/planning/planned-module.component').then((m) => m.PlannedModuleComponent),
      },
      {
        path: 'operating-costs',
        data: { module: 'operatingCosts' },
        loadComponent: () => import('./features/planning/planned-module.component').then((m) => m.PlannedModuleComponent),
      },
      {
        path: 'reports',
        data: { module: 'reports' },
        loadComponent: () => import('./features/planning/planned-module.component').then((m) => m.PlannedModuleComponent),
      },
    ],
  },
  { path: '**', redirectTo: 'dashboard' },
];
