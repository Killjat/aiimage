import axios from 'axios';
import type { Message, ChatResponse, AuthResponse, LoginRequest, RegisterRequest, User } from '../types';

// 从环境变量读取 API URL，生产环境必须配置
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

if (!API_BASE_URL) {
  console.error('错误: VITE_API_BASE_URL 环境变量未配置！请在 .env 文件中设置。');
}

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 60000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// 添加请求拦截器，自动添加 token
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Auth API
export const register = async (data: RegisterRequest): Promise<AuthResponse> => {
  const response = await apiClient.post<AuthResponse>('/auth/register', data);
  return response.data;
};

export const login = async (data: LoginRequest): Promise<AuthResponse> => {
  const response = await apiClient.post<AuthResponse>('/auth/login', data);
  return response.data;
};

export const logout = async (): Promise<void> => {
  await apiClient.post('/auth/logout');
};

export const getCurrentUser = async (): Promise<User> => {
  const response = await apiClient.get<{ user: User }>('/auth/me');
  return response.data.user;
};

// Chat API
export const sendMessage = async (
  model: string,
  messages: Message[]
): Promise<ChatResponse> => {
  try {
    const response = await apiClient.post<ChatResponse>('/chat/send', {
      model,
      messages,
    });
    return response.data;
  } catch (error: any) {
    if (error.response?.data) {
      return error.response.data;
    }
    return {
      success: false,
      error: '网络错误，请检查后端服务是否启动',
    };
  }
};

export const checkHealth = async (): Promise<boolean> => {
  try {
    const response = await apiClient.get('/health');
    return response.data.status === 'ok';
  } catch {
    return false;
  }
};
