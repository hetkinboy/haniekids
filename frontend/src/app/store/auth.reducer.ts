import { createReducer, on } from '@ngrx/store';
import { AuthUser } from '../core/auth/auth.models';
import { authLoggedIn, authLoggedOut } from './auth.actions';

export interface AuthState {
  token: string | null;
  user: AuthUser | null;
}

export const initialAuthState: AuthState = {
  token: null,
  user: null,
};

export const authReducer = createReducer(
  initialAuthState,
  on(authLoggedIn, (_, action) => ({ token: action.token, user: action.user })),
  on(authLoggedOut, () => initialAuthState),
);

