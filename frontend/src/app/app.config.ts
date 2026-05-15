import {
  ApplicationConfig,
  LOCALE_ID,
  isDevMode,
  provideBrowserGlobalErrorListeners,
} from '@angular/core';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { registerLocaleData } from '@angular/common';
import localeVi from '@angular/common/locales/vi';
import { provideRouter } from '@angular/router';
import { provideAnimations } from '@angular/platform-browser/animations';
import { provideStore } from '@ngrx/store';
import { provideEffects } from '@ngrx/effects';
import { provideStoreDevtools } from '@ngrx/store-devtools';
import { provideNzIcons } from 'ng-zorro-antd/icon';
import { NZ_I18N, vi_VN } from 'ng-zorro-antd/i18n';
import {
  AppstoreOutline,
  CloudDownloadOutline,
  EditOutline,
  EyeOutline,
  ImportOutline,
  LoginOutline,
  LogoutOutline,
  PlusOutline,
  ReloadOutline,
  SaveOutline,
  SettingOutline,
  ShopOutline,
  TagsOutline,
} from '@ant-design/icons-angular/icons';

import { authInterceptor } from './core/auth/auth.interceptor';
import { routes } from './app.routes';
import { authReducer } from './store/auth.reducer';
import { productReducer } from './store/product.reducer';

const icons = [
  AppstoreOutline,
  EditOutline,
  EyeOutline,
  ImportOutline,
  LoginOutline,
  LogoutOutline,
  PlusOutline,
  ReloadOutline,
  SaveOutline,
  ShopOutline,
  TagsOutline,
  SettingOutline,
  CloudDownloadOutline,
];

registerLocaleData(localeVi);

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideAnimations(),
    provideHttpClient(withInterceptors([authInterceptor])),
    provideRouter(routes),
    provideStore({
      auth: authReducer,
      products: productReducer,
    }),
    provideEffects([]),
    provideStoreDevtools({ maxAge: 25, logOnly: !isDevMode() }),
    provideNzIcons(icons),
    { provide: LOCALE_ID, useValue: 'vi-VN' },
    { provide: NZ_I18N, useValue: vi_VN },
  ],
};
