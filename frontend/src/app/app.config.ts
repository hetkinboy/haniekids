import { ApplicationConfig, isDevMode, provideBrowserGlobalErrorListeners } from '@angular/core';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { provideRouter } from '@angular/router';
import { provideAnimations } from '@angular/platform-browser/animations';
import { provideStore } from '@ngrx/store';
import { provideEffects } from '@ngrx/effects';
import { provideStoreDevtools } from '@ngrx/store-devtools';
import { provideNzIcons } from 'ng-zorro-antd/icon';
import {
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
];

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
  ]
};
