
import axios, { AxiosInstance, AxiosResponse, AxiosError } from 'axios';
import { User, AuthResponse, ApiResponse, resetPasswordData } from '@/types/api';

const API_BASE = process.env.NEXT_PUBLIC_API_BASE;
let accessToken: string | null = null;
let isRefreshing = false;
let pendingRequests: Array<(token: string) => void> = [];

export function setAccessToken(token: string | null) {
    accessToken = token;
    if (token) {
      localStorage.setItem('accessToken', token);
    } else {
      localStorage.removeItem('accessToken');
    }
}

class ApiService {
  private api: AxiosInstance;

  constructor() {
    this.api = axios.create({
      baseURL: API_BASE,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      withCredentials: true, // cookie (refresh token) for sending
    });

    // Request Interceptor
    this.api.interceptors.request.use(
      (config) => {
        // 🚨 login/register এ Authorization no need
        if (
          accessToken &&
          config.headers &&
          !config.url?.includes('/auth/login') &&
          !config.url?.includes('/auth/register')
        ) {
          config.headers.Authorization = `Bearer ${accessToken}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response Interceptor
    this.api.interceptors.response.use(
      (response) => response,
      async (error: AxiosError) => {
        const originalRequest = error.config as any;

        if (
          originalRequest.url?.includes('/auth/login') ||
          originalRequest.url?.includes('/auth/register')
        ) {
          return Promise.reject(error); // সরাসরি UI তে error যাবে
        }


        if (error.response?.status === 401 && !originalRequest._retry) {
          originalRequest._retry = true;

          if (!isRefreshing) {
            isRefreshing = true;
            try {
              const response: AxiosResponse<AuthResponse> = await this.api.post('/auth/refresh');
              const newToken = response.data.data.token;
              setAccessToken(newToken);

              // pending requests retry
              pendingRequests.forEach((cb) => cb(newToken as string));
              pendingRequests = [];

              return this.api(originalRequest);
            } catch (refreshError) {
              setAccessToken(null);
              window.location.href = '/login'; // force logout
              return Promise.reject(refreshError);
            } finally {
              isRefreshing = false;
            }
          }

          // Queue pending requests
          return new Promise((resolve) => {
            pendingRequests.push((token: string) => {
              if (originalRequest.headers) {
                originalRequest.headers.Authorization = `Bearer ${token}`;
              }
              resolve(this.api(originalRequest));
            });
          });
        }

        return Promise.reject(error);
      }
    );
  }

  // registration
  async register(data: { name: string; email: string; password: string }): Promise<AuthResponse> {
    const response: AxiosResponse<AuthResponse> = await this.api.post('/auth/register', data);
    return response.data;
  }

  // login
  async login(data: { email: string; password: string }): Promise<AuthResponse> {
    const response: AxiosResponse<AuthResponse> = await this.api.post('/auth/login', data);
    setAccessToken(response.data.data.token);
    return response.data;
  }

  // logout
  async logout(): Promise<void> {
    try {
      await this.api.get('/auth/logout');
    } finally {
      setAccessToken(null);
    }
  }

  // get current user
  async me(): Promise<ApiResponse<User>> {
    const response: AxiosResponse<ApiResponse<User>> = await this.api.get('/auth/me');
    return response.data;
  }

  // forgot password
  async forgotPassword(data: { email: string }): Promise<void> {
    await this.api.post('/auth/forgot-password', data);
  }

  // reset password
  async resetPassword(data: resetPasswordData): Promise<void> {
    await this.api.post('/auth/reset-password', data);
  }
}

export const apiService = new ApiService();