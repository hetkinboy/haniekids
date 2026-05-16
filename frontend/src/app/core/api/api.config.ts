import { isDevMode } from '@angular/core';

export const API_BASE_URL = isDevMode()
  ? 'https://haniapi.limousinevn.vn/api'
  : 'https://haniapi.limousinevn.vn/api';
