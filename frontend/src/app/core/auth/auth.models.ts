export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'member';
  status: string;
  last_login_at?: string | null;
}

export interface LoginData {
  token: string;
  token_type: 'Bearer';
  expires_at: string;
  user: AuthUser;
}

export interface UserListData {
  items: AuthUser[];
}

export interface CreateUserPayload {
  name: string;
  email: string;
  password: string;
  role: 'admin' | 'member';
  status: 'active' | 'inactive';
}

export interface UpdateUserPayload {
  name: string;
  email: string;
  password?: string;
  role: 'admin' | 'member';
  status: 'active' | 'inactive';
}

export interface ChangePasswordPayload {
  current_password: string;
  new_password: string;
}
