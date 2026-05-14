import { Injectable, computed, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { Store } from '@ngrx/store';

import { API_BASE_URL } from '../api/api.config';
import { ApiResponse } from '../api/api.models';
import { AuthUser, LoginData } from './auth.models';
import { authLoggedIn, authLoggedOut } from '../../store/auth.actions';

const TOKEN_KEY = 'tiktok_cost_token';
const USER_KEY = 'tiktok_cost_user';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly tokenState = signal<string | null>(localStorage.getItem(TOKEN_KEY));
  private readonly userState = signal<AuthUser | null>(this.readStoredUser());

  readonly token = this.tokenState.asReadonly();
  readonly user = this.userState.asReadonly();
  readonly isLoggedIn = computed(() => !!this.tokenState());
  readonly isAdmin = computed(() => this.userState()?.role === 'admin');

  constructor(
    private readonly http: HttpClient,
    private readonly store: Store,
  ) {
    const user = this.userState();
    const token = this.tokenState();

    if (user && token) {
      this.store.dispatch(authLoggedIn({ token, user }));
    }
  }

  login(email: string, password: string): Observable<ApiResponse<LoginData>> {
    return this.http.post<ApiResponse<LoginData>>(`${API_BASE_URL}/auth/login`, { email, password }).pipe(
      tap((response) => {
        this.setSession(response.data.token, response.data.user);
      }),
    );
  }

  logout(): void {
    const token = this.tokenState();

    if (token) {
      this.http.post(`${API_BASE_URL}/auth/logout`, {}).subscribe({ error: () => undefined });
    }

    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    this.tokenState.set(null);
    this.userState.set(null);
    this.store.dispatch(authLoggedOut());
  }

  private setSession(token: string, user: AuthUser): void {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    this.tokenState.set(token);
    this.userState.set(user);
    this.store.dispatch(authLoggedIn({ token, user }));
  }

  private readStoredUser(): AuthUser | null {
    const raw = localStorage.getItem(USER_KEY);

    if (!raw) {
      return null;
    }

    try {
      return JSON.parse(raw) as AuthUser;
    } catch {
      localStorage.removeItem(USER_KEY);
      return null;
    }
  }
}

