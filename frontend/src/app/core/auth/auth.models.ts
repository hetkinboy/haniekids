export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'member';
  status: string;
}

export interface LoginData {
  token: string;
  token_type: 'Bearer';
  expires_at: string;
  user: AuthUser;
}

