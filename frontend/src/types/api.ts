export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string;
  created_at: string;
  updated_at: string;
}

export interface AuthResponse {
  status: string;
  message: string;
  data: {
    user: User;
    token: string;
  };
}

export interface ApiResponse<T> {
  status: string;
  message?: string;
  data: T;
}

export interface resetPasswordData {
  token: string;
  password: string;
  email: string;
}
