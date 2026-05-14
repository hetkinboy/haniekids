import { createAction, props } from '@ngrx/store';
import { AuthUser } from '../core/auth/auth.models';

export const authLoggedIn = createAction('[Auth] Logged In', props<{ token: string; user: AuthUser }>());
export const authLoggedOut = createAction('[Auth] Logged Out');

