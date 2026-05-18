import { isDevMode } from '@angular/core';

export const API_BASE_URL = isDevMode()
  ? 'http://localhost:8080/api'
  : 'https://haniapi.limousinevn.vn/api';
